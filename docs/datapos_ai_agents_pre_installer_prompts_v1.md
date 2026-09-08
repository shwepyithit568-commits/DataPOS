# DataPOS — AI Agents Pre-Installer Prompts v1

## အသုံးပြုရန် ရည်ရွယ်ချက်

ဤ prompts များသည် DataPOS ကို Windows EXE/Offline Installer မတည်ဆောက်မီ AI Agents ဖြင့်လုပ်နိုင်သည့် technical verification၊ documentation refresh၊ CI၊ security audit၊ release snapshot automation နှင့် installer plan correction များအတွက်ဖြစ်သည်။

Repository:

```text
https://github.com/shwepyithit568-commits/DataPOS
```

Audit စစ်ဆေးခဲ့ချိန် GitHub `main` latest commit:

```text
d8f7abecfbe74fade6fe2ff06084fd609b0f8252
```

Agent တစ်ယောက်ပြီးမှ နောက် Agent ကိုစပါ။ တစ်ပြိုင်နက်တည်းမလုပ်ပါနှင့်။ Agent တစ်ယောက်စီသည် မစမီ latest `main` ကို pull/fetch လုပ်ပြီး actual HEAD ကို report လုပ်ရမည်။ အထက်ပါ SHA ကို hardcode source of truth အဖြစ် မယူဘဲ လက်ရှိ GitHub `main` HEAD ကိုစစ်ပြီး အသုံးပြုရမည်။

---

## အားလုံးလိုက်နာရမည့် Common Rules

Prompt တစ်ခုချင်းစီအောက်တွင် အောက်ပါစည်းကမ်းများ အကျုံးဝင်သည်—

1. Repository ရှိ `AGENTS.md`၊ `README.md`၊ Source of Truth documents၊ `docs/myanmar_business_commercial_readiness_plan_v1.md`၊ `docs/phase_f_completion_report.md` နှင့် `docs/windows_offline_installer_plan_v1.md` ရှိပါက အပြည့်အစုံဖတ်ပါ။
2. Existing unrelated changes ကို မဖျက်၊ reset၊ overwrite သို့မဟုတ် commit မလုပ်ပါနှင့်။
3. `git reset --hard`၊ `git clean -fd`၊ `migrate:fresh`၊ production database reset စသည့် destructive command မသုံးပါနှင့်။
4. Real `.env`၊ credentials၊ APP_KEY၊ customer database၊ backup contents သို့မဟုတ် secret values ကို output/report/GitHub မှာ မဖော်ပြပါနှင့်။
5. Test မ run နိုင်ခြင်းကို PASS မရေးပါနှင့်။ Automated test ကို physical hardware/manual UAT အဖြစ် မရေးပါနှင့်။
6. လူကိုယ်တိုင်စစ်ဆေးရန်လိုသော အရာတိုင်းကို `PENDING — Human Verification` ဟုရေးပါ။
7. Changes မစမီ baseline test/build run ပါ။ Changes ပြီးနောက် targeted tests၊ full tests နှင့် production build ကို သင့်တော်သလို run ပါ။
8. Test counts နှင့် assertion counts ကို မရောပါနှင့်။ Exact command နှင့် exact final summary ကို report ထဲထည့်ပါ။
9. Store scope၊ permission၊ existing POS/Ecommerce/Repair workflows နှင့် existing data compatibility မပျက်ရ။
10. User-facing strings ပြင်ပါက Myanmar၊ English၊ Simplified Chinese translation key parity စစ်ပါ။
11. Agent တစ်ယောက်စီသည် မိမိ scope အတွင်းသာ commit တစ်ခု သို့မဟုတ် logically separated commits ပြုလုပ်ပါ။ Push authorization/configuration ရှိမှသာ push လုပ်ပါ။
12. Final response တွင် full commit SHA၊ changed files၊ reason၊ tests/build exact result၊ limitations နှင့် `git status --short` output ပေးပါ။

