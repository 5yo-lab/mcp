# Windows helper: runs the canonical bootstrap script via sh (Git Bash / WSL).
# Prefer:  sh scripts/bootstrap.sh
$ErrorActionPreference = "Stop"
$Root = if ($PSScriptRoot) { Split-Path -Parent $PSScriptRoot } else { Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path) }
Set-Location $Root

$sh = Get-Command sh -ErrorAction SilentlyContinue
if (-not $sh) {
  Write-Error @"
'sh' not found. Use one of:
  - Git Bash:  sh scripts/bootstrap.sh
  - WSL:       wsl sh scripts/bootstrap.sh
  - Install Git for Windows (includes sh)
"@
}

& sh "$Root/scripts/bootstrap.sh"
