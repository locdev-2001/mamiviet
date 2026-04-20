import { useHomepageSection } from '@/lib/contexts/AppContentContext';
import { useTranslation } from 'react-i18next';

export default function IntroSection() {
  const section = useHomepageSection('intro');
  const { t } = useTranslation();

  if (section && !section.enabled) return null;

  const c = section?.content ?? {};
  const title = c.title || t('homepage.intro_title');
  const text1 = c.text1 || t('homepage.intro_text1');
  const text2 = c.text2 || t('homepage.intro_text2');

  return (
    <section className="max-w-3xl mx-auto px-4 py-12 text-center">
      <h2 className="text-2xl md:text-3xl font-bold mb-4">{title}</h2>
      <p className="text-base md:text-lg text-neutral-300 mb-2">{text1}</p>
      <p className="text-base md:text-lg text-neutral-400">{text2}</p>
    </section>
  );
}
