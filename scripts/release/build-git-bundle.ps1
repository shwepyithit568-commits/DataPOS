#Requires -Version 5.1
<#
.SYNOPSIS
    Builds a full Git repository bundle snapshot for offline backup and recovery.
.DESCRIPTION
    Creates a verifiable Git bundle containing all branches, tags, and commit history.
    Executes 'git bundle verify' and computes SHA-256 checksum.
    Strictly for internal engineering recovery and disaster recovery; NOT for customer distribution.
.PARAMETER Version
    Semantic version tag (default: 2026.1.0).
.PARAMETER OutputDir
    Destination directory (default: dist/release-snapshots).
.PARAMETER AllowDirty
    Allows build with uncommitted changes (intended for automated testing only).
#>
[CmdletBinding()]
param(
    [string]$Version = "2026.1.0",
    [string]$OutputDir = "dist/release-snapshots",
    [switch]$AllowDirty = $false
)

$ErrorActionPreference = "Stop"

# 1. Resolve project root
$ProjectRoot = Resolve-Path "$PSScriptRoot/../.."
Set-Location $ProjectRoot

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  DataPOS - Git Bundle Builder (Disaster Recovery)" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "Project Root: $ProjectRoot"

# 2. Check Git Clean Working Tree
$status = git status --porcelain
if ($status -and -not $AllowDirty) {
    Write-Error "Preflight Check Failed: Git working tree is dirty. Stash or commit all changes before creating a Git bundle.`n$status"
    exit 1
}

# 3. Extract Git Metadata
$FullSha = (git rev-parse HEAD).Trim()
$ShortSha = (git rev-parse --short HEAD).Trim()
$Branch = (git rev-parse --abbrev-ref HEAD).Trim()
$DateStamp = Get-Date -Format "yyyyMMdd"

$BundleName = "datapos-git-bundle-v${Version}-${ShortSha}-${DateStamp}"
$FullOutputDir = Join-Path $ProjectRoot $OutputDir
if (-not (Test-Path $FullOutputDir)) {
    New-Item -ItemType Directory -Force -Path $FullOutputDir | Out-Null
}

$BundlePath = Join-Path $FullOutputDir "${BundleName}.bundle"
$ShaPath = Join-Path $FullOutputDir "${BundleName}.sha256"
$ManifestPath = Join-Path $FullOutputDir "${BundleName}.manifest.json"

if (Test-Path $BundlePath) {
    Remove-Item $BundlePath -Force
}

# 4. Create Git Bundle
Write-Host "Creating Git bundle for all branches and tags (--all)..." -ForegroundColor Yellow
git bundle create $BundlePath --all
if (-not (Test-Path $BundlePath)) {
    Write-Error "Failed to generate Git bundle."
    exit 1
}

# 5. Verify Git Bundle
Write-Host "Verifying Git bundle via 'git bundle verify'..." -ForegroundColor Yellow
$prevEAP = $ErrorActionPreference
$ErrorActionPreference = "SilentlyContinue"
$verifyOutput = git bundle verify $BundlePath 2>&1
$ErrorActionPreference = $prevEAP

if ($LASTEXITCODE -ne 0) {
    Write-Error "Git bundle verification failed!`n$verifyOutput"
    exit 1
}
Write-Host "  [OK] Git bundle integrity verified." -ForegroundColor Green

# 6. Compute Cryptographic SHA-256 Checksum
Write-Host "Computing SHA-256 checksum..." -ForegroundColor Yellow
$hashResult = Get-FileHash -Path $BundlePath -Algorithm SHA256
$sha256 = $hashResult.Hash.ToLower()
$bundleFileName = Split-Path $BundlePath -Leaf
"$sha256  $bundleFileName" | Out-File -FilePath $ShaPath -Encoding utf8 -NoNewline

# 7. Generate Manifest JSON
Write-Host "Writing Git bundle manifest..." -ForegroundColor Yellow
$bundleSize = (Get-Item $BundlePath).Length
$manifest = [ordered]@{
    app                  = "DataPOS"
    type                 = "git_bundle_snapshot"
    version              = $Version
    git_commit           = $FullSha
    git_short_sha        = $ShortSha
    git_branch           = $Branch
    built_at             = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
    bundle_filename      = $bundleFileName
    sha256               = $sha256
    size_bytes           = $bundleSize
    distribution_policy  = "STRICTLY INTERNAL / ENGINEERING DISASTER RECOVERY. NEVER DISTRIBUTE TO CUSTOMERS."
    restore_procedure    = "git clone $bundleFileName DataPOS-restored -> cd DataPOS-restored -> git checkout main"
}

$manifestJson = $manifest | ConvertTo-Json -Depth 5
$manifestJson | Out-File -FilePath $ManifestPath -Encoding utf8

Write-Host "============================================================" -ForegroundColor Green
Write-Host "  Git Bundle Created and Verified Successfully!" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host "Bundle:   $BundlePath ($([math]::Round($bundleSize/1MB, 2)) MB)"
Write-Host "Checksum: $ShaPath ($sha256)"
Write-Host "Manifest: $ManifestPath"
Write-Host ""
