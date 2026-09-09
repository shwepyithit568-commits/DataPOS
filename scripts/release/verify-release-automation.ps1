#Requires -Version 5.1
[CmdletBinding()]
param(
    [string]$TestOutputDir = "dist/testing-release-snapshots"
)

$ErrorActionPreference = "Stop"

$ProjectRoot = Resolve-Path "$PSScriptRoot/../.."
Set-Location $ProjectRoot

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  DataPOS - Release Automation Verification Suite" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan

$FullTestOutputDir = Join-Path $ProjectRoot $TestOutputDir
if (Test-Path $FullTestOutputDir) {
    Remove-Item $FullTestOutputDir -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $FullTestOutputDir | Out-Null

$PassedTests = 0
$FailedTests = 0

function Assert-Test([string]$Name, [scriptblock]$Condition) {
    Write-Host -NoNewline "Testing: $Name ... "
    try {
        $result = & $Condition
        if ($result) {
            Write-Host "PASS" -ForegroundColor Green
            $script:PassedTests++
        } else {
            Write-Host "FAIL (Returned false)" -ForegroundColor Red
            $script:FailedTests++
        }
    } catch {
        Write-Host "FAIL ($($_.Exception.Message))" -ForegroundColor Red
        $script:FailedTests++
    }
}

try {
    # Test 1: Scripts Exist
    Assert-Test "Release scripts exist" {
        (Test-Path "$PSScriptRoot/build-source-snapshot.ps1") -and
        (Test-Path "$PSScriptRoot/build-git-bundle.ps1")
    }

    # Test 2: Build Source Snapshot
    Write-Host "`nRunning build-source-snapshot.ps1 in test directory..." -ForegroundColor Yellow
    & "$PSScriptRoot/build-source-snapshot.ps1" -Version "9.9.9-test" -OutputDir $TestOutputDir -AllowDirty
    $zipFiles = Get-ChildItem -Path $FullTestOutputDir -Filter "*.zip"
    $shaFiles = Get-ChildItem -Path $FullTestOutputDir -Filter "*.sha256"
    $manifestFiles = Get-ChildItem -Path $FullTestOutputDir -Filter "*.manifest.json"

    Assert-Test "Source ZIP, SHA256, and Manifest generated" {
        ($zipFiles.Count -eq 1) -and ($shaFiles.Count -eq 1) -and ($manifestFiles.Count -eq 1)
    }

    $zipPath = $zipFiles[0].FullName
    $shaPath = $shaFiles[0].FullName
    $manifestPath = $manifestFiles[0].FullName

    # Test 3: Verify SHA256
    Assert-Test "Source ZIP SHA-256 matches actual bitstream" {
        $actualHash = (Get-FileHash -Path $zipPath -Algorithm SHA256).Hash.ToLower()
        $recordedHash = (Get-Content -Path $shaPath).Split(" ")[0].Trim().ToLower()
        $actualHash -eq $recordedHash
    }

    # Test 4: Verify Manifest JSON
    Assert-Test "Manifest JSON contains correct Git HEAD SHA and metadata" {
        $manifest = Get-Content -Path $manifestPath -Raw | ConvertFrom-Json
        $headSha = (git rev-parse HEAD).Trim()
        ($manifest.git_commit -eq $headSha) -and
        ($manifest.app -eq "DataPOS") -and
        ($manifest.version -eq "9.9.9-test") -and
        ($manifest.type -eq "source_release_snapshot")
    }

    # Test 5: Verify Archive Contents
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
    try {
        $entries = $zip.Entries | ForEach-Object { $_.FullName }

        Assert-Test "Archive includes composer.lock and package-lock.json" {
            $hasComp = $entries | Where-Object { $_ -match "composer\.lock$" }
            $hasPkg = $entries | Where-Object { $_ -match "package-lock\.json$" }
            ($null -ne $hasComp) -and ($null -ne $hasPkg)
        }

        Assert-Test "Archive includes .env.example" {
            $hasEnvEx = $entries | Where-Object { $_ -match "\.env\.example$" }
            $null -ne $hasEnvEx
        }

        Assert-Test "Archive strictly EXCLUDES real .env files" {
            $prohibitedEnv = $entries | Where-Object { $_ -match '\.env$' -and $_ -notmatch '\.env\.example$' }
            $null -eq $prohibitedEnv
        }

        Assert-Test "Archive strictly EXCLUDES SQLite databases (*.sqlite)" {
            $prohibitedDb = $entries | Where-Object { $_ -match '\.sqlite' }
            $null -eq $prohibitedDb
        }

        Assert-Test "Archive strictly EXCLUDES vendor/ and node_modules/" {
            $prohibitedVendor = $entries | Where-Object { $_ -match '^[^/]+/vendor/' -or $_ -match '^[^/]+/node_modules/' }
            $null -eq $prohibitedVendor
        }

        Assert-Test "Archive strictly EXCLUDES logs and private keys" {
            $prohibitedKeys = $entries | Where-Object { $_ -match '\.(pem|ppk|key)$' -or $_ -match '\.log$' }
            $null -eq $prohibitedKeys
        }

        Assert-Test "Archive honors .gitattributes (sensitive SQL dumps excluded)" {
            $prohibitedSql = $entries | Where-Object { $_ -match 'manual_.*\.sql' }
            $null -eq $prohibitedSql
        }
    } finally {
        $zip.Dispose()
    }

    # Test 6: Build Git Bundle
    Write-Host "`nRunning build-git-bundle.ps1 in test directory..." -ForegroundColor Yellow
    & "$PSScriptRoot/build-git-bundle.ps1" -Version "9.9.9-test" -OutputDir $TestOutputDir -AllowDirty
    $bundleFiles = Get-ChildItem -Path $FullTestOutputDir -Filter "*.bundle"

    Assert-Test "Git Bundle generated" {
        $bundleFiles.Count -eq 1
    }

    $bundlePath = $bundleFiles[0].FullName

    # Test 7: Verify Git Bundle Integrity
    Assert-Test "Git bundle verified via git-bundle-verify" {
        $prevEAP = $ErrorActionPreference
        $ErrorActionPreference = "SilentlyContinue"
        $null = git bundle verify $bundlePath 2>&1
        $ec = $LASTEXITCODE
        $ErrorActionPreference = $prevEAP
        $ec -eq 0
    }

    # Test 8: Verify Git Bundle SHA256
    Assert-Test "Git bundle SHA256 matches actual bitstream" {
        $bundleHash = (Get-FileHash -Path $bundlePath -Algorithm SHA256).Hash.ToLower()
        $bundleShaFile = Join-Path $FullTestOutputDir ($bundleFiles[0].BaseName + ".sha256")
        $recordedBundleHash = (Get-Content -Path $bundleShaFile).Split(" ")[0].Trim().ToLower()
        $bundleHash -eq $recordedBundleHash
    }

    # Test 9: Dirty Worktree Refusal
    Assert-Test "Dirty worktree refuses release snapshot without AllowDirty" {
        # Check git status
        $status = git status --porcelain
        if ($status) {
            # Since worktree is currently dirty, running without -AllowDirty should throw/fail
            try {
                & "$PSScriptRoot/build-source-snapshot.ps1" -Version "9.9.9-fail" -OutputDir $TestOutputDir
                $false # should have thrown
            } catch {
                $true # caught expected preflight failure
            }
        } else {
            # Working tree is clean; temporarily touch an untracked file to test
            $dummy = Join-Path $ProjectRoot "temp_dirty_test.tmp"
            New-Item -ItemType File -Path $dummy | Out-Null
            try {
                try {
                    & "$PSScriptRoot/build-source-snapshot.ps1" -Version "9.9.9-fail" -OutputDir $TestOutputDir
                    $false
                } catch {
                    $true
                }
            } finally {
                if (Test-Path $dummy) { Remove-Item $dummy -Force }
            }
        }
    }

} finally {
    Write-Host "`nCleaning up test artifacts in $FullTestOutputDir..." -ForegroundColor Yellow
    if (Test-Path $FullTestOutputDir) {
        Remove-Item $FullTestOutputDir -Recurse -Force
    }
}

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  Results: $PassedTests Passed, $FailedTests Failed" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Cyan

if ($FailedTests -gt 0) {
    exit 1
}
