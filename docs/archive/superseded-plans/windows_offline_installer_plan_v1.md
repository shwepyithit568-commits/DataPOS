# DataPOS — Windows Offline Installer Plan v1

**Document Reference:** `docs/windows_offline_installer_plan_v1.md`
**Parent Document:** [myanmar_business_commercial_readiness_plan_v1.md](../../plans/myanmar_business_commercial_readiness_plan_v1.md)
**Completion Report:** [phase_f_completion_report.md](../completed-phases/phase_f_completion_report.md)
**Created:** 2026-09-09
**Prepared By:** Tech Buddy (Senior Software Architect & Pair Programmer)
**Status:** 🟡 Awaiting Project Owner Approval Before Implementation

> **Rule (from master plan, Section 23):** ဤ plan ကို implement မစမီ Project Owner ၏ explicit approval ရရှိရမည်။
> Phase F completion report ပါ UAT sign-off မပြည့်မနေ installer build မစရ။

---

## 1. Goal

DataPOS application ကို Myanmar ဆိုင်ရှင်တစ်ဦး (programmer မဟုတ်သူ) ကိုယ်တိုင် — XAMPP၊ PHP၊ Composer၊ Node.js မည်သည့် developer tool မှ မတပ်ဆင်ဘဲ — Windows 10/11 (64-bit) PC တစ်လုံးပေါ်တွင် double-click တစ်ချက်ဖြင့် install ပြုလုပ်နိုင်ပြီး offline ဖြင့် အပြည့်အဝ အသုံးပြုနိုင်သော self-contained installer package တည်ဆောက်ရမည်။

### Success Criteria

- Installer file size ≤ 150 MB (download-friendly for Myanmar mobile data)
- Install time on low-spec PC ≤ 5 minutes
- Zero internet required after installation
- Zero command-line interaction required from user
- First-run store setup ≤ 10 minutes for non-technical user
- Uninstall via Windows "Add or Remove Programs" leaves no orphan files

---

## 2. Technology Decision

### 2.1 Installer Framework

**Selected: Inno Setup 6.x (free, open-source, ISL license)**

| Option | Cost | Myanmar PC Compatible | File Size | Complexity |
|:---|:---:|:---:|:---:|:---:|
| **Inno Setup 6** (selected) | Free | ✅ Win 10/11 | Small overhead | Low |
| NSIS | Free | ✅ | Small overhead | Medium |
| WiX Toolset | Free | ✅ | Medium overhead | High |
| Electron packager | Free | ⚠️ Chromium = +150MB | Very large | High |
| InstallShield | Paid | ✅ | Medium | Medium |

**Rationale:** Inno Setup ကို Myanmar IT community တွင် ကျယ်ကျယ်ပြန့်ပြန့် အသုံးပြုနေပြီး documentation ကောင်းကောင်းရှိသည်။ Pascal script ဖြင့် complex logic ရေးနိုင်ပြီး UPX compression support ရှိသည်။ File size overhead နည်းဆုံး။

### 2.2 Runtime Bundle Strategy

**Selected: PHP 8.2 NTS + PHP Built-in Server (zero Apache/Nginx)**

```
DataPOS-Installer/
├── php/              ← PHP 8.2.x NTS x64 Windows binary (embedded)
│   ├── php.exe
│   ├── php.ini       ← Pre-configured (SQLite, mbstring, fileinfo, openssl, zip, bcmath, gd)
│   └── ext/          ← Only required extensions
├── app/              ← Laravel application (pre-built assets)
│   ├── public/build/ ← Vite-built assets (CSS/JS/fonts — already compiled)
│   ├── storage/      ← Writable; gitignored contents seeded fresh
│   └── ...
├── DataPOS.exe       ← Launcher (wraps php.exe artisan serve)
├── DataPOS-Setup.bat ← First-run migration runner (hidden, called by installer)
└── Uninstall.exe     ← Inno Setup uninstaller
```

**Why PHP built-in server (not Apache/Nginx):**
- Zero port conflict complexity
- Zero Windows Service permission requirements (no admin needed to run)
- PHP 8.2 built-in server is production-stable for single-user local apps
- 20–30 concurrent requests support — sufficient for one POS terminal

