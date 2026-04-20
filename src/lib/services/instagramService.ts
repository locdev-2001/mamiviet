import { fetchApi } from '../utils';

export interface InstagramPost {
  id: number;
  type: string;
  short_code: string;
  caption: string;
  hashtags: string[];
  mentions: string[];
  url: string;
  comments_count: number;
  first_comment: string;
  latest_comments: string[];
  dimensions_height: number;
  dimensions_width: number;
  display_url: string;
  images: string[];
  alt: string | null;
  likes_count: number;
  timestamp: string;
  child_posts: any[];
  owner_full_name: string;
  owner_username: string;
  owner_id: number;
  is_comments_disabled: boolean;
  input_url: string;
  is_sponsored: boolean;
}

export interface InstagramResponse {
  data: InstagramPost[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    links: any[];
    path: string;
    per_page: number;
    to: number;
    total: number;
  };
}

export const instagramService = {
  getPosts: (page: number = 1): Promise<InstagramResponse> => 
    fetchApi(`/user/instagram-posts?page=${page}`),
};