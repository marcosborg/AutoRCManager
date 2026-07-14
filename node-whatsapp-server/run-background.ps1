$ErrorActionPreference = 'Continue'

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$logs = Join-Path $root 'logs'
$outLog = Join-Path $logs 'whatsapp-node.out.log'
$errLog = Join-Path $logs 'whatsapp-node.err.log'
$restartLog = Join-Path $logs 'whatsapp-node-restarts.log'

New-Item -ItemType Directory -Path $logs -Force | Out-Null
Set-Location $root

$env:PUPPETEER_SKIP_DOWNLOAD = 'true'

$sessionName = 'autorc-manager'
$envPath = Join-Path $root '.env'
if (Test-Path $envPath) {
  Get-Content $envPath | ForEach-Object {
    if ($_ -match '^\s*WHATSAPP_SESSION_NAME\s*=\s*(.+?)\s*$') {
      $sessionName = $matches[1].Trim('"').Trim("'")
    }
  }
}

$sessionDir = Join-Path $root ".wwebjs_auth\session-$sessionName"

function Write-RestartLog([string] $message) {
  Add-Content -Path $restartLog -Value "[$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')] $message"
}

function Stop-SessionChrome {
  $sessionMarker = "session-$sessionName"
  $processes = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue |
    Where-Object { $_.Name -eq 'chrome.exe' -and $_.CommandLine -like "*$sessionMarker*" }

  foreach ($process in $processes) {
    Write-RestartLog "Stopping stale Chrome process $($process.ProcessId) for $sessionMarker"
    Stop-Process -Id $process.ProcessId -Force -ErrorAction SilentlyContinue
  }

  if (Test-Path $sessionDir) {
    Get-ChildItem -LiteralPath $sessionDir -Force -Filter 'Singleton*' -ErrorAction SilentlyContinue |
      Remove-Item -Force -ErrorAction SilentlyContinue
  }
}

$mutex = New-Object System.Threading.Mutex($false, 'Global\AutoRCWhatsAppNodeServer')
if (-not $mutex.WaitOne(0)) {
  Write-RestartLog "Another supervisor is already running. Exiting."
  exit 0
}

while ($true) {
  Stop-SessionChrome
  Write-RestartLog "Starting node index.js"

  & node.exe index.js >> $outLog 2>> $errLog
  $exitCode = $LASTEXITCODE

  Write-RestartLog "node index.js stopped with exit code $exitCode. Cleaning session Chrome and restarting in 10 seconds."
  Stop-SessionChrome

  Start-Sleep -Seconds 10
}
