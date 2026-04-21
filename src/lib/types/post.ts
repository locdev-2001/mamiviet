export type PostCover = {
  url: string;
  thumb: string;
  card: string;
  hero: string;
  width: number;
  height: number;
};

export type PostMetaData = {
  id: number;
  slug: string;
  title: string;
  excerpt: string;
  cover: PostCover | null;
  og_image: string | null;
  author_name: string;
  published_at_iso: string | null;
  published_at_display: string;
  reading_time: number;
  url: string;
};

export type PostPagination = {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
};

export type BlogListPayload = {
  posts: PostMetaData[];
  pagination: PostPagination;
};

export type BlogShowPayload = {
  post: PostMetaData;
  related: PostMetaData[];
};

export type BlogNotFoundPayload = {
  not_found: true;
};

export type BlogPayload = BlogListPayload | BlogShowPayload | BlogNotFoundPayload;

export type AppContent = {
  locale: string;
  settings: Record<string, unknown>;
  homepage?: unknown;
  blog?: BlogPayload;
};