---

# Prompt 1 — Release Candidate Baseline၊ Test Evidence နှင့် GitHub CI

```text
DataPOS repository ၏ လက်ရှိ GitHub main branch ကို Windows Offline Installer မစမီ release-candidate baseline အဖြစ် ပြန်စစ်ပြီး automated verification infrastructure ကို production-ready အဆင့်သို့ ပြင်ဆင်ပါ။

Repository:
https://github.com/shwepyithit568-commits/DataPOS

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။

## Objectives

1. Current `main` full HEAD SHA၊ branch၊ remotes၊ `git status --short` ကို မှတ်တမ်းတင်ပါ။ Documentation ထဲရှိ SHA အဟောင်းကို source of truth မယူပါနှင့်။
2. Current environment နှင့် project requirements ကို inventory လုပ်ပါ—PHP၊ Composer၊ Node/npm၊ Laravel၊ SQLite၊ required PHP extensions။
3. Baseline အဖြစ် အောက်ပါတို့ကို run ပါ—
   - `composer validate --strict`
   - `php artisan about --only=environment`
   - `php artisan test`
   - `npm ci`
   - `npm run build`
4. Failing tests/build ရှိပါက root cause ရှာပြီး task scope အတွင်းရှိ regression ကိုသာ ပြင်ပါ။ Tests ကို ဖြုတ်ခြင်း၊ skip လုပ်ခြင်း၊ assertion ပျော့အောင်လုပ်ခြင်းဖြင့် green မလုပ်ပါနှင့်။
5. `.github/workflows/ci.yml` မရှိပါက ဖန်တီးပါ။ ရှိပါက လက်ရှိ architecture နှင့်ကိုက်ညီအောင် ပြင်ပါ။ CI တွင် အနည်းဆုံး—
   - Supported PHP version
   - Required PHP extensions
   - Composer install from lock file
   - Node install from lock file
   - SQLite test setup
   - Laravel key/test environment setup without real secrets
   - `php artisan test`
   - `npm run build`
   - Translation key parity test/command
   - Generated/build artifact check
   ပါရမည်။
6. CI က `database/database.sqlite` သို့မဟုတ် project ၏ canonical testing database ကို deterministic အဖြစ်စီစဉ်ရမည်။ Real/local DB မတင်ရ။
7. CI dependency cache သုံးနိုင်သော်လည်း stale build/vendor ကို source of truth မလုပ်ရ။
8. `main` latest commit အတွက် CI run ဖြစ်နိုင်စေရန် commit ပြုလုပ်ပါ။ Push ခွင့်ရှိပြီး user scope အတွင်းဖြစ်မှ push ပါ။

## Human-only Boundaries

- Physical printer/scanner test မလုပ်နိုင်ပါက PENDING ဟုရေးပါ။
- Seven-day real pilot အဖြစ် automated test ကို မရေတွက်ပါနှင့်။
- Clean Windows install စမ်းသပ်မှု မလုပ်ရသေးပါက PENDING ဟုရေးပါ။

## Deliverables

- CI workflow file
- Baseline verification report: `docs/pre_installer_automated_baseline_report.md`
- Exact test/build summaries
- Full commit SHA and changed files
- Remaining automated blockers
- Human verification pending list

ဤ task တွင် README အကြီးစား rewrite၊ installer build၊ EXE creation သို့မဟုတ် production deployment မလုပ်ပါနှင့်။
```

---

# Prompt 2 — Security၊ Secrets နှင့် Public Repository Audit

