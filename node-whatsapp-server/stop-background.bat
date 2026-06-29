@echo off
setlocal

echo.
echo A parar AutoRC WhatsApp Node Server...
echo.

powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "$root=(Resolve-Path '%~dp0').Path.TrimEnd('\'); $port=Get-NetTCPConnection -LocalPort 3099 -ErrorAction SilentlyContinue | Select-Object -First 1; if ($port) { Stop-Process -Id $port.OwningProcess -Force }; Get-CimInstance Win32_Process | Where-Object { $_.ProcessId -ne $PID -and $_.Name -eq 'powershell.exe' -and $_.CommandLine -like ('*' + $root + '*run-background.ps1*') } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force }"

echo Parado.
pause
