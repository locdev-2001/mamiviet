import { useTranslation } from "react-i18next";

export function Footer() {
  const { t } = useTranslation();
  const year = new Date().getFullYear();

  return (
    <footer className="text-white relative">
      {/* Top section with #2f2721 background */}
      <div className="bg-[#2f2721] relative">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">

          {/* Main content grid */}
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 sm:gap-12 lg:gap-16 text-center mb-16 sm:mb-20">
            {/* Contact Us */}
            <div className="space-y-4 sm:space-y-6">
              <h3 className="text-base sm:text-lg font-light tracking-[0.15em] sm:tracking-[0.2em] text-white uppercase mb-6 sm:mb-8">
                {t('footer.contact_us')}
              </h3>
              <div className="space-y-2 sm:space-y-3 text-neutral-200">
                <p className="text-xs sm:text-sm font-light">
                  T. {t('footer.phone')}
                </p>
                <p className="text-xs sm:text-sm font-light">
                  M. {t('footer.email')}
                </p>
              </div>
            </div>

            {/* Address */}
            <div className="space-y-4 sm:space-y-6">
              <h3 className="text-base sm:text-lg font-light tracking-[0.15em] sm:tracking-[0.2em] text-white uppercase mb-6 sm:mb-8">
                {t('footer.address')}
              </h3>
              <div className="space-y-1 text-neutral-200">
                <p className="text-xs sm:text-sm font-light">{t('footer.address_line1')}</p>
                <p className="text-xs sm:text-sm font-light">{t('footer.address_line2')}</p>
              </div>
            </div>

            {/* Opening Hours */}
            <div className="space-y-4 sm:space-y-6 sm:col-span-2 lg:col-span-1">
              <h3 className="text-base sm:text-lg font-light tracking-[0.15em] sm:tracking-[0.2em] text-white mb-6 sm:mb-8 capitalize">
                {t('footer.opening_hours')}
              </h3>
              <div className="space-y-1 text-neutral-200 text-xs sm:text-sm font-light">
                <p>{t('footer.hours_mon_thu')}</p>
                <p>{t('footer.hours_mon_thu_evening')}</p>
                {/*<p>{t('footer.hours_fri_sat')}</p>*/}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom section with #262212 background */}
      <div className="bg-[#262212] relative">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
          {/* Top part: Logo with horizontal lines on both sides */}
          <div className="flex items-center justify-center mb-8 sm:mb-10 lg:mb-12">
            {/* Left line */}
            <div className="h-px bg-neutral-600 flex-1"></div>
            
            {/* Logo */}
            <div className="mx-4 sm:mx-6 lg:mx-8">
              <div className="w-16 h-16 sm:w-20 sm:h-20 lg:w-24 lg:h-24 rounded-full flex items-center justify-center shadow-lg">
                <img 
                  src="/logo.png" 
                  alt={t('header.logo_alt', 'Mamiviet logo')} 
                  className="h-10 sm:h-12 lg:h-16 w-auto"
                />
              </div>
            </div>
            
            {/* Right line */}
            <div className="h-px bg-neutral-600 flex-1"></div>
          </div>

          {/* Bottom part: Social links and Copyright - responsive layout */}
          <div className="flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0">
            {/* Social Links */}
            <div className="flex items-center space-x-3 sm:space-x-4">
              <span className="text-white text-xs tracking-[0.15em] sm:tracking-[0.2em] font-light">FACEBOOK</span>
              <span className="text-white text-xs">◇</span>
              <span className="text-white text-xs tracking-[0.15em] sm:tracking-[0.2em] font-light">INSTAGRAM</span>
            </div>

            {/* Copyright */}
            <div className="text-center sm:text-right">
              <p className="text-white text-xs tracking-[0.1em] sm:tracking-[0.15em] font-light">
                © {year} {t('footer.company_name')}, {t('footer.all_rights_reserved')}
              </p>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}