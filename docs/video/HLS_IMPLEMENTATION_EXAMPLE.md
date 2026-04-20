# Cách Triển Khai HLS cho Video Lớn

## Bước 1: Cài đặt HLS.js
```bash
npm install hls.js
npm install --save-dev @types/hls.js
```

## Bước 2: Convert Video sang HLS
```bash
# Cần cài FFmpeg
ffmpeg -i video.mp4 \
  -c:v h264 -c:a aac \
  -f hls \
  -hls_time 6 \
  -hls_playlist_type vod \
  -hls_segment_filename "video_%03d.ts" \
  video.m3u8
```

## Bước 3: Sử dụng HLSPlayer Component

### Thay thế video hiện tại:
```typescript
// Thay vì:
<video src="/video.mp4" />

// Sử dụng:
import HLSPlayer from '@/components/HLSPlayer';

<HLSPlayer
  src="/video.m3u8"
  poster="/restaurant.jpg"
  autoPlay={isVideoLoaded}
  loop
  muted
  playsInline
  className="w-full h-full object-cover"
/>
```

### Trong Index.tsx:
```typescript
// Thay đổi video container
{isVideoLoaded && (
  <HLSPlayer
    src="/video.m3u8"
    poster="/restaurant.jpg"
    autoPlay={true}
    loop
    muted
    playsInline
    className="w-full h-full object-cover"
  />
)}
```

## Bước 4: Tạo Multiple Quality Streams

```bash
# 1080p
ffmpeg -i video.mp4 -c:v h264 -b:v 3000k -c:a aac -f hls video_1080p.m3u8

# 720p
ffmpeg -i video.mp4 -c:v h264 -b:v 1500k -c:a aac -f hls video_720p.m3u8

# 480p
ffmpeg -i video.mp4 -c:v h264 -b:v 800k -c:a aac -f hls video_480p.m3u8

# Master playlist
cat > video_master.m3u8 << EOF
#EXTM3U
#EXT-X-VERSION:3
#EXT-X-STREAM-INF:BANDWIDTH=3000000,RESOLUTION=1920x1080
video_1080p.m3u8
#EXT-X-STREAM-INF:BANDWIDTH=1500000,RESOLUTION=1280x720
video_720p.m3u8
#EXT-X-STREAM-INF:BANDWIDTH=800000,RESOLUTION=854x480
video_480p.m3u8
EOF
```

## Khi Nào Nên Sử dụng HLS:

### ✅ Sử dụng HLS khi:
- Video > 10MB
- Cần adaptive quality
- User có mạng không ổn định
- Video dài > 2 phút

### ❌ KHÔNG cần HLS khi:
- Video < 5MB
- Video ngắn (< 30s)
- Chỉ là background video
- Cần simple implementation

## Production Deployment:

### 1. File Structure:
```
public/
  videos/
    hero/
      video.m3u8          # Master playlist
      video_1080p.m3u8    # 1080p playlist
      video_720p.m3u8     # 720p playlist
      video_480p.m3u8     # 480p playlist
      video_000.ts        # Video segments
      video_001.ts
      ...
```

### 2. CDN Configuration:
```nginx
# Nginx config for HLS
location ~* \.(m3u8)$ {
    add_header Cache-Control "max-age=0, no-cache, no-store, must-revalidate";
    add_header Access-Control-Allow-Origin "*";
    add_header Access-Control-Allow-Methods "GET, HEAD, OPTIONS";
}

location ~* \.(ts)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    add_header Access-Control-Allow-Origin "*";
}
```

## Lưu Ý Quan Trọng:

1. **File Size**: HLS tạo nhiều file nhỏ thay vì 1 file lớn
2. **Browser Support**: Hỗ trợ tất cả browsers hiện đại
3. **SEO**: Không ảnh hưởng đến SEO
4. **Cost**: CDN cost cao hơn do nhiều requests
5. **Complexity**: Setup phức tạp hơn video thường

## Kết Luận:

Với video hiện tại của bạn, **lazy loading + compression** đã đủ tối ưu.
HLS chỉ cần thiết nếu:
- Video rất lớn (>50MB)
- Cần adaptive streaming
- Có budget cho infrastructure phức tạp