```text
DataPOS public GitHub repository ကို Windows installer/release archive မထုတ်မီ read-heavy security and secret exposure audit ပြုလုပ်ပါ။ Confirmed issues ကို scope အတွင်း လုံခြုံစွာပြင်နိုင်ပါက ပြင်ပါ။ Git history rewrite၊ credential rotation သို့မဟုတ် repository visibility ပြောင်းခြင်းကို explicit Project Owner approval မရှိဘဲ မလုပ်ပါနှင့်။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1 ပြီးဆုံးသော latest main ကိုအသုံးပြုပါ။

## Audit Scope

1. Current files နှင့် full Git history ကို secret scanner ဖြင့်စစ်ပါ။ Tool မရှိပါက documented alternatives သုံးပါ။ Secret raw values ကို terminal report/final response တွင် မဖော်ပြရ။ File path၊ commit၊ secret type နှင့် remediation status ကို redact လုပ်ပြီးသာ report လုပ်ပါ။
2. အနည်းဆုံး အောက်ပါတို့ကိုစစ်ပါ—
   - `.env*`
   - APP_KEY
   - DB credentials
   - SMTP/API/payment keys
   - VAPID/web-push keys
   - SSH/private keys
   - Access tokens
   - Real phone/email/customer data
   - SQLite databases
   - Backup ZIPs
   - Uploaded vouchers/images containing private information
3. `.gitignore` သည် `.env`၊ databases၊ backups၊ logs၊ keys၊ `vendor`၊ `node_modules`၊ local Excel files နှင့် build/temp artifacts ကို သင့်တော်စွာ exclude လုပ်ကြောင်းစစ်ပါ။
4. Already tracked sensitive artifact ရှိမရှိ `git ls-files` ဖြင့်စစ်ပါ။ Ignore rule ရှိရုံဖြင့် already tracked file မလုံခြုံကြောင်း report လုပ်ပါ။
5. Production/UAT seeders ထဲတွင် default passwords၊ known PINs နှင့် test accounts မတော်တဆ commercial build ထဲမဝင်စေရန် စစ်ပါ။
6. Installer/release archive exclusion policy ရေးပါ။
7. Dependencies အတွက်—
   - `composer audit`
   - `npm audit --omit=dev` သို့မဟုတ် project-compatible command
   run ပြီး findings ကို severity အလိုက် report လုပ်ပါ။ Safe compatible updates မဟုတ်ပါက version bump မလုပ်ဘဲ remediation plan ရေးပါ။
8. License inventory ပြုလုပ်ပါ—PHP runtime၊ Laravel dependencies၊ PHPSpreadsheet၊ html2pdf.js၊ Noto Sans Myanmar၊ installer tooling။ Redistribution restriction မသေချာပါက Unknown/Pending Legal Review ဟုရေးပါ။

## Deliverables

- `docs/security_and_release_archive_audit.md`
- Sanitized secret findings table
- Required credential rotation checklist for Project Owner
- Installer/source ZIP inclusion-exclusion matrix
- Dependency audit results
- Third-party license manifest draft
- Changed files and commit SHA

Real credentials ကို ကိုယ်တိုင် rotate မလုပ်ပါနှင့်။ Git history ကို approval မရှိဘဲ rewrite/force-push မလုပ်ပါနှင့်။
```

---

# Prompt 3 — Runtime၊ SQLite Database နှင့် Writable Storage Canonicalization

