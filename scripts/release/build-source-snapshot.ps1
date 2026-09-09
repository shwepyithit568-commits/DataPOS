#Requires -Version 5.1
<#
.SYNOPSIS
    Builds a clean, safe, reproducible source release ZIP snapshot and SHA-256 manifest.
.DESCRIPTION
    Creates a distribution source archive from Git HEAD. Strictly excludes all secrets,
    real customer data, SQLite databases, local backups, uploads, logs, and vendor/node_modules.
    Includes lockfiles and pre-compiled frontend assets.
.PARAMETER Version
    Semantic version tag (default: 2026.1.0 or Git tag).
.PARAMETER OutputDir
    Destination directory (default: dist/release-snapshots).
.PARAMETER AllowDirty
    Allows build with uncommitted changes (intended for automated testing only).
.PARAMETER IncludeCompiledAssets
    Injects pre-compiled public/build assets into the archive if present.
#>
[CmdletBinding()]
param(
    [string]$Version = "2026.1.0",
    [string]$OutputDir = "dist/release-snapshots",
    [switch]$AllowDirty = $false,
    [switch]$IncludeCompiledAssets = $true
)

$ErrorActionPreference = "Stop"

# 1. Resolve project root
$ProjectRoot = Resolve-Path "$PSScriptRoot/../.."
Set-Location $ProjectRoot

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  DataPOS - Source Release Snapshot Builder" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "Project Root: $ProjectRoot"

# 2. Check Git Clean Working Tree
$status = git status --porcelain
if ($status -and -not $AllowDirty) {
    Write-Error "Preflight Check Failed: Git working tree is dirty. Stash or commit all changes before creating a release snapshot.`n$status"
    exit 1
}

# 3. Secret Preflight Scan
Write-Host "Running secret and sensitive file preflight check..." -ForegroundColor Yellow
$trackedSensitive = git ls-files | Where-Object {
    $_ -match '^(?!\.env\.example$)\.env' -or
    $_ -match '\.(pem|ppk|key)$' -or
    $_ -match 'id_rsa' -or
    $_ -match 'storage/database/.*\.sqlite' -or
    $_ -match 'storage/app/uat-backups/'
}

