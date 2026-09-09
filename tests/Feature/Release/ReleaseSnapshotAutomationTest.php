<?php

namespace Tests\Feature\Release;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReleaseSnapshotAutomationTest extends TestCase
{
    /**
     * 1. Verifies that all required release scripts exist.
     */
    public function test_release_automation_scripts_exist(): void
    {
        $this->assertFileExists(base_path('scripts/release/build-source-snapshot.ps1'));
        $this->assertFileExists(base_path('scripts/release/build-git-bundle.ps1'));
        $this->assertFileExists(base_path('scripts/release/verify-release-automation.ps1'));
    }

    /**
     * 2. Verifies that .gitattributes has proper export-ignore directives
     * to protect against secret/data leakage during git archive exports.
     */
    public function test_gitattributes_contains_strict_export_ignore_rules(): void
    {
        $gitattributesPath = base_path('.gitattributes');
        $this->assertFileExists($gitattributesPath);

        $content = File::get($gitattributesPath);

        $this->assertStringContainsString('*.sql export-ignore', $content);
        $this->assertStringContainsString('manual_2026-08-30_015910.mysql.sql export-ignore', $content);
        $this->assertStringContainsString('storage/app/backups export-ignore', $content);
        $this->assertStringContainsString('dist export-ignore', $content);
    }

    /**
     * 3. Verifies that .gitignore contains build output directories and bundles.
     */
    public function test_gitignore_ignores_build_dist_and_bundle_artifacts(): void
    {
        $gitignorePath = base_path('.gitignore');
        $this->assertFileExists($gitignorePath);

        $content = File::get($gitignorePath);

        $this->assertStringContainsString('/dist', $content);
        $this->assertStringContainsString('*.bundle', $content);
    }

    /**
     * 4. Verifies that build-source-snapshot.ps1 script contains mandatory security preflight and exclusions.
     */
    public function test_source_snapshot_script_contains_security_guarantees(): void
    {
        $scriptPath = base_path('scripts/release/build-source-snapshot.ps1');
        $content = File::get($scriptPath);

        // Preflight & clean check
        $this->assertStringContainsString('git status --porcelain', $content);
        $this->assertStringContainsString('AllowDirty', $content);

        // Security scan
        $this->assertStringContainsString('trackedSensitive', $content);
        $this->assertStringContainsString('.env', $content);
        $this->assertStringContainsString('.sqlite', $content);

        // Required inclusion & exclusion assertions
        $this->assertStringContainsString('composer.lock', $content);
        $this->assertStringContainsString('package-lock.json', $content);
        $this->assertStringContainsString('.env.example', $content);
        $this->assertStringContainsString('--worktree-attributes', $content);
        $this->assertStringContainsString('SHA256', $content);
        $this->assertStringContainsString('manifest.json', $content);
    }

    /**
     * 5. Verifies that build-git-bundle.ps1 enforces bundle verification and sha256 checksum generation.
     */
    public function test_git_bundle_script_enforces_verification_and_checksums(): void
    {
        $scriptPath = base_path('scripts/release/build-git-bundle.ps1');
        $content = File::get($scriptPath);

        $this->assertStringContainsString('git bundle create', $content);
        $this->assertStringContainsString('git bundle verify', $content);
        $this->assertStringContainsString('SHA256', $content);
        $this->assertStringContainsString('manifest.json', $content);
    }

    /**
     * 6. Verifies that the documentation strictly separates all four artifact types.
     */
    public function test_release_documentation_defines_four_distinct_artifacts(): void
    {
        $docPath = base_path('docs/release_snapshot_and_backup_guide.md');
        $this->assertFileExists($docPath);

        $content = File::get($docPath);

        $this->assertStringContainsString('Source Release ZIP', $content);
        $this->assertStringContainsString('Git Bundle', $content);
        $this->assertStringContainsString('Customer Data Backup ZIP', $content);
        $this->assertStringContainsString('Windows Installer EXE', $content);
        $this->assertStringContainsString('STRICTLY PROHIBITED', $content);
    }
}
