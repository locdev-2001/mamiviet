import { useMemo } from "react";
import type { AppContent } from "@/lib/types/post";

declare global {
  interface Window {
    __APP_CONTENT__?: AppContent | null;
    __APP_LOCALE__?: string;
  }
}

export function useAppContent(): AppContent | null {
  return useMemo(() => (typeof window !== "undefined" ? window.__APP_CONTENT__ ?? null : null), []);
}

export function useAppLocale(): string {
  return useMemo(() => {
    if (typeof window === "undefined") return "de";
    return window.__APP_LOCALE__ ?? document.documentElement.lang ?? "de";
  }, []);
}
