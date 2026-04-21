import { useTranslation } from "react-i18next";
import { PostMeta } from "./PostMeta";
import type { PostMetaData } from "@/lib/types/post";

type Props = {
  post: PostMetaData;
  eager?: boolean;
};

export function PostCard({ post, eager = false }: Props) {
  const { t } = useTranslation();
  const loading = eager ? "eager" : "lazy";
  const fetchPriority = eager ? "high" : "auto";

  return (
    <article className="group flex flex-col overflow-hidden rounded-lg bg-neutral-900 transition hover:bg-neutral-800">
      <a href={post.url} className="block" aria-label={post.title}>
        <div className="relative aspect-[16/10] overflow-hidden bg-neutral-800">
          {post.cover ? (
            <img
              src={post.cover.card}
              srcSet={`${post.cover.thumb} 400w, ${post.cover.card} 800w, ${post.cover.hero} 1600w`}
              sizes="(max-width: 768px) 100vw, (max-width: 1280px) 50vw, 33vw"
              alt={post.title}
              loading={loading}
              // @ts-expect-error fetchpriority not in React types yet
              fetchpriority={fetchPriority}
              decoding="async"
              className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center bg-neutral-800 text-neutral-600">
              <span className="text-xs uppercase tracking-wider">Mamiviet</span>
            </div>
          )}
        </div>
      </a>

      <div className="flex flex-1 flex-col gap-3 p-5">
        <h2 className="line-clamp-2 text-lg font-semibold leading-snug text-white">
          <a href={post.url} className="hover:text-amber-400">
            {post.title}
          </a>
        </h2>
        {post.excerpt && (
          <p className="line-clamp-3 text-sm text-neutral-400">{post.excerpt}</p>
        )}
        <PostMeta post={post} className="mt-auto" />
        <a
          href={post.url}
          className="mt-2 inline-flex items-center gap-1 text-sm font-medium text-amber-400 hover:text-amber-300"
        >
          {t("blog.read_more")}
          <span aria-hidden="true">→</span>
        </a>
      </div>
    </article>
  );
}
