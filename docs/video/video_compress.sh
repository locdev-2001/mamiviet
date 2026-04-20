#!/bin/bash

echo "========================================"
echo "VIDEO OPTIMIZATION SCRIPT (Linux/Mac)"
echo "========================================"
echo ""
echo "Video hiện tại: video.mp4 (467MB)"
echo ""

# Check if FFmpeg is installed
if ! command -v ffmpeg &> /dev/null; then
    echo "❌ FFmpeg chưa được cài đặt!"
    echo ""
    echo "Cài đặt FFmpeg:"
    echo "Ubuntu/Debian: sudo apt install ffmpeg"
    echo "CentOS/RHEL: sudo yum install ffmpeg"
    echo "macOS: brew install ffmpeg"
    echo ""
    exit 1
fi

echo "✅ FFmpeg đã được cài đặt!"
echo ""

cd "$(dirname "$0")"

if [ ! -f "public/video.mp4" ]; then
    echo "❌ Không tìm thấy public/video.mp4"
    exit 1
fi

echo "🎬 Bắt đầu nén video..."
echo ""

# Create backup
echo "📁 Tạo thư mục backup..."
mkdir -p "public/video_backup"
cp "public/video.mp4" "public/video_backup/video_original.mp4"

# Compress to WebM
echo ""
echo "🔧 Đang tạo WebM version (nén tốt hơn)..."
ffmpeg -i "public/video.mp4" -c:v libvpx-vp9 -crf 30 -b:v 0 -c:a libopus -b:a 128k -y "public/video.webm"

# Compress to optimized MP4
echo ""
echo "🔧 Đang tạo MP4 tối ưu..."
ffmpeg -i "public/video.mp4" -c:v libx264 -crf 23 -preset medium -c:a aac -b:a 128k -movflags +faststart -y "public/video_optimized.mp4"

# Create poster
echo ""
echo "🔧 Đang tạo poster frame..."
ffmpeg -i "public/video.mp4" -ss 00:00:01 -vframes 1 -q:v 2 -y "public/video_poster.jpg"

# Show file sizes
echo ""
echo "📊 So sánh kích thước:"
echo ""
ls -lh "public/video.mp4" | awk '{print "Original MP4: " $5}'
ls -lh "public/video.webm" | awk '{print "WebM: " $5}'
ls -lh "public/video_optimized.mp4" | awk '{print "Optimized MP4: " $5}'

echo ""
echo "========================================"
echo "Bước tiếp theo:"
echo "========================================"
echo ""
echo "1. Đổi tên file:"
echo "   mv public/video_optimized.mp4 public/video.mp4"
echo ""
echo "2. Code đã được cập nhật với:"
echo "   - Lazy loading"
echo "   - Multiple formats"
echo "   - HLS ready"
echo ""
echo "✅ HOÀN THÀNH!"