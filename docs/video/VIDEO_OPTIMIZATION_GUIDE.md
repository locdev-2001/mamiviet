# Video Optimization Guide

## Đã Triển Khai

### 1. Lazy Loading Video
- Video chỉ tải khi user scroll đến gần vị trí video (50px trước)
- Sử dụng Intersection Observer API
- `preload="none"` để tránh tải ngay khi page load

### 2. Multiple Video Formats
- WebM: Format nén tốt hơn, hỗ trợ tốt trên Chrome/Firefox
- MP4: Format fallback cho Safari/Edge
- Browser tự động chọn format tốt nhất

### 3. Preload Strategy
- Hero video: `preload="none"` (chỉ tải khi cần)
- Modal video: `preload="metadata"` (tải thông tin cơ bản)

## Tối Ưu Hóa Nâng Cao

### HLS (HTTP Live Streaming)

#### Ưu điểm HLS:
- Adaptive bitrate: Tự động điều chỉnh chất lượng theo băng thông
- Segmented loading: Tải từng đoạn nhỏ thay vì toàn bộ file
- Better user experience trên mạng chậm
- Hỗ trợ multiple resolutions (360p, 720p, 1080p)

#### Cách triển khai HLS:

1. **Convert video sang HLS format:**
```bash
# Sử dụng FFmpeg
ffmpeg -i video.mp4 -c:v h264 -c:a aac -f hls -hls_time 6 -hls_playlist_type vod video.m3u8
```

2. **Cài đặt HLS player:**
```bash
npm install hls.js
```

3. **Component HLS:**
```typescript
import Hls from 'hls.js';

const HLSPlayer = ({ src, poster, ...props }) => {
  const videoRef = useRef<HTMLVideoElement>(null);

  useEffect(() => {
    if (Hls.isSupported() && videoRef.current) {
      const hls = new Hls();
      hls.loadSource(src);
      hls.attachMedia(videoRef.current);
    } else if (videoRef.current?.canPlayType('application/vnd.apple.mpegurl')) {
      // Safari native HLS support
      videoRef.current.src = src;
    }
  }, [src]);

  return <video ref={videoRef} poster={poster} {...props} />;
};
```

### Video Compression Best Practices

#### 1. Optimal Settings:
- **Resolution**: 1080p max cho web
- **Bitrate**: 2-4 Mbps cho 1080p, 1-2 Mbps cho 720p
- **Frame rate**: 30fps (60fps chỉ khi cần thiết)
- **Codec**: H.264/H.265 cho MP4, VP9 cho WebM

#### 2. FFmpeg Commands:
```bash
# Compress MP4
ffmpeg -i input.mp4 -c:v libx264 -crf 23 -preset medium -c:a aac -b:a 128k output.mp4

# Create WebM
ffmpeg -i input.mp4 -c:v libvpx-vp9 -crf 30 -b:v 0 -c:a libopus output.webm

# Create multiple resolutions
ffmpeg -i input.mp4 -vf scale=1920:1080 -c:v libx264 -crf 23 output_1080p.mp4
ffmpeg -i input.mp4 -vf scale=1280:720 -c:v libx264 -crf 25 output_720p.mp4
```

### CDN Optimization

#### 1. Video CDN Services:
- **Cloudflare Stream**: Automatic optimization + HLS
- **AWS CloudFront**: Custom caching rules
- **Vimeo/YouTube**: Embed thay vì host trực tiếp

#### 2. Cache Headers:
```nginx
location ~* \.(mp4|webm|m3u8|ts)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### Progressive Enhancement

#### 1. Loading States:
```typescript
const [videoState, setVideoState] = useState<'loading' | 'loaded' | 'error'>('loading');

<video
  onLoadStart={() => setVideoState('loading')}
  onCanPlay={() => setVideoState('loaded')}
  onError={() => setVideoState('error')}
/>
```

#### 2. Network-aware Loading:
```typescript
const connection = (navigator as any).connection;
const slowConnection = connection?.effectiveType === 'slow-2g' || connection?.effectiveType === '2g';

// Chỉ auto-play nếu mạng nhanh
const shouldAutoPlay = !slowConnection && isVideoLoaded;
```

### Monitoring & Analytics

#### 1. Video Performance Metrics:
- Time to first frame
- Buffer health
- Playback quality
- Error rates

#### 2. Implementation:
```typescript
const videoRef = useRef<HTMLVideoElement>(null);

useEffect(() => {
  const video = videoRef.current;
  if (!video) return;

  const handleProgress = () => {
    const buffered = video.buffered;
    const currentTime = video.currentTime;
    // Log buffer health
  };

  video.addEventListener('progress', handleProgress);
  return () => video.removeEventListener('progress', handleProgress);
}, []);
```

## Kết Luận

**Đã triển khai**: Lazy loading, multiple formats, optimal preload
**Khuyến nghị tiếp theo**:
1. Convert video sang WebM/MP4 với compression tốt hơn
2. Nếu video > 10MB: Triển khai HLS
3. Sử dụng CDN cho video delivery
4. Monitor performance metrics