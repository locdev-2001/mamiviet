import { GloriaFoodButton } from '@/components/GloriaFoodButton';
import MediaImage from '@/components/MediaImage';
import { getSingleMedia, useHomepageSection } from '@/lib/contexts/AppContentContext';
import { useTranslation } from 'react-i18next';

export default function ReservationSection() {
  const section = useHomepageSection('reservation');
  const { t } = useTranslation();

  if (section && !section.enabled) return null;

  const c = section?.content ?? {};
  const image = getSingleMedia(section, 'image');

  const title = c.title || t('homepage.reservation_section.title');
  const subtitle = c.subtitle || t('homepage.reservation_section.subtitle');
  const note = c.note || t('homepage.reservation_section.note');
  const ctaLabel = c.cta_label || t('homepage.reservation_section.submit');
  const overlayText = c.overlay_text || t('homepage.reservation_section.overlay_title');
  const overlaySubtitle = c.overlay_subtitle || t('homepage.reservation_section.overlay_subtitle');

  return (
    <section className="reservation-section relative py-20 px-6 bg-background">
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <div className="space-y-8">
            <div className="text-center lg:text-left">
              <h2 className="text-4xl md:text-5xl lg:text-6xl font-light text-white/20 uppercase font-cormorant-semibold tracking-widest mb-4">
                {title}
              </h2>
              <h3 className="text-2xl md:text-3xl font-light text-white uppercase tracking-wide mb-8">
                {subtitle}
              </h3>
              <p className="text-sm text-white/60 mb-8">{note}</p>
            </div>
            <GloriaFoodButton
              cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
              ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
              type="reservation"
              className="!bg-transparent !border-2 !border-primary !font-medium !uppercase !tracking-wider !bg-primary !text-white !transition-all !duration-300 !px-12 !py-4 !text-sm disabled:!opacity-50 disabled:!cursor-not-allowed"
            >
              {ctaLabel}
            </GloriaFoodButton>
          </div>

          <div className="relative order-1 lg:order-2">
            <div className="relative h-[600px] lg:h-[700px]">
              <MediaImage
                media={image}
                fallbackSrc="/image8.jpg"
                fallbackAlt="Vietnamese dishes and dining experience"
                style={{ borderRadius: '50% 0 0 0' }}
                className="absolute inset-0 w-full h-full object-cover"
                sizes="(min-width:1024px) 50vw, 100vw"
              />
              <div
                className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"
                style={{ borderRadius: '50% 0 0 0' }}
              />
              <div className="absolute bottom-0 right-0 -rotate-6 max-w-xs">
                <p className="text-primary font-delafield-xl" style={{ whiteSpace: 'normal' }}>
                  {overlayText} {overlaySubtitle}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
