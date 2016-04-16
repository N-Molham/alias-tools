for /f usebackq %%a IN (`dir /b *.avi`) do ffmpeg -i %%a -codec:v mpeg2video -qscale:v 2 -codec:a mp2 -b:a 192k done\%%a.mpg
pause