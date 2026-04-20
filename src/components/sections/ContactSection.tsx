import MediaImage from '@/components/MediaImage';
import { getSingleMedia, useHomepageSection } from '@/lib/contexts/AppContentContext';
import { forwardRef } from 'react';
import { useTranslation } from 'react-i18next';

const ContactSection = forwardRef<HTMLElement>((_, ref) => {
  const section = useHomepageSection('contact');
  const { t } = useTranslation();

  if (section && !section.enabled) return null;

  const c = section?.content ?? {};
  const data = section?.data ?? null;
  const image = getSingleMedia(section, 'image');

  const title = c.title || t('homepage.contact_section.title');
  const restaurantName = c.restaurant_name || t('homepage.contact_section.restaurant_name');
  const address = c.address || t('homepage.contact_section.address');
  const phone = c.phone || t('homepage.contact_section.phone');
  const email = c.email || t('homepage.contact_section.email');
  const igLabel = c.instagram_label || t('homepage.contact_section.ig_name');
  const igUrl = data?.instagram_url || t('homepage.contact_section.instagram');
  const mapEmbed = data?.map_embed;
  const overlayText = t('homepage.reservation_section.overlay_title');
  const overlaySubtitle = t('homepage.reservation_section.overlay_subtitle');

  const phoneHref = `tel:${phone.replace(/\s+/g, '')}`;
  const emailHref = `mailto:${email}`;

  return (
    <section className="reservation-section relative py-20 px-6 bg-background" ref={ref}>
      <div className="max-w-7xl mx-auto">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
          <div className="relative">
            <div className="relative h-[600px] lg:h-[700px]">
              <MediaImage
                media={image}
                fallbackSrc="/image9.jpg"
                fallbackAlt="Vietnamese dishes and dining experience"
                style={{ borderRadius: '0 50% 0 0' }}
                className="absolute inset-0 w-full h-full object-cover"
                sizes="(min-width:1024px) 50vw, 100vw"
              />
              <div
                className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"
                style={{ borderRadius: '0 50% 0 0' }}
              />
              <div className="absolute bottom-0 right-0 -rotate-6 max-w-xs">
                <p className="text-primary font-delafield-xl" style={{ whiteSpace: 'normal' }}>
                  {overlayText} {overlaySubtitle}
                </p>
              </div>
            </div>
          </div>

          <div className="space-y-8">
            <div className="text-center lg:text-left">
              <h2 className="text-4xl md:text-5xl lg:text-6xl font-light text-white/20 uppercase font-cormorant-semibold tracking-widest mb-4">
                {title}
              </h2>
            </div>
            <div className="space-y-6">
              <div>
                <h3 className="text-2xl md:text-3xl font-jost text-white mb-8">{restaurantName}</h3>
              </div>
              <div className="flex items-start gap-4">
                <svg className="w-6 h-6 text-primary flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                </svg>
                <div>
                  <p className="text-white text-lg">{address}</p>
                </div>
              </div>
              <div className="flex items-center gap-4">
                <svg className="w-6 h-6 text-primary flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                </svg>
                <a href={phoneHref} className="text-white text-lg hover:text-primary transition-colors">
                  {phone}
                </a>
              </div>
              <div className="flex items-center gap-4">
                <svg className="w-6 h-6 text-primary flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
                <a href={emailHref} className="text-white text-lg hover:text-primary transition-colors">
                  {email}
                </a>
              </div>
              <div className="flex items-center gap-4">
                <svg viewBox="0 0 100 50" className="w-6 h-6 text-primary flex-shrink-0" fill="currentColor">
                  <text x="50" y="30" fontFamily="Arial, sans-serif" fontSize="70" textAnchor="middle" dominantBaseline="middle" fontWeight="bold">IG</text>
                </svg>
                <a href={igUrl} target="_blank" rel="noopener noreferrer" className="text-white text-lg hover:text-primary transition-colors">
                  {igLabel}
                </a>
              </div>
              {mapEmbed && (
                <div className="mt-8">
                  <div className="relative w-full h-[300px] md:h-[400px] rounded-lg overflow-hidden shadow-2xl">
                    <iframe
                      src={mapEmbed}
                      width="100%"
                      height="100%"
                      style={{ border: 0 }}
                      allowFullScreen
                      loading="lazy"
                      referrerPolicy="no-referrer-when-downgrade"
                      sandbox="allow-scripts allow-same-origin allow-popups"
                    />
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
});

ContactSection.displayName = 'ContactSection';
export default ContactSection;