```text
DataPOS ၏ local Windows deployment architecture တွင် documentation/configuration မကိုက်ညီမှုများကို audit ပြီး installer မစမီ canonical runtime layout ကို implementation-ready အဖြစ်သတ်မှတ်ပါ။ Safe and backward-compatible code/config changes လိုအပ်ပါက tests ဖြင့်ပြင်ပါ။ Installer/EXE ကို မတည်ဆောက်သေးပါနှင့်။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1 နှင့် Prompt 2 ပြီးသော latest main ကိုသုံးပါ။

## Known Questions to Resolve from Repository Evidence

1. README တွင် local SQLite path `database/database.sqlite` ဖြစ်သော်လည်း installer plan တွင် `storage/database/datapos.sqlite` ဟုရှိသည်။ Application အမှန်တကယ်သုံးနေသော config၊ `.env.example`၊ migrations၊ tests၊ backup service နှင့် restore service ကိုစစ်ပြီး canonical path တစ်ခုသတ်မှတ်ပါ။
2. Backward compatibility မပျက်စေရန် existing database migration/move/fallback behavior လိုမလို ဆုံးဖြတ်ပါ။ Existing DB ကို silent overwrite မလုပ်ရ။
3. Program binaries နှင့် writable data ကိုခွဲပါ—
   - Application/runtime location
   - Database
   - Uploads/media
   - Logs/cache/sessions
   - Backups
   - Config/secrets
4. `C:\Program Files\DataPOS` နှင့် `%PROGRAMDATA%`/`%LOCALAPPDATA%` တို့၏ Windows permissions implications ကို စစ်ပြီး single-user v1 အတွက် ရွေးချယ်မှုတစ်ခုကို evidence နှင့်ဆုံးဖြတ်ပါ။
5. Backup/restore သည် canonical DB/media paths ကိုသာသုံးကြောင်း automated tests ဖြင့်အတည်ပြုပါ။
6. SQLite WAL၊ busy timeout၊ foreign keys၊ transactions နှင့် abrupt shutdown recovery config ကိုစစ်ပါ။ Unsupported claim မရေးပါနှင့်။
7. PHP built-in server launcher plan အတွက်—
   - loopback-only binding
   - port collision/fallback
   - PID/process ownership
   - stale process cleanup
   - health check
   - graceful shutdown
   - multiple launcher clicks
   - log rotation
   တို့ကို technical design အဖြစ်ရေးပါ။
8. Current `server.php`/front-controller compatibility ရှိမရှိ code ဖြင့်စစ်ပါ။ မရှိသော file ကို plan ထဲမညွှန်းရ။

## Required Tests

- Canonical SQLite path test
- Existing DB preserved test
- Fresh DB initialization test
- Backup includes canonical DB/media test
- Restore returns data to canonical location test
- Writable directory validation test
- Missing/unwritable directory graceful error test
- WAL/foreign key configuration test

## Deliverables

- `docs/windows_runtime_and_storage_architecture.md`
- Updated configuration/tests where required
- Database path migration/backward-compatibility design
- Final path matrix
- Exact test/build results
- Commit SHA and changed files

Do not create installer script၊ launcher EXE၊ Windows scheduled tasks သို့မဟုတ် production package in this task.
```

---

# Prompt 4 — README.md Full Rewrite from Current Repository

