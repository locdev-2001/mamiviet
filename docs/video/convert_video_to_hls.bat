@echo off
echo Converting video.mp4 to HLS with 2-second segments (~1MB each)...
echo This may take 2-5 minutes depending on video length.
echo.

cd /d "%~dp0public"

ffmpeg -i video.mp4 -c:v libx264 -b:v 3500k -maxrate 4000k -bufsize 8000k -preset fast -c:a aac -b:a 96k -hls_time 2 -hls_list_size 0 -hls_segment_filename "video%%d.ts" video.m3u8

echo.
echo Conversion completed!
echo New segments should be ~1MB each
echo Press any key to exit...
pause > nul
