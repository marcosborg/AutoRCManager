@echo off
setlocal
cd /d "%~dp0"

echo.
echo AutoRC WhatsApp Node Server - instalacao Windows
echo.

where node >nul 2>nul
if errorlevel 1 (
  echo ERRO: Node.js nao encontrado.
  echo Instala o Node.js LTS em https://nodejs.org/ e volta a correr este ficheiro.
  pause
  exit /b 1
)

where npm >nul 2>nul
if errorlevel 1 (
  echo ERRO: npm nao encontrado.
  echo Reinstala o Node.js LTS com npm incluido.
  pause
  exit /b 1
)

if not exist ".env" (
  copy ".env.example" ".env" >nul
  echo Criado ficheiro .env a partir do .env.example.
)

echo.
echo A instalar dependencias...
npm install
if errorlevel 1 (
  echo.
  echo ERRO: npm install falhou.
  pause
  exit /b 1
)

echo.
echo Instalacao concluida.
echo Agora corre start-windows.bat para iniciar o WhatsApp.
pause
