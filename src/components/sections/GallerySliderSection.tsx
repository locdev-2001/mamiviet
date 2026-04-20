import MediaImage from '@/components/MediaImage';
import { getMediaList, useHomepageSection } from '@/lib/contexts/AppContentContext';
import { useTranslation } from 'react-i18next';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

const FALLBACK_IMAGES = [
  { src: '/image1.jpg', radius: '50% 50% 0 0' },
  { src: '/image2.jpg', radius: '20px 20px 0 0' },
  { src: '/image3.jpg', radius: '30px' },
  { src: '/image4.jpg', radius: '40px 0 0 0' },
  { src: '/image5.jpg', radius: '30px 10px 30px 10px' },
  { src: '/image6.jpg', radius: '0' },
  { src: '/image7.jpg', radius: '20px 20px 0 0' },
  { src: '/image8.jpg', radius: '30px' },
  { src: '/image9.jpg', radius: '40px 0 0 0' },
];

const RADII = FALLBACK_IMAGES.map((item) => item.radius);

export default function GallerySliderSection() {
  const section = useHomepageSection('gallery_slider');
  const { t } = useTranslation();

  if (section && !section.enabled) return null;

  const title = section?.content?.title || t('homepage.gallery_section.title');
  const subtitle = section?.content?.subtitle || t('homepage.gallery_section.subtitle');
  const imageAlt = t('homepage.gallery_section.image_alt');

  const images = getMediaList(section, 'images');
  const slides = images.length >= 5
    ? images.map((media, idx) => ({
        media,
        src: media.src,
        radius: RADII[idx % RADII.length],
      }))
    : FALLBACK_IMAGES.map((item) => ({ media: null, src: item.src, radius: item.radius }));

  return (
    <section className="py-20 px-6 bg-background">
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-16">
          <h2 className="text-4xl lg:text-5xl xl:text-[60px] font-cormorant-light text-white/10 leading-none tracking-wider uppercase mb-8">
            {title}
          </h2>
          <p className="text-lg text-white/60 font-inter">{subtitle}</p>
        </div>

        <Swiper
          modules={[Navigation, Pagination, Autoplay]}
          spaceBetween={24}
          slidesPerView={5}
          breakpoints={{
            320: { slidesPerView: 2, spaceBetween: 16 },
            768: { slidesPerView: 3, spaceBetween: 20 },
            1024: { slidesPerView: 4, spaceBetween: 24 },
            1280: { slidesPerView: 5, spaceBetween: 24 },
          }}
          navigation={false}
          pagination={{ el: '.swiper-pagination-custom', clickable: true }}
          autoplay={{ delay: 5000, disableOnInteraction: false }}
          loop
          className="gallery-swiper"
        >
          {slides.map((slide, idx) => (
            <SwiperSlide key={slide.src + idx}>
              <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: slide.radius }}>
                <MediaImage
                  media={slide.media}
                  fallbackSrc={slide.src}
                  fallbackAlt={`${imageAlt} ${idx + 1}`}
                  className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  sizes="(min-width:1280px) 20vw, (min-width:1024px) 25vw, (min-width:768px) 33vw, 50vw"
                />
              </div>
            </SwiperSlide>
          ))}
        </Swiper>

        <div className="flex justify-center mt-8">
          <div className="swiper-pagination-custom flex justify-center gap-2" />
        </div>
      </div>
    </section>
  );
}
