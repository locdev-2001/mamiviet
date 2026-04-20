import { GloriaFoodButton } from '@/components/GloriaFoodButton';
import MediaImage from '@/components/MediaImage';
import { ParallaxImage } from '@/components/ParallaxImage';
import { getSingleMedia, useHomepageSection } from '@/lib/contexts/AppContentContext';
import { useTranslation } from 'react-i18next';

export default function WelcomeSection() {
  const section = useHomepageSection('welcome');
  const { t } = useTranslation();

  if (section && !section.enabled) return null;

  const c = section?.content ?? {};
  const main = getSingleMedia(section, 'main');
  const overlay = getSingleMedia(section, 'overlay');

  const brandName = c.brand_name || t('homepage.welcome_section.brand_name');
  const tagline = c.tagline || t('homepage.welcome_section.tagline');
  const cuisineLabel = c.cuisine_label || t('homepage.welcome_section.cuisine_title');
  const title = c.title || t('homepage.welcome_section.welcome_title');
  const body = c.body || t('homepage.welcome_section.welcome_text');
  const ctaLabel = c.cta_label || t('homepage.welcome_section.order_online');

  return (
    <section className="py-20 px-6">
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-20">
          <div className="flex items-center justify-center mb-6">
            <img src="/logo.png" alt="Mamiviet Logo" className="h-20 w-auto" />
          </div>
          <h2 className="text-[28px] font-source-medium tracking-[0.3em] text-white mb-4 uppercase">
            {brandName}
          </h2>
          <p className="font-delafield-xl mt-[50px] text-primary">{tagline}</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
          <div className="space-y-8">
            <div className="relative">
              <h3 className="text-4xl lg:text-5xl xl:text-[60px] font-cormorant-light text-white/10 leading-none tracking-wider text-left">
                {cuisineLabel}
              </h3>
              <div className="mt-8">
                <h4 className="text-2xl md:text-3xl lg:text-5xl font-jost text-white mb-6 tracking-wide">
                  {title}
                </h4>
                <p className="text-white/80 font-inter text-base leading-relaxed mb-8">{body}</p>
                <GloriaFoodButton
                  cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
                  ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
                  type="order"
                  className="!bg-primary !hover:bg-primary/90 !text-white !font-source-semibold !text-sm !tracking-wider !uppercase !px-8 !py-4 !transition-colors"
                >
                  {ctaLabel}
                </GloriaFoodButton>
              </div>
            </div>
          </div>

          <div className="relative z-20">
            <div className="relative">
              {main ? (
                <MediaImage
                  media={main}
                  fallbackSrc="/image1.jpg"
                  fallbackAlt="Asian cuisine dishes"
                  className="aspect-square overflow-hidden w-full h-full object-cover z-20"
                  sizes="(min-width:1024px) 50vw, 100vw"
                />
              ) : (
                <ParallaxImage
                  src="/image1.jpg"
                  alt="Asian cuisine dishes"
                  className="aspect-square overflow-hidden z-20"
                  direction="down"
                  intensity={20}
                />
              )}
              {overlay ? (
                <MediaImage
                  media={overlay}
                  fallbackSrc="/image2.jpg"
                  fallbackAlt="Vietnamese food"
                  className="absolute top-1/3 -left-8 w-36 h-48 md:w-40 md:h-52 object-cover z-30"
                  sizes="(min-width:768px) 160px, 144px"
                />
              ) : (
                <ParallaxImage
                  src="/image2.jpg"
                  alt="Vietnamese food"
                  className="absolute top-1/3 -left-8 w-36 h-48 md:w-40 md:h-52 z-30"
                  direction="up"
                  intensity={120}
                />
              )}
            </div>
            <div className="absolute -top-4 -right-4 w-full h-full -z-10" />
          </div>
        </div>
      </div>
    </section>
  );
}
