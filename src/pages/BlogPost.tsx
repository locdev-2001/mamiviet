import { useTranslation } from "react-i18next";
import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";
import { PostMeta } from "@/components/blog/PostMeta";
import { PostContent } from "@/components/blog/PostContent";
import { RelatedPosts } from "@/components/blog/RelatedPosts";
import { useAppContent, useAppLocale } from "@/lib/hooks/useAppContent";
import type { BlogShowPayload, BlogNotFoundPayload } from "@/lib/types/post";

function isShowPayload(v: unknown): v is BlogShowPayload {
  return typeof v === "object" && v !== null && "post" in v;
}

function isNotFound(v: unknown): v is BlogNotFoundPayload {
  return typeof v === "object" && v !== null && "not_found" in v;
}

export default function BlogPost() {
  const { t } = useTranslation();
  const locale = useAppLocale();
  const appContent = useAppContent();
  const payload = appContent?.blog;
  const blogPath = locale === "en" ? "/en/blog" : "/blog";

  if (!payload || isNotFound(payload)) {
    return (
      <div className="min-h-screen bg-black text-white flex flex-col">
        <Header />
        <main className="flex-1 pt-24 md:pt-36">
          <section className="mx-auto max-w-3xl px-4 py-24 text-center">
            <h1 className="text-3xl font-bold text-white">{t("blog.not_found.title")}</h1>
            <p className="mt-4 text-neutral-400">{t("blog.not_found.message")}</p>
            <a
              href={blogPath}
              className="mt-8 inline-flex items-center gap-2 rounded-md border border-neutral-700 px-6 py-3 text-sm font-medium text-neutral-200 hover:border-amber-400 hover:text-amber-400"
            >
              <span aria-hidden="true">←</span>
              {t("blog.not_found.cta")}
            </a>
          </section>
        </main>
        <Footer />
      </div>
    );
  }

  if (!isShowPayload(payload)) return null;

  const { post, related } = payload;

  return (
    <div className="min-h-screen bg-black text-white flex flex-col">
      <Header />
      <main className="flex-1 pt-24 md:pt-36">
        <article>
          {post.cover && (
            <div className="relative h-[42vh] min-h-[320px] w-full overflow-hidden bg-neutral-900 sm:h-[56vh]">
              <img
                src={post.cover.hero}
                srcSet={`${post.cover.card} 800w, ${post.cover.hero} 1600w`}
                sizes="100vw"
                alt={post.title}
                loading="eager"
                // @ts-expect-error fetchpriority not in React types yet
                fetchpriority="high"
                decoding="async"
                className="h-full w-full object-cover"
              />
              <div className="absolute inset-0 bg-gradient-to-b from-black/20 via-black/40 to-black" />
            </div>
          )}

          <header className="mx-auto max-w-3xl px-4 pt-10">
            <a
              href={blogPath}
              className="mb-4 inline-flex items-center gap-2 text-sm text-neutral-400 hover:text-amber-400"
            >
              <span aria-hidden="true">←</span>
              {t("blog.back_to_list")}
            </a>
            <h1 className="text-3xl font-bold leading-tight text-white sm:text-4xl">{post.title}</h1>
            {post.excerpt && <p className="mt-4 text-lg text-neutral-300">{post.excerpt}</p>}
            <PostMeta post={post} className="mt-6" />
          </header>

          <div className="mx-auto max-w-3xl px-4 py-10">
            <PostContent />
          </div>
        </article>

        <RelatedPosts posts={related} />
      </main>
      <Footer />
    </div>
  );
}