```text
DataPOS repository ၏ root `README.md` သည် project စတင်ခါစက အချက်အလက်များနှင့် stale environment details ပါနေသောကြောင့် လက်ရှိ codebase ကို source of truth အဖြစ်အသုံးပြုပြီး README ကို အပြည့်အစုံပြန်ရေးပါ။ README ကို marketing claim မဟုတ်ဘဲ developer၊ tester၊ future maintainer နှင့် installer builder တို့အတွက် တိကျသော entry point ဖြစ်အောင်ရေးပါ။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1–3 ပြီးဆုံးသော latest main ကိုသုံးပါ။

## Mandatory Audit Before Writing

- `composer.json` / `composer.lock`
- `package.json` / lock file
- `.env.example`
- `config/database.php` and runtime configuration
- Routes, modules and middleware
- Migrations/seeders
- Tests
- Backup/restore
- Offline assets/PWA
- Reports/import/export/PDF/printing
- Roles/permissions
- Current docs map
- Current CI workflow

README အဟောင်းကို စကားလုံးပြောင်းရုံ မလုပ်ပါနှင့်။ Current implementation ကို evidence ဖြင့် inventory ပြီး လုံးဝပြန်တည်ဆောက်ပါ။

## Required README Sections

1. DataPOS အကြောင်းအကျဉ်း
2. Current release/readiness status
3. Exact technology stack and supported versions
4. Supported business modes—POS-only၊ Online + Offline၊ optional modules; code က support လုပ်သလောက်သာရေးရန်
5. Implemented module inventory
6. Known partial/deferred modules
7. Local development requirements
8. Windows local/UAT run instructions
9. Canonical database/storage paths
10. Environment setup from `.env.example`
11. Install dependencies/build/migrate commands
12. Safe UAT seed/demo setup
13. Default test accounts—ရှိပါက UAT-only ဟုရှင်းပြပြီး commercial build မှ exclude လုပ်ရန်
14. Automated test and CI commands
15. Backup/restore workflow
16. XLSX/CSV/PDF/printing summary
17. Offline vs sales-channel terminology
18. Security rules and secret handling
19. Destructive commands that must not run on production
20. Documentation index with repository-relative links
21. Current human-verification pending list
22. Installer status and prerequisites
23. Contribution/development rules
24. License/status—အတည်မပြုရသေးပါက proprietary/undecided ကို မခန့်မှန်းဘဲ project owner confirmation required ဟုရေးရန်

## README Accuracy Rules

- Absolute Windows `file:///D:/...` links မသုံးပါနှင့်။ GitHub-compatible relative links သုံးပါ။
- Port `8501`/`8502` စသည့်မကိုက်ညီမှုများကို repository config နဲ့ဖြေရှင်းပြီး canonical value တစ်ခုရေးပါ။
- Tests PASS count ကို current command run မရှိဘဲ hardcode မလုပ်ပါနှင့်။
- “Fully tested”, “production-ready”, “seven-day pilot passed”, “hardware supported” စသည့် claims ကို evidence မရှိဘဲ မရေးပါနှင့်။
- Automated/Manual/Physical status ကို သီးခြားရေးပါ။
- README တွင် real secret၊ real customer data သို့မဟုတ် production credentials မပါရ။

## Verification

- All Markdown links resolve
- Commands match actual project files
- Module names match routes/controllers
- Documentation paths exist
- README statements do not conflict with current completion report

## Deliverables

- Rewritten root `README.md`
- Optional `docs/README_AUDIT_NOTES.md` with removed stale claims and reasons
- Exact verification results
- Full commit SHA and changed files

ဤ task တွင် application business logic၊ migrations၊ installer သို့မဟုတ် production deployment မပြင်ပါနှင့်။ README accuracy ကိုထိခိုက်သော critical code mismatch တွေ့ပါက blocker အဖြစ် report လုပ်ပါ။
```

---

# Prompt 5 — Phase F Completion Report Refresh and Evidence Correction

```text
`docs/phase_f_completion_report.md` ကို current GitHub main HEAD နှင့် actual test evidence အပေါ်အခြေခံပြီး ပြန်စစ်၊ ပြန်ရေးပါ။ Report အဟောင်းထဲက stale SHA၊ uncommitted changes၊ exaggerated automated/manual claims နှင့် inconsistent test counts ကို ပြင်ပါ။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1–4 ပြီးဆုံးသော latest main ကိုသုံးပါ။

## Mandatory Corrections

1. Current full HEAD SHA နှင့် clean/dirty status ကို actual commands ဖြင့် update ပါ။
2. `32 modified + 19 untracked` ကဲ့သို့ stale data ဖယ်ရှားပါ။ Current values သာရေးပါ။
3. Full test suite ကို fresh run လုပ်ပြီး tests၊ assertions၊ failures၊ skipped နှင့် duration ကို မရောဘဲရေးပါ။
4. `npm ci` နှင့် `npm run build` exact result ထည့်ပါ။
5. GitHub CI run/status ရှိပါက URL/status ထည့်ပါ။ မရှိ/မrunနိုင်ပါက Pending ဟုရေးပါ။
6. Automated test က route rendering/CDN regex စစ်ထားခြင်းသာဖြစ်ပါက `Automated Offline Readiness Test` ဟုသာရေးပါ။ `Seven-Day Physical Offline Pilot` ကို PENDING ထားပါ။
7. Simulated exception/transaction rollback test ကို `Simulated Crash/Atomicity Test` ဟုရေးပါ။ Physical power cut test ကို PENDING ထားပါ။
8. ESC/POS byte-generation test ကို automated ဟုရေးပြီး physical 58mm/80mm output ကို PENDING ထားပါ။
9. Automated backup/restore test နှင့် clean second-PC restore ကို သီးခြားခွဲပါ။ Physical clean-PC restore ကို PENDING ထားပါ။
10. Browser UAT၊ printer၊ scanner၊ A4 print နှင့် human usability ကို PENDING ထားပါ။
11. Known limitations ထဲမှ P0/P1 blockers ကို “does not block” ဟုခန့်မှန်းမရေးဘဲ Installer Pilot / Commercial GA gate အလိုက်ခွဲပါ။
12. Database/runtime path decision ကို Prompt 3 ရလဒ်အတိုင်း update ပါ။
13. Documentation links ကို repository-relative links ပြောင်းပါ။

