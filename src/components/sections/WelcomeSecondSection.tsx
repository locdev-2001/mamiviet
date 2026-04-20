import MediaImage from '@/components/MediaImage';
import { ParallaxImage } from '@/components/ParallaxImage';
import { getSingleMedia, useHomepageSection } from '@/lib/contexts/AppContentContext';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';

export default function WelcomeSecondSection() {
  const section = useHomepageSection('welcome_second');
  const { t } = useTranslation();
  const navigate = useNavigate();

  if (section && !section.enabled) return null;

  const c = section?.content ?? {};
  const main = getSingleMedia(section, 'main');
  const overlay = getSingleMedia(section, 'overlay');

  const cuisineLabel = c.cuisine_label || t('homepage.welcome_section.cuisine_title');
  const title = c.title || t('homepage.welcome_section.second_section.title');
  const body = c.body || t('homepage.welcome_section.second_section.text');
  const ctaLabel = c.cta_label || t('homepage.welcome_section.second_section.read_more');

  return (
    <section className="py-20 px-6">
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
          <div className="relative order-2 lg:order-1 z-20">
            <div className="relative">
              {main ? (
                <MediaImage
                  media={main}
                  fallbackSrc="/image3.jpg"
                  fallbackAlt="Vietnamese traditional dishes"
                  className="aspect-square overflow-hidden w-full h-full object-cover z-20"
                  sizes="(min-width:1024px) 50vw, 100vw"
                />
              ) : (
                <ParallaxImage
                  src="/image3.jpg"
                  alt="Vietnamese traditional dishes"
                  className="aspect-square overflow-hidden z-20"
                  direction="down"
                  intensity={20}
                />
              )}
              {overlay ? (
                <MediaImage
                  media={overlay}
                  fallbackSrc="/image4.jpg"
                  fallbackAlt={t('homepage.gallery_section.image_alt')}
                  className="absolute top-1/3 -right-8 w-36 h-48 md:w-40 md:h-52 object-cover z-30"
                  sizes="(min-width:768px) 160px, 144px"
                />
              ) : (
                <ParallaxImage
                  src="/image4.jpg"
                  alt={t('homepage.gallery_section.image_alt')}
                  className="absolute top-1/3 -right-8 w-36 h-48 md:w-40 md:h-52 z-30"
                  direction="up"
                  intensity={120}
                />
              )}
            </div>
            <div className="absolute -top-4 -left-4 w-full h-full -z-10" />
          </div>

          <div className="space-y-8 order-1 lg:order-2">
            <div className="relative">
              <h3 className="text-4xl lg:text-5xl xl:text-[60px] font-cormorant-light text-white/10 leading-none tracking-wider text-left">
                {cuisineLabel}
              </h3>
              <div className="mt-8">
                <h4 className="text-2xl md:text-3xl lg:text-5xl font-jost text-white mb-6 tracking-wide">
                  {title}
                </h4>
                <p className="text-white/80 font-inter text-base leading-relaxed mb-8">{body}</p>
                <button
                  onClick={() => navigate('/bilder')}
                  className="bg-primary hover:bg-primary/90 text-white font-source-semibold text-sm tracking-wider uppercase px-8 py-4 transition-colors"
                >
                  {ctaLabel}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
