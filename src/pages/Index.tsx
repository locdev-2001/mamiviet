import ContactSection from '@/components/sections/ContactSection';
import GallerySliderSection from '@/components/sections/GallerySliderSection';
import HeroSection from '@/components/sections/HeroSection';
import IntroSection from '@/components/sections/IntroSection';
import OrderSection from '@/components/sections/OrderSection';
import ReservationSection from '@/components/sections/ReservationSection';
import WelcomeSection from '@/components/sections/WelcomeSection';
import WelcomeSecondSection from '@/components/sections/WelcomeSecondSection';
import { Footer } from '@/components/Footer';
import { Header } from '@/components/Header';
import Loading from '@/components/ui/loading';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { useEffect, useRef, useState } from 'react';
import { useLocation } from 'react-router-dom';

const Index = () => {
  const location = useLocation();
  const [loading, setLoading] = useState(true);
  const contactRef = useRef<HTMLElement | null>(null);

  const scrollToRef = (target: string) => {
    const refs: Record<string, React.RefObject<HTMLElement>> = { contact: contactRef };
    refs[target]?.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    if (loading) return;
    gsap.registerPlugin(ScrollTrigger);
    gsap.fromTo('#left-image',
      { opacity: 0, x: -100 },
      { opacity: 1, x: 0, duration: 0.8, ease: 'power2.out',
        scrollTrigger: { trigger: '#left-image', start: 'top 80%', end: 'bottom 20%', once: true } });
    gsap.fromTo('#right-image',
      { opacity: 0, x: 100 },
      { opacity: 1, x: 0, duration: 0.8, delay: 0.2, ease: 'power2.out',
        scrollTrigger: { trigger: '#right-image', start: 'top 80%', end: 'bottom 20%', once: true } });
  }, [loading]);

  useEffect(() => {
    const timer = setTimeout(() => setLoading(false), 1200);
    return () => clearTimeout(timer);
  }, []);

  useEffect(() => {
    if (location?.state?.scrollTo) {
      scrollToRef(location.state.scrollTo);
      window.history.replaceState({}, document.title);
    }
  }, [location.state]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loading />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-background text-white flex flex-col">
      <Header scrollToRef={scrollToRef} />
      <main className="flex-1 mt-16">
        <HeroSection />
        <section className="relative">
          <WelcomeSection />
          <WelcomeSecondSection />
          <div className="absolute inset-0 pointer-events-none z-10">
            <div className="relative w-full h-full flex justify-center">
              <div className="flex justify-around container w-full">
                <div className="w-px h-full bg-gradient-to-b from-transparent via-white/20 to-transparent animated-glow-line" />
                <div className="w-px h-full bg-gradient-to-b from-transparent via-white/20 to-transparent animated-glow-line" />
              </div>
            </div>
          </div>
        </section>
        <OrderSection />
        <ReservationSection />
        <ContactSection ref={contactRef} />
        <GallerySliderSection />
        <IntroSection />
      </main>
      <Footer />
    </div>
  );
};

export default Index;