## Final Gate Categories

- PASS — Automated Evidence
- PASS — Human/Physical Evidence
- PENDING — Human Verification
- BLOCKED
- DEFERRED — Explicitly Approved Scope

## Deliverables

- Updated `docs/phase_f_completion_report.md`
- Evidence-to-claim matrix
- Remaining human checklist (မလုပ်သေးပါ)
- Installer pilot blockers
- Commercial GA blockers
- Exact test/build output
- Full commit SHA and changed files

Project Owner signature/approval ကို Agent ကိုယ်တိုင် မဖြည့်ရ။ Human UAT ကို PASS မလုပ်ရ။
```

---

# Prompt 6 — Safe Source ZIP and Git Bundle Automation

```text
DataPOS ကို installer မတည်ဆောက်မီ recoverable release snapshot အဖြစ် သိမ်းနိုင်ရန် source ZIP၊ Git bundle နှင့် checksum generation automation ကိုတည်ဆောက်ပါ။ Customer data backup ZIP နှင့် source release ZIP ကို လုံးဝခွဲထားရမည်။ Installer မတည်ဆောက်သေးပါနှင့်။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။ Prompt 1–5 ပြီးဆုံးသော latest main ကိုသုံးပါ။

## Required Artifacts and Scripts

1. `scripts/release/build-source-snapshot.ps1`
   - Clean Git worktree မဟုတ်ပါက fail
   - Current tag/version/full SHA capture
   - Tracked source files မှသာ ZIP တည်ဆောက်
   - `.env`၊ database၊ backups၊ uploads၊ logs၊ keys၊ caches၊ `vendor`၊ `node_modules`၊ local spreadsheets ကို exclude
   - `composer.lock` နှင့် npm lock file ကို include
   - SHA-256 checksum ထုတ်
   - Manifest JSON/Markdown ထုတ်
2. `scripts/release/build-git-bundle.ps1`
   - Branches/tags/history ပါဝင်သည့် Git bundle ထုတ်
   - `git bundle verify` run
   - SHA-256 checksum ထုတ်
3. `docs/release_snapshot_and_backup_guide.md`
   - Source ZIP
   - Git bundle
   - Customer data backup ZIP
   - Installer artifact
   တို့၏ ရည်ရွယ်ချက်နှင့် restore procedure ကို သီးခြားရှင်းပြပါ။

## Security Rules

- Build output ကို repository အတွင်း commit မလုပ်ရ။ Output directory ကို gitignored ထားပါ။
- Secret scan မအောင်မြင်ပါက source snapshot build ကို block လုပ်နိုင်သည့် preflight design ထားပါ။
- Real database/customer backup ကို source ZIP ထဲဘယ်တော့မှမထည့်ရ။
- Source ZIP ကို installer bundle ဟုမခေါ်ရ။
- Git bundle ကို public customer distribution မလုပ်ရ။
- Archive names တွင် semantic version၊ short SHA နှင့် build date ပါရမည်။

## Tests

