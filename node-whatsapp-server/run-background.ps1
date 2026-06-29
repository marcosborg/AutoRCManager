$ErrorActionPreference = 'Continue'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$logs = Join-Path $root 'logs'
$outLog = Join-Path $logs 'whatsapp-node.out.log'
$errLog = Join-Path $logs 'whatsapp-node.err.log'
$restartLog = Join-Path $logs 'whatsapp-node-restarts.log'

New-Item -ItemType Directory -Path $logs -Force | Out-Null
Set-Location $root

$env:PUPPETEER_SKIP_DOWNLOAD = 'true'

$mutex = New-Object System.Threading.Mutex($false, 'Global\AutoRCWhatsAppNodeServer')
if (-not $mutex.WaitOne(0)) {
  Add-Content -Path $restartLog -Value "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] Another supervisor is already running. Exiting."
  exit 0
}

while ($true) {
  $startedAt = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
  Add-Content -Path $restartLog -Value "[$startedAt] Starting node index.js"

  & node.exe index.js >> $outLog 2>> $errLog
  $exitCode = $LASTEXITCODE

  $stoppedAt = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
  Add-Content -Path $restartLog -Value "[$stoppedAt] node index.js stopped with exit code $exitCode. Restarting in 10 seconds."

  Start-Sleep -Seconds 10
}
