import { useSetting } from '@/lib/contexts/AppContentContext';
import { useTranslation } from 'react-i18next';

export function Footer() {
  const { t } = useTranslation();
  const year = new Date().getFullYear();

  const contactHeading = useSetting('footer.contact_us', t('footer.contact_us'));
  const addressHeading = useSetting('footer.address', t('footer.address'));
  const hoursHeading = useSetting('footer.opening_hours', t('footer.opening_hours'));

  const phone = useSetting('footer.phone', t('footer.phone'));
  const email = useSetting('footer.email', t('footer.email'));
  const addressLine1 = useSetting('footer.address_line1', t('footer.address_line1'));
  const addressLine2 = useSetting('footer.address_line2', t('footer.address_line2'));

  const hoursRaw = useSetting('footer.hours', `${t('footer.hours_mon_thu')}\n${t('footer.hours_mon_thu_evening')}`);
  const hoursLines = hoursRaw.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);

  const companyName = useSetting('footer.company_name', t('footer.company_name'));
  const rightsText = useSetting('footer.all_rights_reserved', t('footer.all_rights_reserved'));

  const facebookUrl = useSetting('social.facebook_url');
  const instagramUrl = useSetting('social.instagram_url');

  return (
    <footer className="text-white relative">
      <div className="bg-[#2f2721] relative">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 sm:gap-12 lg:gap-16 text-center mb-16 sm:mb-20">
            <div className="space-y-4 sm:space-y-6">
              <h3 className="text-base sm:text-lg font-light tracking-[0.15em] sm:tracking-[0.2em] text-white uppercase mb-6 sm:mb-8">
                {contactHeading}
              </h3>
              <div className="space-y-2 sm:space-y-3 text-neutral-200">
                {phone && <p className="text-xs sm:text-sm font-light">T. {phone}</p>}
                {email && <p className="text-xs sm:text-sm font-light">M. {email}</p>}
              </div>
            </div>

            <div className="space-y-4 sm:space-y-6">
              <h3 className="text-base sm:text-lg font-light tracking-[0.15em] sm:tracking-[0.2em] text-white uppercase mb-6 sm:mb-8">
                {addressHeading}
              </h3>
              <div className="space-y-1 text-neutral-200">
                {addressLine1 && <p className="text-xs sm:text-sm font-light">{addressLine1}</p>}
                {addressLine2 && <p className="text-xs sm:text-sm font-light">{addressLine2}</p>}
              </div>
            </div>

            <div className="space-y-4 sm:space-y-6 sm:col-span-2 lg:col-span-1">
              <h3 className="text-base sm:text-lg font-light tracking-[0.15em] sm:tracking-[0.2em] text-white mb-6 sm:mb-8 capitalize">
                {hoursHeading}
              </h3>
              <div className="space-y-1 text-neutral-200 text-xs sm:text-sm font-light">
                {hoursLines.map((line, idx) => (
                  <p key={idx}>{line}</p>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-[#262212] relative">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
          <div className="flex items-center justify-center mb-8 sm:mb-10 lg:mb-12">
            <div className="h-px bg-neutral-600 flex-1" />
            <div className="mx-4 sm:mx-6 lg:mx-8">
              <div className="w-16 h-16 sm:w-20 sm:h-20 lg:w-24 lg:h-24 rounded-full flex items-center justify-center shadow-lg">
                <img src="/logo.png" alt={t('header.logo_alt', 'Mamiviet logo')} className="h-10 sm:h-12 lg:h-16 w-auto" />
              </div>
            </div>
            <div className="h-px bg-neutral-600 flex-1" />
          </div>

          <div className="flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0">
            <div className="flex items-center space-x-3 sm:space-x-4">
              {facebookUrl ? (
                <a href={facebookUrl} target="_blank" rel="noopener noreferrer" className="text-white text-xs tracking-[0.15em] sm:tracking-[0.2em] font-light hover:text-primary transition-colors">
                  FACEBOOK
                </a>
              ) : (
                <span className="text-white/40 text-xs tracking-[0.15em] sm:tracking-[0.2em] font-light">FACEBOOK</span>
              )}
              <span className="text-white text-xs">◇</span>
              {instagramUrl ? (
                <a href={instagramUrl} target="_blank" rel="noopener noreferrer" className="text-white text-xs tracking-[0.15em] sm:tracking-[0.2em] font-light hover:text-primary transition-colors">
                  INSTAGRAM
                </a>
              ) : (
                <span className="text-white/40 text-xs tracking-[0.15em] sm:tracking-[0.2em] font-light">INSTAGRAM</span>
              )}
            </div>

            <div className="text-center sm:text-right">
              <p className="text-white text-xs tracking-[0.1em] sm:tracking-[0.15em] font-light">
                © {year} {companyName}, {rightsText}
              </p>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
