# SkillBridge - mail (SendGrid SMTP) configuration helper.
#
# Prompts for the SMTP settings and the test recipient email, then writes
# them into the local .env file (which is gitignored - credentials are NEVER
# committed). The API key is entered as a secure string and is never echoed.
#
# Run from the project root:
#   powershell -ExecutionPolicy Bypass -File scripts/configure-mail.ps1

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $projectRoot '.env'

if (-not (Test-Path $envFile)) {
    Write-Error ".env not found at $envFile"
    exit 1
}

Write-Host ''
Write-Host '=== SkillBridge mail configuration (SendGrid SMTP) ===' -ForegroundColor Cyan
Write-Host 'Enter your SendGrid SMTP details. The API key is typed as a secret'
Write-Host 'and is only written to .env (never committed to git).'
Write-Host ''

$mailHost = Read-Host 'SMTP host [smtp.sendgrid.net]'
if (-not $mailHost) { $mailHost = 'smtp.sendgrid.net' }

$mailPort = Read-Host 'SMTP port [587]'
if (-not $mailPort) { $mailPort = '587' }

$mailUser = Read-Host 'SMTP username [apikey]'
if (-not $mailUser) { $mailUser = 'apikey' }

$securePass = Read-Host -AsSecureString 'SMTP API key (secret, not echoed)'
$bstr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePass)
$mailPass = [System.Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
[System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)

$mailFrom = Read-Host 'From address (sender verified with SendGrid)'
if (-not $mailFrom) { Write-Error 'From address is required.'; exit 1 }

$mailFromName = Read-Host 'From name [SkillBridge]'
if (-not $mailFromName) { $mailFromName = 'SkillBridge' }

$recipient = Read-Host 'Test recipient email (receives the real reset email)'
if (-not $recipient) { Write-Error 'Recipient email is required.'; exit 1 }

function Set-EnvVar {
    param(
        [string]$File,
        [string]$Name,
        [string]$Value
    )
    $lines = Get-Content -LiteralPath $File
    $found = $false
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match "^$([regex]::Escape($Name))=") {
            $lines[$i] = "$Name=`"$Value`""
            $found = $true
        }
    }
    if (-not $found) {
        $lines += "$Name=`"$Value`""
    }
    Set-Content -LiteralPath $File -Value $lines
}

Set-EnvVar -File $envFile -Name 'MAIL_MAILER' -Value 'smtp'
Set-EnvVar -File $envFile -Name 'MAIL_HOST' -Value $mailHost
Set-EnvVar -File $envFile -Name 'MAIL_PORT' -Value $mailPort
Set-EnvVar -File $envFile -Name 'MAIL_USERNAME' -Value $mailUser
Set-EnvVar -File $envFile -Name 'MAIL_PASSWORD' -Value $mailPass
Set-EnvVar -File $envFile -Name 'MAIL_ENCRYPTION' -Value 'tls'
Set-EnvVar -File $envFile -Name 'MAIL_FROM_ADDRESS' -Value $mailFrom
Set-EnvVar -File $envFile -Name 'MAIL_FROM_NAME' -Value $mailFromName
Set-EnvVar -File $envFile -Name 'MAIL_TEST_RECIPIENT' -Value $recipient

Write-Host ''
Write-Host 'Mail configuration written to .env.' -ForegroundColor Green
Write-Host 'Run `php artisan config:clear` before testing so Laravel picks up the new values.'
Write-Host ''
