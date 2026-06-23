@echo off
setlocal
cd /d "%~dp0"

echo.
echo AutoRC WhatsApp Node Server
echo.

if not exist ".env" (
  echo ERRO: ficheiro .env nao encontrado.
  echo Corre primeiro install-windows.bat.
  pause
  exit /b 1
)

where node >nul 2>nul
if errorlevel 1 (
  echo ERRO: Node.js nao encontrado.
  echo Instala o Node.js LTS em https://nodejs.org/.
  pause
  exit /b 1
)

if not exist "node_modules" (
  echo Dependencias nao encontradas. A correr npm install...
  npm install
  if errorlevel 1 (
    echo.
    echo ERRO: npm install falhou.
    pause
    exit /b 1
  )
)

npm start
pause
