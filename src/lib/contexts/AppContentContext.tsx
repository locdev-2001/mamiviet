import { createContext, useContext, type ReactNode } from 'react';
import type { AppContent, HomepageContent, HomepageSection, MediaItem } from '@/types/appContent';

const AppContentContext = createContext<AppContent | null>(null);

export function AppContentProvider({ children }: { children: ReactNode }) {
  const value = typeof window !== 'undefined' ? window.__APP_CONTENT__ ?? null : null;
  return <AppContentContext.Provider value={value}>{children}</AppContentContext.Provider>;
}

export function useAppContent(): AppContent | null {
  return useContext(AppContentContext);
}

export function useHomepageSection<K extends keyof HomepageContent>(
  key: K
): HomepageContent[K] | null {
  const content = useContext(AppContentContext);
  return (content?.homepage?.[key] ?? null) as HomepageContent[K] | null;
}

export function useSetting(key: string, fallback = ''): string {
  const content = useContext(AppContentContext);
  const value = content?.settings?.[key];
  return value !== undefined && value !== null && value !== '' ? value : fallback;
}

export function getSingleMedia(section: HomepageSection | null, collection: string): MediaItem | null {
  const value = section?.media?.[collection];
  if (!value) return null;
  return Array.isArray(value) ? value[0] ?? null : value;
}

export function getMediaList(section: HomepageSection | null, collection: string): MediaItem[] {
  const value = section?.media?.[collection];
  if (!value) return [];
  return Array.isArray(value) ? value : [value];
}
