import { Footer } from "@/components/Footer";
import { GloriaFoodButton } from "@/components/GloriaFoodButton";
import { Header } from "@/components/Header";
import { ParallaxImage } from "@/components/ParallaxImage";
import Loading from "@/components/ui/loading";
import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import React, { useEffect, useRef, useState } from "react";
import { useTranslation } from "react-i18next";
import { useLocation, useNavigate } from "react-router-dom";
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

const Index = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { t } = useTranslation();
  const [loading, setLoading] = useState(true);
  const contactRef = useRef(null);

  const handleReadMoreClick = () => {
    navigate('/bilder');
  };


    // const scrollToRef = useCallback((target: string) => {
    //   const refs: Record<string, React.RefObject<HTMLElement>> = {
    //     contact: contactRef,
    //   };

    //   const sectionRef = refs[target];
    //   sectionRef?.current?.scrollIntoView({ behavior: "smooth" });
    // },[])

    const scrollToRef = (target:string ) => {
      const refs: Record<string, React.RefObject<HTMLElement>> = {
        contact: contactRef,
      };

      const sectionRef = refs[target];
      sectionRef?.current?.scrollIntoView({ behavior: "smooth" });
    }
  useEffect(() => {
    if (!loading) {
      gsap.registerPlugin(ScrollTrigger);
      
      // Animate left image
      gsap.fromTo("#left-image", 
        { 
          opacity: 0, 
          x: -100 
        },
        {
          opacity: 1,
          x: 0,
          duration: 0.8,
          ease: "power2.out",
          scrollTrigger: {
            trigger: "#left-image",
            start: "top 80%",
            end: "bottom 20%",
            once: true
          }
        }
      );

      // Animate right image with delay
      gsap.fromTo("#right-image", 
        { 
          opacity: 0, 
          x: 100 
        },
        {
          opacity: 1,
          x: 0,
          duration: 0.8,
          delay: 0.2,
          ease: "power2.out",
          scrollTrigger: {
            trigger: "#right-image",
            start: "top 80%",
            end: "bottom 20%",
            once: true
          }
        }
      );

    }
  }, [loading]);
  useEffect(() => {
    const timer = setTimeout(() => setLoading(false), 1200);
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    if (location?.state?.scrollTo) {
      scrollToRef(location.state.scrollTo)
      window.history.replaceState({}, document.title);
    }
  }, [location.state,scrollToRef]);

  if (loading) return <div className="min-h-screen flex items-center justify-center bg-background"><Loading /></div>;
  const surveyUrl = import.meta.env.VITE_EMOLYZER_SURVEY_URL;
  return (
    <div className="min-h-screen bg-background text-white flex flex-col">
      <Header scrollToRef={scrollToRef} />
      <main className="flex-1 mt-16">
        {/* Hero Banner Section */}
        <section className="relative w-full min-h-[90vh] flex items-center justify-center overflow-hidden">
          {/* Background image + very light overlay */}
          <img
            src="/primaryRestaurant.jpg"
            alt="Restaurant interior"
            className="absolute inset-0 w-full h-full object-cover opacity-90 z-0"
          />
          <div className="absolute inset-0 bg-gradient-to-r from-black/20 via-black/10 to-black/20 z-10" />
          
          {/* Centered Hero text */}
          <div className="relative z-20 w-full h-full flex items-center justify-center">
              <div className="text-center space-y-6">
                  <h1 className="text-[32px] md:text-[40px] text-wrap text-white leading-tight tracking-tight">
                      {t('homepage.hero_title')}
                  </h1>
              </div>
          </div>

        </section>
        {/*CUISINE SECTION */}
        <section className="relative">
          {/* Cuisine Welcome Section */}
          <section className="py-20 px-6">
            <div className="max-w-7xl mx-auto">
              {/* Header with logo and tagline */}
              <div className="text-center mb-20">
                <div className="flex items-center justify-center mb-6">
                  <img src="/logo.png" alt="Mamiviet Logo" className="h-20 w-auto" />
                </div>
                <h2 className="text-[28px] font-source-medium tracking-[0.3em] text-white mb-4 uppercase">
                  {t('homepage.welcome_section.brand_name')}
                </h2>
                <p className="font-delafield-xl mt-[50px] text-primary">
                  {t('homepage.welcome_section.tagline')}
                </p>
              </div>

              {/* Main content grid */}
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
                {/* Left content */}
                <div className="space-y-8">
                  {/* Large CUISINE text */}
                  <div className="relative">
                    <h3 className="text-4xl lg:text-5xl xl:text-[60px] font-cormorant-light text-white/10 leading-none tracking-wider text-left">
                      {t('homepage.welcome_section.cuisine_title')}
                    </h3>
                    <div className="mt-8">
                      <h4 className="text-2xl  md:text-3xl lg:text-5xl font-jost text-white mb-6 tracking-wide">
                        {t('homepage.welcome_section.welcome_title')}
                      </h4>
                      <p className="text-white/80 font-inter text-base leading-relaxed mb-8">
                        {t('homepage.welcome_section.welcome_text')}
                      </p>
                    <GloriaFoodButton
                        cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
                        ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
                        type="order"
                        className="!bg-primary !hover:bg-primary/90 !text-white !font-source-semibold !text-sm !tracking-wider !uppercase !px-8 !py-4 !transition-colors"
                    >
                        {t('homepage.welcome_section.order_online')}
                    </GloriaFoodButton>
                    </div>
                  </div>
                </div>

                {/* Right images */}
                <div className="relative z-20">
                  <div className="relative">
                    {/* Large image with parallax */}
                    <ParallaxImage
                        src="/image1.jpg"
                        alt="Asian cuisine dishes"
                        className="aspect-square overflow-hidden z-20"
                        direction="down"
                        intensity={20}
                    />

                    {/* Small image overlay with opposite parallax */}
                    <ParallaxImage
                        src="/image2.jpg"
                        alt="Vietnamese food"
                        className="absolute top-1/3 -left-8 w-36 h-48 md:w-40 md:h-52 z-30"
                        direction="up"
                        intensity={120}
                    />
                  </div>

                  {/* Decorative elements */}
                  <div className="absolute -top-4 -right-4 w-full h-full -z-10"></div>
                </div>
              </div>
            </div>
          </section>

          {/* Second Cuisine Section */}
          <section className="py-20 px-6">
            <div className="max-w-7xl mx-auto">
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
                {/* Left images */}
                <div className="relative order-2 lg:order-1 z-20">
                  <div className="relative">
                    {/* Large image with parallax */}
                    <ParallaxImage
                        src="/image3.jpg"
                        alt="Vietnamese traditional dishes"
                        className="aspect-square overflow-hidden z-20"
                        direction="down"
                        intensity={20}
                    />

                    {/* Small image overlay with opposite parallax */}
                    <ParallaxImage
                        src="/image4.jpg"
                        alt={t('homepage.gallery_section.image_alt')}
                        className="absolute top-1/3 -right-8 w-36 h-48 md:w-40 md:h-52 z-30"
                        direction="up"
                        intensity={120}
                    />
                  </div>

                  {/* Decorative elements */}
                  <div className="absolute -top-4 -left-4 w-full h-full -z-10"></div>
                </div>

                {/* Right content */}
                <div className="space-y-8 order-1 lg:order-2">
                  {/* Large CUISINE text */}
                  <div className="relative">
                    <h3 className="text-4xl lg:text-5xl xl:text-[60px] font-cormorant-light text-white/10 leading-none tracking-wider text-left">
                      {t('homepage.welcome_section.cuisine_title')}
                    </h3>
                    <div className="mt-8">
                      <h4 className="text-2xl md:text-3xl lg:text-5xl font-jost text-white mb-6 tracking-wide">
                        {t('homepage.welcome_section.second_section.title')}
                      </h4>
                      <p className="text-white/80 font-inter text-base leading-relaxed mb-8">
                        {t('homepage.welcome_section.second_section.text')}
                      </p>
                      <button
                        onClick={handleReadMoreClick}
                        className="bg-primary hover:bg-primary/90 text-white font-source-semibold text-sm tracking-wider uppercase px-8 py-4 transition-colors"
                      >
                        {t('homepage.welcome_section.second_section.read_more')}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          {/* Connecting Vertical Lines */}
          <div className="absolute inset-0 pointer-events-none z-10">
            <div className="relative w-full h-full flex justify-center">
              <div className="flex justify-around container w-full">
                {/* Left vertical line */}
                <div className="w-px h-full bg-gradient-to-b from-transparent via-white/20 to-transparent animated-glow-line"></div>
                {/* Right vertical line */}
                <div className="w-px h-full bg-gradient-to-b from-transparent via-white/20 to-transparent animated-glow-line"></div>
              </div>
            </div>
          </div>
        </section>

        {/* Horizontal Food Section */}
        <section className="py-20 px-6 bg-background">
          <div className="max-w-7xl mx-auto">
            {/* Content Layout: Image - Content - Image */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-16 items-center">
              
              {/* Left Image */}
              <div 
                id="left-image"
                className="relative"
              >
                <div className="w-64 h-64 md:w-80 md:h-80 lg:w-96 lg:h-96 mx-auto overflow-hidden shadow-2xl" style={{ borderRadius: '50% 50% 0 0' }}>
                  <img 
                    src="/image5.jpg"
                    alt="Vietnamese noodles dish"
                    className="w-full h-full object-cover object-center"
                  />
                </div>
              </div>

              {/* Center Content */}
              <div className="text-center px-4">
                <h2 className="text-lg md:text-xl lg:text-2xl font-jost text-white mb-6 uppercase tracking-wide">
                  {t('homepage.order_section.title')}
                </h2>
                
                <div className="space-y-4 text-white/80 mb-8">
                  <p className="text-base">
                    {t('homepage.order_section.takeaway')}
                  </p>
                  <p className="text-base">
                    {t('homepage.order_section.delivery')}
                  </p>
                  <p className="text-base">
                    {t('homepage.order_section.reservation')}
                  </p>
                  <p className="text-sm text-white/60">
                    {t('homepage.order_section.free_delivery')}
                  </p>
                </div>
                  <GloriaFoodButton
                      cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
                      ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
                      type="order"
                      className="!bg-primary !hover:bg-primary/90 !text-white !font-source-semibold !text-sm !tracking-wider !uppercase !px-8 !py-4 !transition-colors"
                  >
                      {t('homepage.order_section.order_button')}
                  </GloriaFoodButton>
              </div>

              {/* Right Image */}
              <div 
                id="right-image"
                className="relative"
              >
                <div className="w-64 h-64 md:w-80 md:h-80 lg:w-96 lg:h-96 mx-auto overflow-hidden shadow-2xl" style={{ borderRadius: '50% 0 50% 0' }}>
                  <img 
                    src="/image6.jpg"
                    alt="Vietnamese seafood dish"
                    className="w-full h-full object-cover object-center"
                  />
                </div>
              </div>

            </div>
          </div>
        </section>

        {/* Reservation Section */}
        <section className="reservation-section relative py-20 px-6 bg-background">
          <div className="max-w-7xl mx-auto">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
              
              {/* Left Form */}
              <div className="space-y-8">
                <div className="text-center lg:text-left">
                  <h2 className="text-4xl md:text-5xl lg:text-6xl font-light text-white/20 uppercase font-cormorant-semibold tracking-widest mb-4">
                    {t('homepage.reservation_section.title')}
                  </h2>
                  <h3 className="text-2xl md:text-3xl font-light  text-white uppercase tracking-wide mb-8">
                    {t('homepage.reservation_section.subtitle')}
                  </h3>
                  <p className="text-sm text-white/60 mb-8">
                    {t('homepage.reservation_section.note')}
                  </p>
                </div>

                  <GloriaFoodButton
                      cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
                      ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
                      type="reservation"
                      className="!bg-transparent !border-2 !border-primary !font-medium !uppercase !tracking-wider !bg-primary !text-white !transition-all !duration-300 !px-12 !py-4 !text-sm disabled:!opacity-50 disabled:!cursor-not-allowed"
                  >
                      {t('homepage.reservation_section.submit')}
                  </GloriaFoodButton>
              </div>

              {/* Right Image */}
              <div className="relative order-1 lg:order-2">
                <div className="relative h-[600px] lg:h-[700px]">
                  <img 
                    src="/image8.jpg"
                    alt="Vietnamese dishes and dining experience"
                    style={{ borderRadius: '50% 0 0 0' }}
                    className="absolute inset-0 w-full h-full object-cover"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" style={{ borderRadius: '50% 0 0 0' }} />
                  
                  {/* Overlay text */}
                  <div className="absolute bottom-0 right-0 -rotate-6 max-w-xs">
                    <p className="text-primary font-delafield-xl" style={{ whiteSpace: 'normal' }}>
                      {t('homepage.reservation_section.overlay_title')} {t('homepage.reservation_section.overlay_subtitle')}
                    </p>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </section>

          {/* Contact Section */}
        <section className="reservation-section relative py-20 px-6 bg-background" ref={contactRef}>
          <div className="max-w-7xl mx-auto">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                <div className="relative">
                  <div className="relative h-[600px] lg:h-[700px]">
                    <img 
                      src="/image9.jpg"
                      alt="Vietnamese dishes and dining experience"
                      style={{ borderRadius: '0 50% 0 0' }}
                      className="absolute inset-0 w-full h-full object-cover"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent" style={{ borderRadius: '0 50% 0 0' }} />
                    
                    {/* Overlay text */}
                    <div className="absolute bottom-0 right-0 -rotate-6 max-w-xs">
                      <p className="text-primary font-delafield-xl" style={{ whiteSpace: 'normal' }}>
                        {t('homepage.reservation_section.overlay_title')} {t('homepage.reservation_section.overlay_subtitle')}
                      </p>
                    </div>
                  </div>
              </div>
              {/* Contact Info */}
              <div className="space-y-8">
                <div className="text-center lg:text-left">
                  <h2 className="text-4xl md:text-5xl lg:text-6xl font-light text-white/20 uppercase font-cormorant-semibold tracking-widest mb-4">
                    {t('homepage.contact_section.title')}
                  </h2>
                </div>

                <div className="space-y-6">
                  {/* Restaurant Name */}
                  <div>
                    <h3 className="text-2xl md:text-3xl font-jost text-white mb-8">
                      {t('homepage.contact_section.restaurant_name')}
                    </h3>
                  </div>

                  {/* Address */}
                  <div className="flex items-start gap-4">
                    <svg className="w-6 h-6 text-primary flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clipRule="evenodd" />
                    </svg>
                    <div>
                      <p className="text-white text-lg">{t('homepage.contact_section.address')}</p>
                    </div>
                  </div>

                  {/* Phone */}
                  <div className="flex items-center gap-4">
                    <svg className="w-6 h-6 text-primary flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                    <a href="tel:+493414925244" className="text-white text-lg hover:text-primary transition-colors">
                      {t('homepage.contact_section.phone')}
                    </a>
                  </div>

                  {/* Email */}
                  <div className="flex items-center gap-4">
                    <svg className="w-6 h-6 text-primary flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                      <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                    </svg>
                    <a href="mailto:info@restaurant-mamiviet.com" className="text-white text-lg hover:text-primary transition-colors">
                      {t('homepage.contact_section.email')}
                    </a>
                  </div>

                  {/* Instagram */}
                  <div className="flex items-center gap-4">
                    <svg viewBox="0 0 100 50" className="w-6 h-6 text-primary flex-shrink-0" fill="currentColor">
                      <text x="50" y="30" fontFamily="Arial, sans-serif" fontSize="70" textAnchor="middle" dominantBaseline="middle" fontWeight="bold">IG</text>
                    </svg>
                    <a href={t('homepage.contact_section.instagram')} target="_blank" rel="noopener noreferrer" className="text-white text-lg hover:text-primary transition-colors">
                      {t('homepage.contact_section.ig_name')}
                    </a>
                  </div>

                  {/* Google Map */}
                  <div className="mt-8">
                      <div className="relative w-full h-[300px] md:h-[400px] rounded-lg overflow-hidden shadow-2xl">
                          <iframe
                              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2492.5538579536387!2d12.32732896718416!3d51.33772531772187!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47a6f79bfe53d701%3A0x89dcff2537a6fce5!2sMami%20Viet%20-%20SUSHI%20-%20Asian%20Cuisine!5e0!3m2!1svi!2s!4v1761356364892!5m2!1svi!2s"
                              width="100%" height="100%" style={{border:0}} allowFullScreen={true} loading="lazy"
                              referrerPolicy="no-referrer-when-downgrade"></iframe>
                      </div>
                  </div>
                </div>
              </div>


            </div>
          </div>
        </section>

          {/* Image Slider Section */}
          <section className="py-20 px-6 bg-background">
              <div className="max-w-7xl mx-auto">
                  {/* Section Title */}
                  <div className="text-center mb-16">
                      <h2 className="text-4xl lg:text-5xl xl:text-[60px] font-cormorant-light text-white/10 leading-none tracking-wider uppercase mb-8">
                {t('homepage.gallery_section.title')}
              </h2>
              <p className="text-lg text-white/60 font-inter">
                {t('homepage.gallery_section.subtitle')}
              </p>
            </div>

            {/* Swiper Image Slider */}
            <Swiper
              modules={[Navigation, Pagination, Autoplay]}
              spaceBetween={24}
              slidesPerView={5}
              breakpoints={{
                320: {
                  slidesPerView: 2,
                  spaceBetween: 16,
                },
                768: {
                  slidesPerView: 3,
                  spaceBetween: 20,
                },
                1024: {
                  slidesPerView: 4,
                  spaceBetween: 24,
                },
                1280: {
                  slidesPerView: 5,
                  spaceBetween: 24,
                }
              }}
              navigation={false}
              pagination={{
                el: '.swiper-pagination-custom',
                clickable: true,
              }}
              autoplay={{
                delay: 5000,
                disableOnInteraction: false,
              }}
              loop={true}
              className="gallery-swiper"
            >
              {/* Image 1 - Rectangle */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '50% 50% 0 0 ' }}>
                  <img
                    src="/image1.jpg"
                    alt={`${t('homepage.gallery_section.image_alt')} 1`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>

              {/* Image 2 - Top corners rounded */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '20px 20px 0 0' }}>
                  <img
                    src="/image2.jpg"
                    alt={`${t('homepage.gallery_section.image_alt')} 2`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>

              {/* Image 3 - Rounded square */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '30px' }}>
                  <img
                    src="/image3.jpg"
                    alt={`${t('homepage.gallery_section.image_alt')} 3`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>

              {/* Image 4 - Top left rounded */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '40px 0 0 0' }}>
                  <img
                    src="/image4.jpg"
                    alt={`${t('homepage.gallery_section.image_alt')} 4`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>

              {/* Image 5 - All corners rounded different */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '30px 10px 30px 10px' }}>
                  <img
                    src="/image5.jpg"
                    alt={`${t('homepage.gallery_section.image_alt')} 5`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>

              {/* Image 6 - Rectangle */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '0' }}>
                  <img
                    src="/image6.jpg"
                    alt={`${t('homepage.gallery_section.image_alt')} 6`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>

              {/* Image 7 - Top corners rounded */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '20px 20px 0 0' }}>
                  <img
                    src="/image7.jpg"
                    alt={`${t('homepage.gallery_section.image_alt')} 7`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>

              {/* Image 8 - Rounded square */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '30px' }}>
                  <img
                    src="/image8.jpg"
                    alt={`${t('homepage.gallery_section.image_alt')} 8`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>

              {/* Image 9 - Top left rounded */}
              <SwiperSlide>
                <div className="aspect-[3/4] overflow-hidden shadow-lg" style={{ borderRadius: '40px 0 0 0' }}>
                  <img 
                    src="/image9.jpg" 
                    alt={`${t('homepage.gallery_section.image_alt')} 9`}
                    className="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                  />
                </div>
              </SwiperSlide>
            </Swiper>

            {/* Pagination Only */}
            <div className="flex justify-center mt-8">
              <div className="swiper-pagination-custom flex justify-center gap-2"></div>
            </div>
          </div>
        </section>

        {/* <section className="py-16 px-4">
          <div className="max-w-md mx-auto">
            <div className="bg-black/80 rounded-lg shadow-lg border border-neutral-800 p-8">
              <a href={surveyUrl} target="_blank" rel="noopener noreferrer" className="flex flex-col items-center gap-4 group">
                <span className="flex items-center justify-center w-64 h-12 rounded-[10px] bg-white shadow-lg transition-transform group-hover:scale-105 relative">
                  <img src="/logo-emolyzer.webp" alt="Emolyzer Logo" style={{ maxWidth: '80%', maxHeight: '70%', objectFit: 'contain', display: 'block' }} />
                  <img src="/hand-click.png" alt="Hand Click" className="absolute -bottom-6 right-2 w-16 h-16 animate-bounce-hand pointer-events-none select-none" />
                </span>
                <div className="mt-2 text-base font-semibold text-primary text-center">{t('homepage.emolyzer_survey_title')}</div>
                <div className="text-sm text-neutral-200 text-center">{t('homepage.emolyzer_survey_desc')}</div>
                <div className="text-xs text-neutral-400 text-center mt-2">{t('homepage.emolyzer_survey_or_qr', 'Oder scannen Sie den QR-Code unten')}</div>
                <img src="/qr-emolyzer.jpg" alt="Emolyzer QR" className="w-32 h-32 object-contain mx-auto mt-2 rounded" />
              </a>
            </div>
          </div>
        </section> */}

        {/* Intro Section */}
        <section className="max-w-3xl mx-auto px-4 py-12 text-center">
          <h2 className="text-2xl md:text-3xl font-bold mb-4">{t('homepage.intro_title')}</h2>
          <p className="text-base md:text-lg text-neutral-300 mb-2">
            {t('homepage.intro_text1')}
          </p>
          <p className="text-base md:text-lg text-neutral-400">
            {t('homepage.intro_text2')}
          </p>
        </section>
      </main>
      <Footer />
    </div>
  );
};

export default Index;