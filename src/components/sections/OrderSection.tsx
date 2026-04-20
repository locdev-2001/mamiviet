import { GloriaFoodButton } from '@/components/GloriaFoodButton';
import MediaImage from '@/components/MediaImage';
import { getSingleMedia, useHomepageSection } from '@/lib/contexts/AppContentContext';
import { useTranslation } from 'react-i18next';

export default function OrderSection() {
  const section = useHomepageSection('order');
  const { t } = useTranslation();

  if (section && !section.enabled) return null;

  const c = section?.content ?? {};
  const left = getSingleMedia(section, 'left');
  const right = getSingleMedia(section, 'right');

  const title = c.title || t('homepage.order_section.title');
  const takeaway = c.takeaway || t('homepage.order_section.takeaway');
  const delivery = c.delivery || t('homepage.order_section.delivery');
  const reservation = c.reservation || t('homepage.order_section.reservation');
  const freeDelivery = c.free_delivery || t('homepage.order_section.free_delivery');
  const ctaLabel = c.cta_label || t('homepage.order_section.order_button');

  return (
    <section className="py-20 px-6 bg-background">
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-16 items-center">
          <div id="left-image" className="relative">
            <div
              className="w-64 h-64 md:w-80 md:h-80 lg:w-96 lg:h-96 mx-auto overflow-hidden shadow-2xl"
              style={{ borderRadius: '50% 50% 0 0' }}
            >
              <MediaImage
                media={left}
                fallbackSrc="/image5.jpg"
                fallbackAlt="Vietnamese noodles dish"
                className="w-full h-full object-cover object-center"
                sizes="(min-width:1024px) 33vw, 100vw"
              />
            </div>
          </div>

          <div className="text-center px-4">
            <h2 className="text-lg md:text-xl lg:text-2xl font-jost text-white mb-6 uppercase tracking-wide">
              {title}
            </h2>
            <div className="space-y-4 text-white/80 mb-8">
              <p className="text-base">{takeaway}</p>
              <p className="text-base">{delivery}</p>
              <p className="text-base">{reservation}</p>
              <p className="text-sm text-white/60">{freeDelivery}</p>
            </div>
            <GloriaFoodButton
              cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
              ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
              type="order"
              className="!bg-primary !hover:bg-primary/90 !text-white !font-source-semibold !text-sm !tracking-wider !uppercase !px-8 !py-4 !transition-colors"
            >
              {ctaLabel}
            </GloriaFoodButton>
          </div>

          <div id="right-image" className="relative">
            <div
              className="w-64 h-64 md:w-80 md:h-80 lg:w-96 lg:h-96 mx-auto overflow-hidden shadow-2xl"
              style={{ borderRadius: '50% 0 50% 0' }}
            >
              <MediaImage
                media={right}
                fallbackSrc="/image6.jpg"
                fallbackAlt="Vietnamese seafood dish"
                className="w-full h-full object-cover object-center"
                sizes="(min-width:1024px) 33vw, 100vw"
              />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
