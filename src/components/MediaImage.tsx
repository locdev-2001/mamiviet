import type { MediaItem } from '@/types/appContent';

type MediaImageProps = {
  media: MediaItem | null | undefined;
  fallbackSrc: string;
  fallbackAlt: string;
  className?: string;
  priority?: boolean;
  sizes?: string;
  style?: React.CSSProperties;
};

export default function MediaImage({
  media,
  fallbackSrc,
  fallbackAlt,
  className,
  priority = false,
  sizes = '100vw',
  style,
}: MediaImageProps) {
  const loading = priority ? 'eager' : 'lazy';
  const priorityAttrs = priority ? { fetchpriority: 'high' as const } : {};

  if (!media?.src) {
    return (
      <img
        src={fallbackSrc}
        alt={fallbackAlt}
        className={className}
        style={style}
        loading={loading}
        decoding="async"
        {...priorityAttrs}
      />
    );
  }

  const alt = media.alt || fallbackAlt;
  const dimensions =
    media.width && media.height ? { width: media.width, height: media.height } : {};

  return (
    <picture>
      {media.srcset && (
        <source type={media.type ?? 'image/webp'} srcSet={media.srcset} sizes={sizes} />
      )}
      <img
        src={media.src}
        alt={alt}
        className={className}
        style={style}
        loading={loading}
        decoding="async"
        {...dimensions}
        {...priorityAttrs}
      />
    </picture>
  );
}
