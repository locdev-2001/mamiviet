@echo off
echo ========================================
echo VIDEO OPTIMIZATION SCRIPT
echo ========================================
echo.
echo Video hiện tại: video.mp4 (467MB)
echo.
echo Bước 1: Cài đặt FFmpeg
echo ========================================
echo.
echo Cách 1: Tải trực tiếp
echo 1. Vào https://ffmpeg.org/download.html#build-windows
echo 2. Tải Windows builds by BtbN
echo 3. Giải nén vào C:\ffmpeg
echo 4. Thêm C:\ffmpeg\bin vào PATH
echo.
echo Cách 2: Winget (as Administrator)
echo winget install "FFmpeg (Essentials Build)"
echo.
echo Cách 3: Chocolatey (as Administrator)
echo choco install ffmpeg
echo.
echo ========================================
echo Bước 2: Nén video (chạy sau khi cài FFmpeg)
echo ========================================
echo.

REM Kiểm tra FFmpeg
ffmpeg -version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ FFmpeg chưa được cài đặt!
    echo Vui lòng cài đặt FFmpeg trước khi chạy script này.
    echo.
    pause
    exit /b 1
)

echo ✅ FFmpeg đã được cài đặt!
echo.

cd /d "%~dp0"

if not exist "public\video.mp4" (
    echo ❌ Không tìm thấy public\video.mp4
    pause
    exit /b 1
)

echo 🎬 Bắt đầu nén video...
echo.

echo 📁 Tạo thư mục backup...
if not exist "public\video_backup" mkdir "public\video_backup"
copy "public\video.mp4" "public\video_backup\video_original.mp4"

echo.
echo 🔧 Đang tạo WebM version (nén tốt hơn)...
ffmpeg -i "public\video.mp4" -c:v libvpx-vp9 -crf 30 -b:v 0 -c:a libopus -b:a 128k -y "public\video.webm"

echo.
echo 🔧 Đang tạo MP4 tối ưu...
ffmpeg -i "public\video.mp4" -c:v libx264 -crf 23 -preset medium -c:a aac -b:a 128k -movflags +faststart -y "public\video_optimized.mp4"

echo.
echo 🔧 Đang tạo poster frame...
ffmpeg -i "public\video.mp4" -ss 00:00:01 -vframes 1 -q:v 2 -y "public\video_poster.jpg"

echo.
echo 📊 So sánh kích thước:
echo.

for %%f in ("public\video.mp4") do echo Original MP4: %%~zf bytes
for %%f in ("public\video.webm") do echo WebM: %%~zf bytes
for %%f in ("public\video_optimized.mp4") do echo Optimized MP4: %%~zf bytes

echo.
echo ========================================
echo Bước 3: Cập nhật code
echo ========================================
echo.
echo 1. Đổi tên file:
echo    - video_optimized.mp4 → video.mp4
echo    - Giữ video.webm
echo.
echo 2. Code đã được cập nhật để sử dụng:
echo    - Lazy loading
echo    - Multiple formats (WebM + MP4)
echo    - HLS ready
echo.
echo ✅ HOÀN THÀNH!
echo.
pause