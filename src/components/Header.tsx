import { useState } from "react";
import { X } from "lucide-react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { GloriaFoodButton } from "@/components/GloriaFoodButton";
import '../styles/fidalgo.css';

const NAV_KEYS = [
  { key: "home", href: "/" },
  { key: "menu", href: "/menu" },
  { key: "bilder", href: "/bilder" },
  { key: "kontakt", scroll: true, target: 'contact' },
];

export function Header({ scrollToRef }) {
  const [open, setOpen] = useState(false);
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const location = useLocation();

  const onCLickScroll = (target) => {
    if (location.pathname === "/") {
      scrollToRef(target);
    } else {
      navigate("/", { state: { scrollTo: target } });
    }
  };

  return (
    <header className="fixed top-0 left-0 w-full z-50 text-white" style={{ backgroundColor: '#2f2721' }}>
      {/* Mobile header */}
      <div className="md:hidden flex items-center justify-between px-6 py-4">
        <Link to="/" className="flex items-center space-x-2 z-50">
          <img src="/logo.png" alt={t('header.logo_alt', 'Mamiviet logo')} className="h-12 w-auto" />
        </Link>
        <div className="flex items-center space-x-4">
          <button
            className="text-white hover:text-primary border-white hover:border-primary px-3 py-1 rounded border transition text-sm font-semibold"
            onClick={() => i18n.changeLanguage(i18n.language === 'de' ? 'en' : 'de')}
            aria-label={t('header.lang_switch', 'Sprache wechseln')}
          >
            {i18n.language === 'de' ? 'DE' : 'EN'}
          </button>
          <button
            className="p-2 focus:outline-none z-50"
            onClick={() => setOpen(true)}
            aria-label="Open menu"
          >
            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>

      {/* Desktop header */}
      <div className="hidden md:block">
        <div className="px-6 py-2 text-center border-b border-white/10" style={{ backgroundColor: '#1f1c17' }}>
          <div className="flex items-center justify-center gap-8 text-sm text-white/80">
            <span>{t('header.hours')}</span>
            <span className="hidden lg:inline">|</span>
            <span className="hidden lg:inline">{t('header.hotline')}</span>
            <span className="hidden xl:inline">|</span>
            <span className="hidden xl:inline">{t('header.locations')}</span>
          </div>
        </div>

        <div className="grid grid-cols-3 items-center px-6 py-3">
          <nav className="flex items-center justify-start space-x-6">
            {NAV_KEYS.map((item) => (
              item.scroll ? (
                <button
                  key={item.key}
                  onClick={() => onCLickScroll(item.target)}
                  className="text-white hover:text-primary transition-colors text-sm font-medium px-3 py-2 rounded uppercase tracking-wider whitespace-nowrap"
                >
                  {t(`header.${item.key}`)}
                </button>
              ) : item.key === 'menu' ? (
                <GloriaFoodButton
                  key={item.key}
                  cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
                  ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
                  type="order"
                  className="!bg-transparent !bg-none !text-white hover:!text-primary !transition-colors !text-sm !font-medium !px-3 !py-2 !rounded !uppercase !tracking-wider !whitespace-nowrap"
                >
                  {t(`header.${item.key}`)}
                </GloriaFoodButton>
              ) : (
                <Link
                  key={item.key}
                  to={item.href}
                  className="text-white hover:text-primary transition-colors text-sm font-medium px-3 py-2 rounded uppercase tracking-wider whitespace-nowrap"
                >
                  {t(`header.${item.key}`)}
                </Link>
              )
            ))}
          </nav>

          <div className="flex items-center justify-center">
            <Link to="/" className="flex items-center space-x-3">
              <img src="/logo.png" alt={t('header.logo_alt', 'Mamiviet logo')} className="h-14 w-auto" />
            </Link>
          </div>

          <div className="flex items-center justify-end space-x-4">
            <button
              className="text-white hover:text-primary border-white hover:border-primary px-3 py-2 rounded border transition text-sm font-semibold uppercase tracking-wider"
              onClick={() => i18n.changeLanguage(i18n.language === 'de' ? 'en' : 'de')}
              aria-label={t('header.lang_switch', 'Sprache wechseln')}
            >
              {i18n.language === 'de' ? 'DE' : 'EN'}
            </button>

            <button
              className="p-2 focus:outline-none"
              onClick={() => setOpen(true)}
              aria-label="Open menu"
            >
              <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      {/* Off-canvas menu */}
      {open && (
        <div className="fixed inset-0 bg-black/90 z-50 flex flex-col items-end">
          <button
            className="p-4 text-white text-2xl self-end"
            onClick={() => setOpen(false)}
            aria-label="Close menu"
          >
            <X size={32} />
          </button>
          <nav className="w-full max-w-xs bg-black/95 h-full p-8 pt-0 flex flex-col space-y-6">
            <div className="mb-8 mt-4 flex items-center space-x-2">
              <img src="/logo.png" alt="Mamiviet logo" className="h-12 w-auto" />
              <span className="text-lg font-bold tracking-widest">{t('header.logo_text', 'Mamiviet.')}</span>
            </div>
            {NAV_KEYS.map((item) => (
              item.key === 'menu' ? (
                <GloriaFoodButton
                  key={item.key}
                  cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
                  ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
                  type="order"
                  className="!block !py-2 !text-lg !font-medium !text-white hover:!text-primary !transition-colors !bg-transparent !bg-none"
                >
                  {t(`header.${item.key}`)}
                </GloriaFoodButton>
              ) : (
                <Link
                  key={item.key}
                  to={item.href}
                  className="block py-2 text-lg font-medium text-white hover:text-primary transition-colors"
                  onClick={() => setOpen(false)}
                >
                  {t(`header.${item.key}`)}
                </Link>
              )
            ))}
          </nav>
        </div>
      )}
    </header>
  );
}
