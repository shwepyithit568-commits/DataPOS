# DataPOS — README Audit Notes (Prompt 4)

**Audit Date:** 2026-09-09  
**Target File:** `README.md`  
**Purpose:** Record removed stale claims, corrected paths, updated commands, and architectural alignments during the full README rewrite.

---

## 1. Stale Claims Removed & Reasons

| Stale Claim in Old README | Correction / New Statement | Reason |
| :--- | :--- | :--- |
| **Absolute Local Paths:** `[...](D:/xmapp/htdocs/DataPOS/...)` used throughout links. | Replaced with repository-relative paths (`docs/...`, `Source_of_Truth_MM.md`, etc.). | Absolute Windows drive paths break navigation on GitHub, CI, and other developers' machines. |
| **Database Path:** Stated exclusively as `database/database.sqlite`. | Updated to canonical path `storage/database/datapos.sqlite` with automatic backward-compatible fallback to `database/database.sqlite`. | Prompt 3 canonicalization separated read-only binaries from writable data to prevent Windows permissions failures. |
| **Outdated "Recommended Next Phase":** Recommended building demo presets, one-click backup/restore, and POS workflows. | Removed and replaced with current Phase F / Pre-Installer status. | Demo preset switcher, one-click backup/restore, POS sale/return/reconciliation, and debt management were already built and verified in Phases A through E. |
| **Missing CI & Automated Baseline:** No mention of GitHub Actions CI or test suite assertion counts. | Added GitHub Actions CI workflow, exact test metrics (**1,743 passed, 1 skipped, 0 failed, 7,966 assertions**). | Baseline established in Prompt 1 must be transparently communicated to contributors and maintainers. |
| **Port Inconsistency:** Mentioned `8501` and `8502` without clear precedence. | Standardized on canonical port `8501` as primary, with `8502` as automatic fallback. | Clean configuration alignment across `.env.example`, documentation, and launcher design. |
| **Lack of Module Transparency:** Only summarized broad module categories. | Expanded into a comprehensive 22+ implemented module inventory alongside known deferred modules. | Prevents confusion regarding what is currently production-tested vs. future roadmap. |
| **Human vs. Automated Test Blur:** Did not explicitly define which hardware items require physical testing. | Added dedicated **Human Verification Pending List** (printers, scanners, cash drawers, clean PC restore, 7-day pilot). | Adherence to Strict Engineering Craftsmanship Policy in `AGENTS.md`. |
| **Missing Tri-lingual Standards:** Did not mention translation key parity for Myanmar, English, and Simplified Chinese. | Added Tri-Lingual Language Invariance policy from `AGENTS.md` v4.1. | Ensures future contributors maintain 3-language translation parity. |

---

## 2. Evidence Verification Summary

- **Composer Requirements:** PHP `^8.2`, Laravel `^12.0`, Livewire `^4.3`, PhpSpreadsheet `^5.9`, Webpush `^11.0`.
- **Node Packages:** Vite `^7.0.7`, TailwindCSS `^4.3.3`, Alpine.js `^3.15.12`, html2pdf.js `^0.14.0`.
- **Test Metrics:** Total 1,744 tests; 1,743 passed, 1 skipped (`MysqlMigrationSmokeTest` when MySQL absent), 7,966 assertions, duration ~146s.
- **Routes & Controllers:** All module names cross-referenced against active routes in `routes/web.php` and `bootstrap/app.php`.
