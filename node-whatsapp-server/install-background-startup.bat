@echo off
setlocal
cd /d "%~dp0"

echo.
echo AutoRC WhatsApp Node Server - arranque invisivel
echo.

where node >nul 2>nul
if errorlevel 1 (
  echo ERRO: Node.js nao encontrado.
  pause
  exit /b 1
)

if not exist ".env" (
  echo ERRO: ficheiro .env nao encontrado.
  pause
  exit /b 1
)

if not exist "node_modules" (
  echo Dependencias nao encontradas. A instalar...
  set PUPPETEER_SKIP_DOWNLOAD=true
  npm.cmd install
  if errorlevel 1 (
    echo ERRO: npm install falhou.
    pause
    exit /b 1
  )
)

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$startup=[Environment]::GetFolderPath('Startup'); $shortcutPath=Join-Path $startup 'AutoRC WhatsApp Node Server.lnk'; $shell=New-Object -ComObject WScript.Shell; $shortcut=$shell.CreateShortcut($shortcutPath); $shortcut.TargetPath='wscript.exe'; $shortcut.Arguments='\"%CD%\start-background.vbs\"'; $shortcut.WorkingDirectory='%CD%'; $shortcut.WindowStyle=7; $shortcut.Description='AutoRC WhatsApp Node Server'; $shortcut.Save()"
if errorlevel 1 (
  echo ERRO: nao foi possivel criar o atalho de arranque.
  pause
  exit /b 1
)

wscript.exe "%CD%\start-background.vbs"

echo.
echo Instalado e iniciado em segundo plano.
echo Logs: %CD%\logs
pause
