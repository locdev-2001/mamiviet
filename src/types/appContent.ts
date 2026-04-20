export type MediaItem = {
  src: string;
  srcset?: string;
  type?: string;
  alt?: string;
  width?: number;
  height?: number;
};

type MediaMap = Record<string, MediaItem | MediaItem[] | undefined>;

export type HomepageSection<C = Record<string, string>, D = Record<string, unknown>> = {
  enabled: boolean;
  content: Partial<C>;
  media: MediaMap;
  data: D | null;
};

export type HomepageContent = {
  hero: HomepageSection<{ title: string }>;
  welcome: HomepageSection<{
    brand_name: string;
    tagline: string;
    cuisine_label: string;
    title: string;
    body: string;
    cta_label: string;
  }>;
  welcome_second: HomepageSection<{
    cuisine_label: string;
    title: string;
    body: string;
    cta_label: string;
  }>;
  order: HomepageSection<{
    title: string;
    takeaway: string;
    delivery: string;
    reservation: string;
    free_delivery: string;
    cta_label: string;
  }>;
  reservation: HomepageSection<{
    title: string;
    subtitle: string;
    note: string;
    cta_label: string;
    overlay_text: string;
    overlay_subtitle: string;
  }>;
  contact: HomepageSection<
    {
      title: string;
      restaurant_name: string;
      address: string;
      phone: string;
      email: string;
      instagram_label: string;
    },
    { instagram_url?: string; map_embed?: string }
  >;
  gallery_slider: HomepageSection<{ title: string; subtitle: string }>;
  intro: HomepageSection<{ title: string; text1: string; text2: string }>;
};

export type SiteSettings = Record<string, string | null>;

export type AppContent = {
  locale: string;
  settings?: SiteSettings;
  homepage?: HomepageContent;
};

declare global {
  interface Window {
    __APP_CONTENT__?: AppContent | null;
    __APP_LOCALE__?: string;
  }
}
