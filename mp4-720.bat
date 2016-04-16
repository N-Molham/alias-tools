@ECHO OFF
ffmpeg -i %1 -vcodec libx264 -preset ultrafast -s 1280x720 -acodec copy %2