- Generated ZIP inclusion/exclusion assertions
- `.env`/SQLite/key/log absence assertions
- Required lock files/source presence
- Manifest SHA equals Git HEAD
- Checksum verification
- Git bundle verification
- Dirty worktree refuses release snapshot

## Deliverables

- Release snapshot scripts
- Documentation
- Automated tests or safe verification script
- Example filenames only; generated large archives ကို Git commit မလုပ်ရ
- Exact verification output
- Full commit SHA and changed files
```

---

# Prompt 7 — Windows Installer Plan v2 Correction Only

```text
လက်ရှိ `docs/windows_offline_installer_plan_v1.md` ကို implementation မစဘဲ repository evidence၊ Prompt 1–6 outputs နှင့် updated Phase F report အပေါ်အခြေခံ၍ `docs/windows_offline_installer_plan_v2.md` အဖြစ် ပြန်ရေးပါ။ v1 ကို history/reference အဖြစ်မဖျက်ပါနှင့်။

အလုပ်မစမီ Common Rules အားလုံးလိုက်နာပါ။

## Required Corrections

1. Canonical application/runtime/database/uploads/logs/backups/config paths ကို Prompt 3 အတိုင်းသုံးပါ။
2. `C:\DataPOS` နှင့် `{autopf}\DataPOS` conflict ကိုဖြေရှင်းပါ။ Program files နှင့် writable data ကိုခွဲပါ။
3. “No admin required”၊ Windows Task Scheduler၊ Program Files ACL၊ startup behavior တို့၏ privileges ကို တိတိကျကျသတ်မှတ်ပါ။
4. PHP runtime version ကို vague/hardcoded မထားဘဲ supported pinned patch version၊ architecture၊ checksum၊ download source နှင့် license verification plan ထည့်ပါ။
5. Required PHP extensions ကို `composer check-platform-reqs` နှင့် current code requirements ကနေ inventory ပြုလုပ်ပါ။
6. PHP built-in server/launcher ကို v1 pilot single-user local app အဖြစ်သာသတ်မှတ်ပြီး process supervision၊ health check၊ port collision၊ duplicate launch၊ graceful shutdown နှင့် log rotation ထည့်ပါ။
7. `server.php` သို့မဟုတ် router file ကို plan မှာသုံးပါက repository ထဲအမှန်တကယ်ရှိ/ဖန်တီးမည့် deliverable ဖြစ်ကြောင်းရှင်းပါ။
8. Database initialization တွင် generic `--seed` မသုံးဘဲ production-safe seeder သီးခြားသတ်မှတ်ပါ။ UAT users/default password/PIN မပါရ။
9. Fresh APP_KEY generation၊ environment creation၊ file permissions နှင့် uninstall data preservation ကို အသေးစိတ်ရေးပါ။
10. Upgrade transaction တွင် pre-update backup၊ version compatibility၊ migration failure rollback နှင့် old binary restore plan ပါရမည်။
11. Auto-backup time ကို hardcode 02:00 AM မထားဘဲ first-run/admin setting မှရွေးနိုင်ရန် စဉ်းစားပါ။ PC ပိတ်နေချိန် missed task behavior ထည့်ပါ။
12. Source ZIP၊ Git bundle၊ customer backup နှင့် installer artifact ကိုမရောရ။
13. Installer file size estimate ကို actual staging build မရှိသေးသရွေ့ estimate ဟုရေးပါ။ UPX ကို blind compression မသုံးဘဲ compatibility/security/antivirus risk review ထည့်ပါ။
14. Code signing ကို pilot နှင့် commercial GA အလိုက်ခွဲပါ။ Self-signed certificate က customer SmartScreen trust မပေးနိုင်ကြောင်းရှင်းပါ။
15. Windows 10/11 “fully tested” ဟု physical/VM evidence မရှိဘဲ မရေးပါနှင့်။ Status ကို Planned/Pending/Automated/Physical အဖြစ်ခွဲပါ။
16. Installer build မစမီ Human UAT pending items ကို gate အဖြစ် ဆက်ထားပါ။