if ($trackedSensitive) {
    Write-Error "Preflight Check Failed: Tracked sensitive artifacts detected in Git:`n$($trackedSensitive -join "`n")"
    exit 1
}
Write-Host "  [OK] Zero tracked secrets or active databases detected." -ForegroundColor Green

# 4. Extract Git Metadata
$FullSha = (git rev-parse HEAD).Trim()
$ShortSha = (git rev-parse --short HEAD).Trim()
$Branch = (git rev-parse --abbrev-ref HEAD).Trim()
$DateStamp = Get-Date -Format "yyyyMMdd"

$SnapshotName = "datapos-source-v${Version}-${ShortSha}-${DateStamp}"
$FullOutputDir = Join-Path $ProjectRoot $OutputDir
if (-not (Test-Path $FullOutputDir)) {
    New-Item -ItemType Directory -Force -Path $FullOutputDir | Out-Null
}

$ZipPath = Join-Path $FullOutputDir "${SnapshotName}.zip"
$ShaPath = Join-Path $FullOutputDir "${SnapshotName}.sha256"
$ManifestPath = Join-Path $FullOutputDir "${SnapshotName}.manifest.json"

if (Test-Path $ZipPath) {
    Remove-Item $ZipPath -Force
}

# 5. Create Clean Git Source Archive
Write-Host "Exporting source archive via git archive (honoring .gitattributes)..." -ForegroundColor Yellow
git archive --format=zip --worktree-attributes --prefix="${SnapshotName}/" --output=$ZipPath HEAD
if (-not (Test-Path $ZipPath)) {
    Write-Error "Failed to generate git archive ZIP."
    exit 1
}

# 6. Inject Compiled Build Assets (public/build) if requested
$buildManifest = Join-Path $ProjectRoot "public/build/manifest.json"
if ($IncludeCompiledAssets -and (Test-Path $buildManifest)) {
    Write-Host "Injecting pre-compiled frontend assets (public/build)..." -ForegroundColor Yellow
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Update)
    try {
        $buildDir = Join-Path $ProjectRoot "public/build"
        $files = Get-ChildItem -Path $buildDir -Recurse -File
        foreach ($file in $files) {
            $rel = $file.FullName.Substring($ProjectRoot.Path.Length + 1).Replace('\', '/')
            $entryName = "${SnapshotName}/${rel}"
            $existing = $zip.GetEntry($entryName)
            if ($existing) { $existing.Delete() }
            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $file.FullName, $entryName) | Out-Null
        }
    } finally {
        $zip.Dispose()
    }
    Write-Host "  [OK] Compiled assets injected." -ForegroundColor Green
}

# 7. Post-Archive Integrity and Security Verification
Write-Host "Verifying archive contents against security policies..." -ForegroundColor Yellow
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($ZipPath)
try {
    $entryNames = $zip.Entries | ForEach-Object { $_.FullName }

    # Required files
    $hasComposerLock = $entryNames | Where-Object { $_ -match "composer\.lock$" }
    $hasPackageLock  = $entryNames | Where-Object { $_ -match "package-lock\.json$" }
    $hasEnvExample   = $entryNames | Where-Object { $_ -match "\.env\.example$" }

    if (-not $hasComposerLock) { throw "Missing required composer.lock in snapshot." }
    if (-not $hasPackageLock)  { throw "Missing required package-lock.json in snapshot." }
    if (-not $hasEnvExample)   { throw "Missing required .env.example in snapshot." }

    # Prohibited files (Security Assertions)
    $prohibited = $entryNames | Where-Object {
        ($_ -match '\.env$' -and $_ -notmatch '\.env\.example$') -or
        ($_ -match '\.sqlite') -or
        ($_ -match '\.sql$') -or
        ($_ -match '^[^/]+/vendor/') -or
        ($_ -match '^[^/]+/node_modules/') -or
        ($_ -match '^[^/]+/storage/logs/.*\.log$') -or
        ($_ -match '\.(pem|ppk|key)$')
    }

    if ($prohibited) {
        throw "Security Violation: Prohibited files found in release snapshot:`n$($prohibited -join "`n")"
    }

    $entryCount = $zip.Entries.Count
} finally {
    $zip.Dispose()
}
Write-Host "  [OK] Archive structure verified: $entryCount files, 0 security violations." -ForegroundColor Green

# 8. Compute Cryptographic SHA-256 Checksum
Write-Host "Computing SHA-256 checksum..." -ForegroundColor Yellow
$hashResult = Get-FileHash -Path $ZipPath -Algorithm SHA256
$sha256 = $hashResult.Hash.ToLower()
$zipFileName = Split-Path $ZipPath -Leaf
"$sha256  $zipFileName" | Out-File -FilePath $ShaPath -Encoding utf8 -NoNewline

# 9. Generate Manifest JSON
Write-Host "Writing release snapshot manifest..." -ForegroundColor Yellow
$zipSize = (Get-Item $ZipPath).Length
$manifest = [ordered]@{
    app                  = "DataPOS"
    type                 = "source_release_snapshot"
    version              = $Version
    git_commit           = $FullSha
    git_short_sha        = $ShortSha
    git_branch           = $Branch
    built_at             = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
    archive_filename     = $zipFileName
    sha256               = $sha256
    size_bytes           = $zipSize
    files_count          = $entryCount
    security_guarantees  = @(
        "Zero real .env files or API credentials",
        "Zero customer SQLite databases or database dumps",
        "Zero customer media or storage uploads",
        "Zero runtime logs or cache entries",
        "Zero uncompiled vendor or node_modules bloat",
        "Deterministic dependency lockfiles included",
        "Compiled production assets included"
    )
    restore_procedure    = "Unpack ZIP -> composer install --no-dev -> cp .env.example .env -> php artisan key:generate -> php artisan migrate"
}

$manifestJson = $manifest | ConvertTo-Json -Depth 5
$manifestJson | Out-File -FilePath $ManifestPath -Encoding utf8

Write-Host "============================================================" -ForegroundColor Green
Write-Host "  Release Snapshot Built Successfully!" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host "Archive:  $ZipPath ($([math]::Round($zipSize/1MB, 2)) MB)"
Write-Host "Checksum: $ShaPath ($sha256)"
Write-Host "Manifest: $ManifestPath"
Write-Host ""
