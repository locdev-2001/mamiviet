import { useTranslation } from "react-i18next";
import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";
import { PostCard } from "@/components/blog/PostCard";
import { BlogPagination } from "@/components/blog/BlogPagination";
import { useAppContent, useAppLocale } from "@/lib/hooks/useAppContent";
import type { BlogListPayload } from "@/lib/types/post";

function isListPayload(v: unknown): v is BlogListPayload {
  return typeof v === "object" && v !== null && "posts" in v && Array.isArray((v as BlogListPayload).posts);
}

export default function Blog() {
  const { t } = useTranslation();
  const locale = useAppLocale();
  const appContent = useAppContent();
  const payload = appContent?.blog;

  const data = isListPayload(payload) ? payload : null;
  const basePath = locale === "en" ? "/en/blog" : "/blog";

  return (
    <div className="min-h-screen bg-black text-white flex flex-col">
      <Header />
      <main className="flex-1 pt-24 md:pt-36">
        <section className="mx-auto max-w-6xl px-4 py-12">
          <header className="mb-10 text-center">
            <h1 className="text-4xl font-bold tracking-tight text-white sm:text-5xl">
              {t("blog.title")}
            </h1>
            <p className="mt-3 text-neutral-400">{t("blog.tagline")}</p>
          </header>

          {!data || data.posts.length === 0 ? (
            <p className="py-20 text-center text-neutral-500">{t("blog.empty")}</p>
          ) : (
            <>
              <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {data.posts.map((post, idx) => (
                  <PostCard key={post.id} post={post} eager={idx < 3} />
                ))}
              </div>
              <BlogPagination pagination={data.pagination} basePath={basePath} />
            </>
          )}
        </section>
      </main>
      <Footer />
    </div>
  );
}