## Required v2 Sections

- Decision log
- Runtime and storage architecture
- First-run and production-safe seed design
- Launcher lifecycle/state machine
- Backup/update/rollback
- Security and secrets
- Code signing and checksums
- Source/release artifact taxonomy
- VM automated installer tests
- Physical PC/hardware tests
- Pilot vs Commercial GA release gates
- Owner open decisions
- Approval checkpoint

## Deliverables

- `docs/windows_offline_installer_plan_v2.md`
- v1-to-v2 correction table
- Unresolved owner decisions
- No installer/EXE implementation
- Full commit SHA and changed files

Project Owner approval မရမီ Inno Setup script၊ launcher executable၊ PHP runtime bundle သို့မဟုတ် final installer မဖန်တီးပါနှင့်။
```

---

# Prompt 8 — Final Cross-Check Agent

```text
Prompt 1–7 agents အားလုံးပြီးဆုံးပြီးနောက် DataPOS pre-installer work ကို read-only final cross-check ပြုလုပ်ပါ။ အဓိက code/business logic မပြင်ပါနှင့်။ Documentation-only factual corrections လိုပါက သီးခြား commit ပြုလုပ်နိုင်သည်။

## Verify

1. GitHub main latest full SHA and clean status
2. CI workflow exists and latest run status
3. Full tests and production build evidence
4. README accuracy and working relative links
5. Canonical database/storage paths are consistent across code, README, backup/restore and installer v2 plan
6. Completion report uses current SHA and honest evidence categories
7. Automated tests are not mislabeled as physical/manual tests
8. Security audit and credential-rotation checklist exist
9. Source snapshot/Git bundle scripts exclude secrets and customer data
10. Installer v2 remains unimplemented and awaits Project Owner approval
11. Human UAT, physical printer/scanner, clean-PC restore, real power-cut and seven-day offline pilot remain PENDING

## Output

Create `docs/pre_installer_ai_work_final_review.md` containing:

- PASS/FAIL/PENDING matrix
- Critical blockers
- Non-blocking limitations
- Human tasks for next day
- Exact Git/CI/test/build references
- Recommendation: Ready or Not Ready for Installer Implementation

Do not mark Ready unless every automated gate is evidenced and all explicitly required human gates are either completed or clearly retained as blockers awaiting Project Owner action.
```

---

## အလုပ်ခိုင်းရန် အကြံပြုအစီအစဉ်

| Order | Agent | Scope | Code Change |
| ---: | --- | --- | --- |
| 1 | Baseline & CI | Tests/build/GitHub verification | CI/fixes only |
| 2 | Security | Secrets/dependencies/licenses | Minimal security fixes |
| 3 | Runtime & Storage | SQLite/writable paths/backup consistency | Config/tests if required |
| 4 | README | Current project documentation | README/docs only |
| 5 | Phase F Report | Evidence correction | Report only |
| 6 | Release Archives | Source ZIP/Git bundle automation | Scripts/docs/tests |
| 7 | Installer Plan v2 | Correct plan only | Docs only |
| 8 | Final Reviewer | Cross-check | Read-only/docs correction |

## နောက်နေ့ လူကိုယ်တိုင်လုပ်ရန် ချန်ထားရမည့်အရာများ

- Browser UAT workflow
- 58mm/80mm thermal printer အစစ်
- A4 printer
- USB/Bluetooth scanner
- Cash drawer
- Clean Windows 10/11 installation
- Backup ကို ဒုတိယကွန်ပျူတာတွင် restore
- Real Windows restart/process recovery
- Physical power-loss test
- Seven-day no-internet pilot
- Store Owner sign-off

AI Agent မည်သူမျှ အထက်ပါ human tasks မလုပ်ရသေးလျှင် PASS ဟုမရေးရ။
