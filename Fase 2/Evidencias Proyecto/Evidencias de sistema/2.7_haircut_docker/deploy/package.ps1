param(
    [string]$OutputPath = (Join-Path $env:USERPROFILE 'Downloads\haircut-2.7-hostinger.tar.gz')
)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$project = Join-Path $root 'HaircutHomeStudio'
$stage = Join-Path ([System.IO.Path]::GetTempPath()) ('hhs-package-' + [guid]::NewGuid().ToString('N'))

function Copy-SourceTree {
    param([string]$Source, [string]$Destination, [string[]]$ExcludedDirectories = @())

    $arguments = @($Source, $Destination, '/E', '/XJ', '/R:1', '/W:1', '/NFL', '/NDL', '/NJH', '/NJS', '/NP',
        '/XF', '.env', '.env.*', '*.log', '*.sql', '*.sqlite', '*.sqlite-*', 'hot')
    if ($ExcludedDirectories.Count -gt 0) {
        $arguments += '/XD'
        $arguments += $ExcludedDirectories
    }
    & robocopy @arguments | Out-Null
    if ($LASTEXITCODE -ge 8) {
        throw "No se pudo copiar $Source (robocopy: $LASTEXITCODE)."
    }
}

try {
    New-Item -ItemType Directory -Path $stage | Out-Null
    $application = Join-Path $stage 'HaircutHomeStudio'
    New-Item -ItemType Directory -Path $application | Out-Null
    foreach ($directory in @('app', 'bootstrap', 'config', 'resources', 'routes', 'docker')) {
        Copy-SourceTree (Join-Path $project $directory) (Join-Path $application $directory) @((Join-Path $project 'bootstrap\cache'))
    }
    foreach ($directory in @('migrations', 'seeders', 'factories')) {
        Copy-SourceTree (Join-Path $project "database\$directory") (Join-Path $application "database\$directory")
    }
    Copy-SourceTree (Join-Path $project 'public') (Join-Path $application 'public') @(
        (Join-Path $project 'public\storage'), (Join-Path $project 'public\build')
    )
    foreach ($file in @('artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'vite.config.js', 'Dockerfile', '.dockerignore')) {
        Copy-Item -LiteralPath (Join-Path $project $file) -Destination (Join-Path $application $file)
    }
    foreach ($file in @('compose.production.yaml', '.env.production.example', 'GUIA_HOSTINGER.md')) {
        Copy-Item -LiteralPath (Join-Path $root $file) -Destination (Join-Path $stage $file)
    }
    $deployment = New-Item -ItemType Directory -Path (Join-Path $stage 'deploy')
    foreach ($file in @('Caddyfile', 'hhs')) {
        Copy-Item -LiteralPath (Join-Path $PSScriptRoot $file) -Destination (Join-Path $deployment.FullName $file)
    }

    $destination = [System.IO.Path]::GetFullPath($OutputPath)
    New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
    & tar.exe -czf $destination -C $stage .
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo generar el archivo de despliegue.'
    }
    $entries = & tar.exe -tzf $destination
    if ($LASTEXITCODE -ne 0) {
        throw 'No se pudo verificar el archivo generado.'
    }
    $forbidden = $entries | Where-Object {
        $_ -match '(^|/)(vendor|node_modules|storage|tests|\.git)(/|$)' -or
        $_ -match '(^|/)\.env($|/)' -or
        ($_ -match '(^|/)\.env\.' -and $_ -notmatch '^\./\.env\.production\.example$') -or
        $_ -match '\.(sql|sqlite)(-|$)' -or
        $_ -match 'bootstrap/cache/.+\.php$'
    }
    if ($forbidden) {
        throw 'El paquete contiene un archivo no permitido. No lo subas al servidor.'
    }
    Write-Output "Paquete 2.7 generado y verificado: $destination"
    Get-FileHash -LiteralPath $destination -Algorithm SHA256
} finally {
    if (Test-Path -LiteralPath $stage) {
        Remove-Item -LiteralPath $stage -Recurse -Force
    }
}
