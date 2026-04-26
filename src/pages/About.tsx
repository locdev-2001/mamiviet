import { useTranslation } from "react-i18next";
import { Header } from "@/components/Header";
import { Footer } from "@/components/Footer";
import { useAppContent, useAppLocale } from "@/lib/hooks/useAppContent";

export default function About() {
  const { t } = useTranslation();
  const locale = useAppLocale();
  const appContent = useAppContent();
  const homePath = locale === "en" ? "/en" : "/";

  const aboutData = appContent?.about as { title?: string; content?: string; heroImage?: string } | undefined;
  const title = aboutData?.title ?? t("about.default_title");
  const content = aboutData?.content ?? null;
  const heroImage = aboutData?.heroImage || "/restaurant.jpg";

  return (
    <div className="min-h-screen bg-background text-white flex flex-col">
      <Header />
      <main className="flex-1 pt-[92px] md:pt-[118px]">
        <article className="overflow-hidden">
          <section className="relative border-b border-primary/15 bg-[#2c241c]">
            <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_15%,rgba(180,140,103,0.18),transparent_32%),linear-gradient(90deg,rgba(0,0,0,0.18),transparent_45%)]" />
            <div className="relative mx-auto grid max-w-7xl items-center gap-12 px-6 py-16 md:py-20 lg:grid-cols-[0.92fr_1.08fr] lg:py-24">
              <div>
                <a
                  href={homePath}
                  className="mb-10 inline-flex items-center gap-3 text-[11px] font-source-semibold uppercase tracking-[0.24em] text-white/55 transition-colors hover:text-primary"
                >
                  <span aria-hidden="true">←</span>
                  {t("about.back_home")}
                </a>
                <div className="mb-7 flex items-center gap-4">
                  <img src="/logo.png" alt="" className="h-14 w-auto" />
                  <div className="h-px flex-1 bg-gradient-to-r from-primary/70 to-transparent" />
                </div>
                <p className="mb-5 text-[12px] font-source-semibold uppercase tracking-[0.34em] text-primary">
                  {t("about.title")}
                </p>
                <h1 className="max-w-2xl font-cormorant-light text-5xl leading-[0.98] tracking-wide text-white md:text-6xl lg:text-7xl">
                  {title}
                </h1>
              </div>

              <div className="relative">
                <div className="absolute -left-5 -top-5 h-24 w-24 border-l border-t border-primary/45" />
                <div className="absolute -bottom-5 -right-5 h-24 w-24 border-b border-r border-primary/45" />
                <div className="relative aspect-[16/10] overflow-hidden border border-white/10 bg-black/20 shadow-[0_30px_80px_rgba(0,0,0,0.35)]">
                  <img
                    src={heroImage}
                    alt={title}
                    className="h-full w-full object-cover"
                  />
                  <div className="absolute inset-0 bg-gradient-to-tr from-[#1f1913]/10 via-transparent to-transparent" />
                </div>
              </div>
            </div>
          </section>

          <section className="relative bg-[#15110d]">
            <div className="absolute inset-y-0 left-1/2 hidden w-px bg-white/10 lg:block" />
            <div className="mx-auto grid max-w-7xl gap-12 px-6 py-14 md:py-20 lg:grid-cols-[280px_minmax(0,760px)]">
              <aside className="hidden lg:block">
                <div className="sticky top-40">
                  <div className="mb-8 h-px w-24 bg-primary/70" />
                  <div className="inline-flex h-28 w-28 items-center justify-center rounded-full border border-primary/25 bg-white/92 shadow-[0_18px_45px_rgba(0,0,0,0.3)]">
                    <img src="/logo.png" alt="" className="h-20 w-auto" />
                  </div>
                  <div className="mt-8 h-px w-40 bg-gradient-to-r from-primary/50 to-transparent" />
                </div>
              </aside>
            {content ? (
              <div
                className="prose prose-invert max-w-none
                  prose-headings:font-cormorant-light prose-headings:font-normal prose-headings:tracking-wide
                  prose-h2:mb-5 prose-h2:mt-12 prose-h2:text-3xl prose-h2:text-primary md:prose-h2:text-4xl
                  prose-p:mb-6 prose-p:font-inter prose-p:text-[16px] prose-p:leading-8 prose-p:text-white/76
                  prose-strong:text-white
                  prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                  [&>p:first-of-type]:text-xl [&>p:first-of-type]:leading-9 [&>p:first-of-type]:text-white/90"
                dangerouslySetInnerHTML={{ __html: content }}
              />
            ) : (
              <p className="font-inter text-white/65 italic">{t("about.title")}</p>
            )}
            </div>
          </section>
        </article>
      </main>
      <Footer />
    </div>
  );
}
