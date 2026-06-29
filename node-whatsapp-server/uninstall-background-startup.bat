@echo off
setlocal

call "%~dp0stop-background.bat"
powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$shortcutPath=Join-Path ([Environment]::GetFolderPath('Startup')) 'AutoRC WhatsApp Node Server.lnk'; Remove-Item -LiteralPath $shortcutPath -Force -ErrorAction SilentlyContinue"

echo.
echo Tarefa removida.
pause