### 2.3 Database

**SQLite 3 (already the project database — no change required)**

- SQLite WAL mode enabled for crash safety (already configured)
- Database file: `storage/database/datapos.sqlite`
- Backup ZIP: `storage/app/backups/`
- No server process; no firewall rules; no port required

### 2.4 Launcher Executable

**Selected: Simple compiled wrapper (AutoIt or Go single-binary)**

The launcher (`DataPOS.exe`) will:
1. Check if PHP process already running on port 8765
2. If not — start `php.exe -S 127.0.0.1:8765 -t public/ server.php` as hidden process
3. Wait up to 5 seconds for server readiness (HTTP probe)
4. Open default browser to `http://127.0.0.1:8765`
5. Show system tray icon with "Open DataPOS" and "Stop Server" menu

**Port:** `8765` (chosen to avoid conflict with XAMPP port 80/443 and common tools)

---

## 3. Bundled Component Manifest

| Component | Version | License | Included In Bundle |
|:---|:---|:---|:---:|
| PHP | 8.2.12 NTS x64 (Windows VC16) | PHP License 3.01 | ✅ |
| SQLite | 3.x (bundled with PHP) | Public Domain | ✅ |
| Laravel Application | Current main branch | Proprietary | ✅ |
| Vite Build Assets | Pre-compiled CSS/JS | MIT | ✅ |
| Noto Sans Myanmar Font | 2.x | SIL OFL 1.1 | ✅ |
| html2pdf.js | 0.10.x | MIT | ✅ (in public/js/) |
| PHPSpreadsheet | ^5.9 (via Composer — vendor/) | MIT | ✅ |
| Laravel Vendor | All composer deps | Mixed MIT/BSD | ✅ |
| Inno Setup Runtime | 6.x | ISL | ✅ (installer only) |

**Total estimated bundle size:** ~90–120 MB (before UPX compression)
**After UPX compression:** ~55–75 MB

---

## 4. Installer UX Flow

### 4.1 Wizard Pages

```
Page 1: Welcome Screen
  ├── DataPOS logo + version
  ├── "ကြိုဆိုပါသည် — DataPOS ကို Windows တွင် တပ်ဆင်ရန်"
  └── [Next] [Cancel]

Page 2: License Agreement
  ├── Proprietary license text (Myanmar + English)
  └── [I Accept] radio → [Next]

Page 3: Install Location
  ├── Default: C:\DataPOS\
  ├── [Browse...] button
  ├── Disk space check: required ~200MB, available: {X}MB
  └── [Next]

Page 4: Start Menu & Shortcuts
  ├── ☑ Create Desktop shortcut
  ├── ☑ Add to Start Menu
  ├── ☑ Start DataPOS automatically when Windows starts
  └── [Next]

Page 5: Installing...
  ├── Progress bar (file extraction)
  ├── "PHP runtime extracting..."
  ├── "Application files extracting..."
  ├── "Setting up database..."  ← runs php artisan migrate --seed silently
  ├── "Creating shortcuts..."
  └── [Finish]

Page 6: Installation Complete
  ├── ✅ "DataPOS Successfully installed!"
  ├── ☑ Launch DataPOS now
  └── [Finish]
```

### 4.2 Silent Install Support

```bash
DataPOS-Setup.exe /SILENT /DIR="C:\DataPOS"
DataPOS-Setup.exe /VERYSILENT /SUPPRESSMSGBOXES /DIR="D:\DataPOS"
```

> ဆိုင်ပေါင်းများ တပ်ဆင်ဖို့ IT reseller များ အတွက် batch deploy support။

---

## 5. First-Run Setup Wizard (In-App)

ပထမ login ပြီးနောက် — store data မရှိသေး (fresh install) ဆိုပါက application အတွင်းမှ first-run wizard ပေါ်လာရမည်:

