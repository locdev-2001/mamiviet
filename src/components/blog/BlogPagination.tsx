import { useTranslation } from "react-i18next";
import type { PostPagination } from "@/lib/types/post";

type Props = {
  pagination: PostPagination;
  basePath: string;
};

export function BlogPagination({ pagination, basePath }: Props) {
  const { t } = useTranslation();
  const { current_page: current, last_page: last } = pagination;

  if (last <= 1) return null;

  const prevUrl = current > 1 ? `${basePath}?page=${current - 1}` : null;
  const nextUrl = current < last ? `${basePath}?page=${current + 1}` : null;

  return (
    <nav className="mt-12 flex items-center justify-between gap-4" aria-label="Pagination">
      {prevUrl ? (
        <a
          href={prevUrl}
          rel="prev"
          className="inline-flex items-center gap-2 rounded-md border border-neutral-700 px-4 py-2 text-sm text-neutral-200 hover:border-amber-400 hover:text-amber-400"
        >
          <span aria-hidden="true">←</span>
          {t("blog.pagination.previous")}
        </a>
      ) : (
        <span />
      )}

      <span className="text-sm text-neutral-400">
        {t("blog.pagination.page", { current, total: last })}
      </span>

      {nextUrl ? (
        <a
          href={nextUrl}
          rel="next"
          className="inline-flex items-center gap-2 rounded-md border border-neutral-700 px-4 py-2 text-sm text-neutral-200 hover:border-amber-400 hover:text-amber-400"
        >
          {t("blog.pagination.next")}
          <span aria-hidden="true">→</span>
        </a>
      ) : (
        <span />
      )}
    </nav>
  );
}
