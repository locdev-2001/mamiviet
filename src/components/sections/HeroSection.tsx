import MediaImage from '@/components/MediaImage';
import { getSingleMedia, useHomepageSection } from '@/lib/contexts/AppContentContext';
import { useTranslation } from 'react-i18next';

export default function HeroSection() {
  const section = useHomepageSection('hero');
  const { t } = useTranslation();

  if (section && !section.enabled) return null;

  const title = section?.content?.title || t('homepage.hero_title');
  const bg = getSingleMedia(section, 'bg');

  return (
    <section className="relative w-full min-h-[90vh] flex items-center justify-center overflow-hidden">
      <MediaImage
        media={bg}
        fallbackSrc="/primaryRestaurant.jpg"
        fallbackAlt="Restaurant interior"
        className="absolute inset-0 w-full h-full object-cover opacity-90 z-0"
        priority
        sizes="100vw"
      />
      <div className="absolute inset-0 bg-gradient-to-r from-black/20 via-black/10 to-black/20 z-10" />
      <div className="relative z-20 w-full h-full flex items-center justify-center">
        <div className="text-center space-y-6">
          <h1 className="text-[32px] md:text-[40px] text-wrap text-white leading-tight tracking-tight">
            {title}
          </h1>
        </div>
      </div>
    </section>
  );
}
