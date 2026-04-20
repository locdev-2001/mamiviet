import React, { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';

interface GloriaFoodButtonProps {
  /** Customer UID from GloriaFood */
  cuid: string;
  /** Restaurant UID from GloriaFood */
  ruid: string;
  /** Button type - 'order' for menu/ordering, 'reservation' for table booking */
  type: 'order' | 'reservation';
  /** Button text to display */
  children: React.ReactNode;
  /** Additional CSS classes */
  className?: string;
}

/**
 * GloriaFood Widget Button Component
 *
 * Tạo nút tích hợp với GloriaFood widget. Hỗ trợ 2 loại:
 * - 'order': Nút đặt món ăn
 * - 'reservation': Nút đặt bàn
 *
 * LƯU Ý: Script GloriaFood đã được tải trong index.html
 *
 * @example
 * // Nút đặt món
 * <GloriaFoodButton
 *   cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
 *   ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
 *   type="order"
 * >
 *   Đặt món ngay
 * </GloriaFoodButton>
 *
 * @example
 * // Nút đặt bàn
 * <GloriaFoodButton
 *   cuid="ea4b98df-3398-4fc2-bde2-9bb461488df0"
 *   ruid="d125f5d8-a9d0-4610-94f8-de39e8dac4f4"
 *   type="reservation"
 *   className="custom-class"
 * >
 *   Đặt bàn
 * </GloriaFoodButton>
 */
export const GloriaFoodButton: React.FC<GloriaFoodButtonProps> = ({
  cuid,
  ruid,
  type,
  children,
  className = '',
}) => {
  const buttonRef = useRef<HTMLSpanElement>(null);
  const location = useLocation(); // Track route changes

  useEffect(() => {
    // Load GloriaFood script dynamically AFTER component mounts
    const loadScript = () => {
      // Check if script already exists
      const existingScript = document.querySelector('script[src*="fbgcdn.com/embedder"]');

      if (!existingScript) {
        // Create and load script
        const script = document.createElement('script');
        script.src = 'https://www.fbgcdn.com/embedder/js/ewm2.js';
        script.async = true;
        script.defer = true;
        document.body.appendChild(script);
      } else {
        // Script exists, rebind buttons for new route
        setTimeout(() => {
          if (typeof (window as any).glfBindButtons === 'function') {
            (window as any).glfBindButtons();
          }
        }, 200);
      }
    };

    // Delay to ensure DOM is ready
    const timer = setTimeout(loadScript, 100);

    return () => clearTimeout(timer);
  }, [location.pathname]); // Re-run when route changes

  const buttonProps: any = {
    className: `cursor-pointer ${type === 'reservation' ? 'reservation' : ''} ${className}`.trim(),
    'data-glf-cuid': cuid,
    'data-glf-ruid': ruid,
    ref: buttonRef,
  };

  // Add reservation attribute if type is reservation
  if (type === 'reservation') {
    buttonProps['data-glf-reservation'] = 'true';
  }

  return <span {...buttonProps}>{children}</span>;
};

export default GloriaFoodButton;