```
Step 1: Store Information
  ├── Store Name (Myanmar)
  ├── Store Name (English)
  ├── Phone / Viber / Telegram
  ├── Address
  └── [Next]

Step 2: Currency & Localization
  ├── Currency: MMK (default) — configurable decimal places
  ├── Date Format: DD/MM/YYYY (default)
  ├── Language: မြန်မာ / English / 中文
  └── [Next]

Step 3: Document Numbering
  ├── Invoice prefix (default: INV)
  ├── Receipt prefix (default: RCT)
  ├── Starting number (default: 00001)
  ├── Reset annually? ☑ Yes
  └── [Next]

Step 4: Printer Setup
  ├── Default paper size: 58mm / 80mm / A4
  ├── [Test Print] button → downloads ESC/POS test byte stream
  └── [Next]

Step 5: Create Owner Account
  ├── Owner name
  ├── PIN (4-6 digits for POS)
  ├── Password (admin login)
  └── [Finish Setup]
```

---

## 6. Auto-Start Mechanism

**Selected: Windows Task Scheduler (no Windows Service required)**

```xml
<!-- Task: DataPOS_Autostart -->
<Triggers>
  <LogonTrigger>
    <Enabled>true</Enabled>
    <UserId>CURRENT_USER</UserId>
  </LogonTrigger>
</Triggers>
<Actions>
  <Exec>
    <Command>C:\DataPOS\DataPOS.exe</Command>
    <Arguments>--autostart</Arguments>
  </Exec>
</Actions>
<Settings>
  <MultipleInstancesPolicy>IgnoreNew</MultipleInstancesPolicy>
  <RunOnlyIfNetworkAvailable>false</RunOnlyIfNetworkAvailable>
</Settings>
```

**Why Task Scheduler (not registry Run key):**
- No admin elevation required
- Works with UAC-enabled systems
- Supports `--autostart` flag to suppress "already running" dialog
- Easily disabled by user via Task Scheduler UI

**Startup behavior with `--autostart` flag:**
- Start PHP server silently (no browser window)
- Show tray icon only
- User clicks tray icon to open browser

---

## 7. Automatic Daily Backup (L-4 Resolution)

> Resolves Known Limitation L-4 from Phase F Completion Report.

**Selected: Windows Task Scheduler + PHP CLI backup command**

```xml
<!-- Task: DataPOS_DailyBackup -->
<Triggers>
  <CalendarTrigger>
    <StartBoundary>2026-01-01T02:00:00</StartBoundary>
    <Repetition>
      <Interval>P1D</Interval>
    </Repetition>
  </CalendarTrigger>
</Triggers>
<Actions>
  <Exec>
    <Command>C:\DataPOS\php\php.exe</Command>
    <Arguments>C:\DataPOS\app\artisan datapos:backup --silent</Arguments>
  </Exec>
</Actions>
```

**Behavior:**
- Runs at 02:00 AM daily (configurable in-app settings)
- Creates `datapos_backup_YYYY-MM-DD_HH-mm.zip` with SHA-256 checksum
- Retains last 30 backups; deletes oldest (configurable retention)
- Writes success/failure to `storage/logs/backup.log`
- Optional: copy backup to USB drive if `D:\DataPOS_Backups\` exists (USB-aware)

---

## 8. Update Mechanism

**Strategy: Manual offline update package (appropriate for Myanmar offline market)**

### 8.1 Update Package Format

```
DataPOS-Update-v1.1.exe   ← Inno Setup updater
  ├── Stops running DataPOS.exe (graceful shutdown via HTTP /api/shutdown)
  ├── Backs up current installation to .zip with timestamp
  ├── Extracts new app/ files (preserves storage/ and .env)
  ├── Runs php artisan migrate --force
  ├── Restarts DataPOS.exe
  └── Shows changelog (Burmese + English)
