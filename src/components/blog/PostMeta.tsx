import { useTranslation } from "react-i18next";
import type { PostMetaData } from "@/lib/types/post";

type Props = {
  post: Pick<PostMetaData, "author_name" | "published_at_iso" | "published_at_display" | "reading_time">;
  className?: string;
};

export function PostMeta({ post, className = "" }: Props) {
  const { t } = useTranslation();

  return (
    <div className={`flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-neutral-400 ${className}`}>
      {post.published_at_iso && (
        <time dateTime={post.published_at_iso}>{post.published_at_display}</time>
      )}
      {post.author_name && (
        <>
          <span aria-hidden="true">·</span>
          <span>{t("blog.by_author", { author: post.author_name })}</span>
        </>
      )}
      {post.reading_time > 0 && (
        <>
          <span aria-hidden="true">·</span>
          <span>{t("blog.reading_time", { minutes: post.reading_time })}</span>
        </>
      )}
    </div>
  );
}
