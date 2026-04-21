import { useTranslation } from "react-i18next";
import { PostCard } from "./PostCard";
import type { PostMetaData } from "@/lib/types/post";

type Props = {
  posts: PostMetaData[];
};

export function RelatedPosts({ posts }: Props) {
  const { t } = useTranslation();

  if (!posts || posts.length === 0) return null;

  return (
    <section className="border-t border-neutral-800 bg-neutral-950 py-16">
      <div className="mx-auto max-w-6xl px-4">
        <h2 className="mb-8 text-2xl font-semibold text-white">{t("blog.related_posts")}</h2>
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {posts.map((post) => (
            <PostCard key={post.id} post={post} />
          ))}
        </div>
      </div>
    </section>
  );
}