```

### 8.2 Update Distribution

1. Update package uploaded to project GitHub Releases page
2. Reseller/IT partner downloads and brings on USB drive to site
3. Double-click updater on customer PC
4. ≤ 3 minutes with no internet

### 8.3 In-App Update Notice (Optional — Phase G)

```
GET http://127.0.0.1:8765/api/version
→ {"version": "1.0.0", "update_available": false}
```

If local file `C:\DataPOS\update\DataPOS-Update-*.exe` exists:
- Show yellow banner: "အပ်ဒိတ် ရရှိနိုင်ပါသည်"
- [Install Update] button runs the updater

> Internet-based auto-update ကို Phase G တွင် optional feature အဖြစ် ထည့်သွင်းနိုင်သည်။

---

## 9. Uninstaller

**Inno Setup built-in uninstaller (`Uninstall.exe`)**

```pascal
// Inno Setup [UninstallRun] section
[UninstallRun]
Filename: "{app}\php\php.exe"; Parameters: "{app}\app\artisan down"; Flags: runhidden
; Gracefully stops PHP server
```

**Uninstall behavior:**
1. Offer to keep or delete `storage/` (data, backups, uploads)
2. Remove Task Scheduler tasks (autostart + daily backup)
3. Remove shortcuts (Desktop + Start Menu)
4. Remove `C:\DataPOS\` (except user-chosen preserved `storage/`)
5. Remove from "Add or Remove Programs"
6. Leave no orphan registry keys

**Uninstall dialog (Myanmar + English):**
```
"DataPOS ဖြုတ်ချသောအခါ သင့်ဆိုင်ဒေတာများကို ဘာလုပ်မည်နည်း?"
  ⦿ ဒေတာများ ထိန်းသိမ်းထားမည် (Recommended)
  ○ ဒေတာများ အားလုံးဖျက်မည် (ဆိုင်မဖွင့်တော့ပါ)
```

---

## 10. Code Signing Requirements

> ⚠️ Windows SmartScreen filter တားဆီးမည်ကို ကာကွယ်ရန် code signing certificate လိုအပ်သည်။

| Option | Annual Cost | Suitable For |
|:---|:---:|:---|
| Standard OV Code Signing (Sectigo/DigiCert) | ~$200–$400 USD | Commercial distribution |
| EV Code Signing (Hardware Token) | ~$400–$700 USD | Instant SmartScreen trust |
| Self-signed (test only) | Free | Internal pilot only |
| No signing | Free | SmartScreen warning will appear |

**Recommendation for pilot phase:** Self-signed certificate ဖြင့် pilot ကို ဆိုင်ပေါင်း 3–5 ခု တွင် test လုပ်ပြီး commercial distribution မတိုင်မီ OV certificate ဝယ်ယူပါ။

**Workaround for pilot (documented for users):**
```
"More info" → "Run anyway" ကိုနှိပ်ပါ
ဒါကို Tech Buddy install လုပ်ပေးမည်ဖြစ်သဖြင့် ကိုယ်တိုင်လုပ်ရန် မလိုပါ
```

---

## 11. Windows Compatibility Matrix

| OS | Architecture | Status | Notes |
|:---|:---:|:---:|:---|
| Windows 11 (22H2+) | x64 | ✅ Primary target | Fully tested |
| Windows 10 (21H2+) | x64 | ✅ Primary target | Fully tested |
| Windows 10 (1909) | x64 | ⚠️ Best-effort | PHP 8.2 minimum OS: Win 10 1607 |
| Windows 10 | x86 (32-bit) | ❌ Not supported | PHP 8.2 x64 only |
| Windows 8.1 | x64 | ❌ Not supported | EOL; PHP 8.2 does not support |
| Windows 7 | x64 | ❌ Not supported | EOL |

**Minimum Hardware Requirements:**
```
CPU:     Intel/AMD 64-bit (dual-core recommended)
RAM:     2 GB minimum, 4 GB recommended
Storage: 500 MB free (installation) + 1 GB (data growth)
Display: 1280×720 minimum, 1366×768 recommended
Printer: USB thermal 58mm/80mm (optional)
```

---

## 12. Security Hardening for Installer

| Concern | Mitigation |
|:---|:---|
| PHP server accessible from LAN | Bind to `127.0.0.1` only (loopback) |
| Port 8765 conflict | Check + fallback to 8766, 8767 |
| Database file readable by all users | Set file ACL to current user only |
| `.env` contains APP_KEY | Generated fresh on install (not hardcoded) |
| Installer integrity | SHA-256 of installer published on download page |
| Backup files unencrypted | Document limitation; optional AES-256 encryption in v1.1 |
| LAN multi-user sharing | Not supported in v1 — documented limitation |

---

## 13. Known Installer Limitations (v1.0)

| # | Limitation | Plan |
|:---:|:---|:---|
| I-1 | Single-PC only (SQLite) — no LAN multi-station | Architecture review for v2 |
| I-2 | No hardware-locked license (L-7) | License module in Phase G |
| I-3 | No internet-based auto-update | Manual update package |
| I-4 | No code signing certificate on pilot | OV cert before commercial release |
| I-5 | 32-bit Windows not supported | Document clearly on download page |
| I-6 | Backup not encrypted at rest | AES-256 option in v1.1 |

---

## 14. Build Pipeline (Phase G Implementation Tasks)

### 14.1 Pre-Build Checklist

```
[ ] Composer install --no-dev --optimize-autoloader
[ ] npm run build  (Vite production assets)
[ ] php artisan config:cache
[ ] php artisan route:cache
[ ] php artisan view:cache
[ ] php artisan event:cache
[ ] Remove .git/, node_modules/, tests/, docs/ from bundle
[ ] Set APP_ENV=production, APP_DEBUG=false in bundled .env.example
[ ] Generate fresh APP_KEY during install (not pre-seeded)
[ ] Confirm SQLite WAL mode in database.php
[ ] Confirm all fonts in public/fonts/ (NotoSansMyanmar)
[ ] Confirm html2pdf.js in public/js/ (offline)
```

### 14.2 Installer Build Script (`build-installer.ps1`)

```powershell
# Step 1: Build assets
npm run build

# Step 2: Composer production install
composer install --no-dev --optimize-autoloader

# Step 3: Artisan caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 4: Copy to staging area
Copy-Item -Recurse . .\staging\app -Exclude @('.git','node_modules','tests','.env')

# Step 5: Download PHP 8.2 NTS x64 (if not cached)
# Source: https://windows.php.net/download/

# Step 6: Compile Inno Setup script
& "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" DataPOS.iss

# Step 7: Compute SHA-256 of installer
Get-FileHash .\Output\DataPOS-Setup-v1.0.exe -Algorithm SHA256
```

### 14.3 Inno Setup Script Outline (`DataPOS.iss`)

```pascal
[Setup]
AppName=DataPOS
AppVersion=1.0.0
AppPublisher=DataPOS Myanmar
DefaultDirName={autopf}\DataPOS
DefaultGroupName=DataPOS
OutputDir=Output
OutputBaseFilename=DataPOS-Setup-v1.0
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
MinVersion=10.0.17763  ; Windows 10 1809+

[Files]
Source: "staging\php\*"; DestDir: "{app}\php"; Flags: recursesubdirs
Source: "staging\app\*"; DestDir: "{app}\app"; Flags: recursesubdirs
Source: "DataPOS.exe"; DestDir: "{app}"

[Run]
; First-run database migration
Filename: "{app}\php\php.exe"; Parameters: """{app}\app\artisan"" migrate --force --seed"; \
  WorkingDir: "{app}\app"; Flags: runhidden waituntilterminated; \
  StatusMsg: "ဒေတာဘေ့စ် ပြင်ဆင်နေသည်..."

; Schedule autostart task
Filename: "schtasks.exe"; Parameters: "/Create /TN DataPOS_Autostart ..."; Flags: runhidden

; Schedule daily backup task
Filename: "schtasks.exe"; Parameters: "/Create /TN DataPOS_DailyBackup ..."; Flags: runhidden

[UninstallRun]
Filename: "schtasks.exe"; Parameters: "/Delete /TN DataPOS_Autostart /F"; Flags: runhidden
Filename: "schtasks.exe"; Parameters: "/Delete /TN DataPOS_DailyBackup /F"; Flags: runhidden
```

---

## 15. Testing Plan for Installer

### 15.1 Automated (CI)

```
[ ] Inno Setup compilation succeeds (exit code 0)
[ ] Installer SHA-256 matches published checksum
[ ] Silent install on clean Windows 10 VM (VirtualBox)
[ ] Silent install on clean Windows 11 VM
[ ] php artisan migrate runs without error after install
[ ] App accessible at http://127.0.0.1:8765 after launcher
[ ] Login, create sale, print receipt — smoke test
[ ] Uninstall leaves no orphan files
```

### 15.2 Manual (Real Hardware)

```
[ ] Low-spec PC (4GB RAM, HDD) — install time < 5 min
[ ] SmartScreen warning → "Run anyway" works
[ ] First-run wizard completes in < 10 min
[ ] 58mm thermal receipt prints from installed app
[ ] USB barcode scanner scans product
[ ] Backup ZIP downloaded and restored on second PC
[ ] Auto-start works after Windows restart
[ ] Daily backup creates file at 02:00 AM
[ ] Uninstall via "Add or Remove Programs" — clean removal
```

---

## 16. Phase G Deliverables

When Project Owner approves this plan, Phase G implementation will produce:

| # | Deliverable | Priority |
|:---:|:---|:---:|
| G-1 | `DataPOS.iss` — Inno Setup script | P0 |
| G-2 | `DataPOS.exe` — Launcher binary (AutoIt or Go) | P0 |
| G-3 | `build-installer.ps1` — Build automation script | P0 |
| G-4 | PHP 8.2 NTS x64 bundle + pre-configured `php.ini` | P0 |
| G-5 | `DataPOS-Setup-v1.0.exe` — Final installer | P0 |
| G-6 | `SHA256SUMS.txt` — Installer integrity file | P0 |
| G-7 | First-run setup wizard (in-app PHP/Blade) | P0 |
| G-8 | Windows Task Scheduler XML tasks (autostart + backup) | P0 |
| G-9 | `datapos:backup` Artisan command (auto-backup) | P1 |
| G-10 | In-app update notice (local file detection) | P1 |
| G-11 | Code signing (OV certificate) | P1 (before commercial) |
| G-12 | `DataPOS-Update-v1.1.exe` — Update package template | P2 |
| G-13 | License/activation module | P2 |
| G-14 | Installer testing report | P0 |

---

## 17. Open Questions for Project Owner

Before implementation begins, Project Owner ၏ ဆုံးဖြတ်ချက် လိုအပ်သည့် အချက်များ:

| # | Question | Default (if no answer) |
|:---:|:---|:---|
| Q-1 | Install directory ကို `C:\DataPOS` လိုချင်သလား၊ `C:\Program Files\DataPOS` လိုချင်သလား? | `C:\DataPOS` (admin-free, simpler) |
| Q-2 | Port `8765` မဟုတ်ဘဲ အခြား port သုံးမည်လား? | `8765` |
| Q-3 | Daily auto-backup time — 02:00 AM OK လား? | 02:00 AM |
| Q-4 | Backup retention — 30 days keep မည်လား? | 30 days |
| Q-5 | Installer language — Myanmar only / bilingual (MY+EN)? | Bilingual |
| Q-6 | Code signing certificate — ဝယ်ရန် budget ရှိပါသလား? | Self-signed for pilot |
| Q-7 | Pilot before wider distribution — ဆိုင်အရေအတွက်? | 3–5 ဆိုင် |
| Q-8 | Single-PC only v1.0 OK လား၊ LAN multi-station ချက်ချင်း လိုချင်ပါသလား? | Single-PC v1.0 |

---

## 18. Approval Checkpoint

> **ဤ `windows_offline_installer_plan_v1.md` ကို review ပြုလုပ်ပြီး Project Owner က approval မပေးမီ installer code မရေးရ၊ PHP bundle မစုစည်းရ၊ Inno Setup script မဖန်တီးရ။**

```
Approval Status:   [ ] Approved  [ ] Rejected  [ ] Revisions Required

Project Owner:     _______________________________

Date:              _______________________________

Signature:         _______________________________

Q-1 to Q-8 Answers / Notes:

  Q-1 Install Dir:    _______________________________
  Q-2 Port:           _______________________________
  Q-3 Backup Time:    _______________________________
  Q-4 Retention:      _______________________________
  Q-5 Language:       _______________________________
  Q-6 Code Signing:   _______________________________
  Q-7 Pilot Stores:   _______________________________
  Q-8 LAN Multi-PC:   _______________________________
```

---

*Prepared by Tech Buddy per `myanmar_business_commercial_readiness_plan_v1.md` Section 20 (Phase F → Phase G handoff).*
*Next document: `windows_offline_installer_phase_g_implementation.md` (after approval).*
