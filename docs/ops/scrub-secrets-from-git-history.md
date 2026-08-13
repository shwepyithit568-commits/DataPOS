# Scrub Production Credentials from Git History

> **ဘာကြောင့် ဒီဖိုင် လိုလဲ:** `docs/production-env-datapos.md` (အခု `docs/ops/production-env-datapos.md` — **2026-08-13 တွင် working tree ကနေ ဖျက်လိုက်ပြီ**၊ ဒါပေမဲ့ git history ထဲမှာ ကျန်နေဆဲ — ဒီအောက်က scrub ညွှန်ကြားချက်တွေက history အတွက် ဆက်အသုံးဝင်တယ်)
> ထဲမှာ **တကယ့် production credentials** (`APP_KEY`, `DB_PASSWORD`, `MAIL_PASSWORD`) တွေ ပါခဲ့ပြီး
> **initial commit (59976ee) ကတည်းက** git history ထဲ ရောက်နေပါသည်။
> Working tree မှာ values တွေကို REDACTED လုပ်ပြီးသားဖြစ်သော်လည်း **အဟောင်း commits တွေထဲမှာ
> မူရင်းတန်ဖိုးတွေ ကျန်နေဆဲဖြစ်သည်။**

---

## 1. လက်ရှိ အခြေအနေ (ဒီ repo အတွက် အတည်ပြုပြီး)

| အချက် | အခြေအနေ |
|---|---|
| Total commits | **4** (59976ee → 38e1fbb → a84d860 → 8c0a265) — history က သေးငယ်လို့ rewrite ပေါ့ပါးသည် |
| Secrets ပါသော ဖိုင် | `docs/production-env-datapos.md` တစ်ခုတည်းသာ (initial commit မှ စ၍ commit ၄ ခုလုံးတွင်) |
| `.env` ကိုယ်တိုင် | **ဘယ်တော့မှ commit မဖြစ်ဖူး** ✓ (`.env.example` သာ — အန္တရာယ်ကင်း) |
| Remote | `https://github.com/shwepyithit568-commits/DataPOS.git` — **private ဖြစ်ဖွယ်ရှိ** (unauthenticated API က 404) |
| Branch | `main` — local = origin/main (up to date) |
| filter-repo | **မရှိသေးပါ** — `pip install git-filter-repo` ဖြင့် ထည့်ရမည် |

---

## 2. ရွေးစရာ ၂ ခု — ဘယ်ဟာ ယူမလဲ

### Option A — History rewrite (git filter-repo) — **အကြံပြုသည်**
Commit ၄ ခုပဲ ရှိတာမို့ rewrite က မြန်ပြီး အန္တရာယ်နည်းသည်။ ပြီးရင် **credentials တွေကို ဘယ်လိုပဲဖြစ်ဖြစ် rotate လုပ်ပါ** (အောက် §6) — history ဖျက်တာက အနာဂတ် ယိုစိမ့်မှုကို ရပ်တန့်ပေးတာပဲ၊ ဖော်ထုတ်ပြီးသား အချက်အလက်ကို မပြန်ပျက်စေနိုင်ပါ။

### Option B — History မပြောင်းဘဲ ထားပါ (rotate သာလုပ်ပါ)
Repo က private ဖြစ်ပြီး ရရှိသူ အကန့်အသတ်ရှိပါက "အရေးမကြီးသေး" လို့ ဆုံးဖြတ်နိုင်သည်။
ဒါပေမယ့် (a) repo ကို public ပြောင်း/မျှဝေမယ်ဆိုရင် ပေါက်ကြားမယ်၊ (b) GitHub Secret Scanning က
ဖော်ထုတ်ပြီးသားလည်း ဖြစ်နိုင်သည်။ **ဒီအခြေအနေမှာ Option A ကို ဦးစားပေးပါ။**

---

## 3. Option A — Step-by-step (git filter-repo)

### Step 0 — Prerequisite
- Python 3.5+ ရှိရမည် (`python --version` စစ်ပါ)။
- filter-repo ထည့်ပါ:
  ```bash
  pip install git-filter-repo
  ```
  (သို့) single-script နည်း: `https://github.com/newren/git-filter-repo` မှ `git-filter-repo` ဖိုင်ကို
  ဒေါင်းလုဒ်လုပ်ပြီး PATH ထဲ ထားပါ။

### Step 1 — အရင်ဆုံး backup လုပ်ပါ (မဖြစ်မနေ)
```bash
# 1a. Git history backup (branch/commit အားလုံး)
git bundle create "D:\backup-datapos-$(date +%Y%m%d).bundle" --all

# 1b. Working tree (uncommitted) backup — ဒီ repo မှာ uncommitted အလုပ်တွေ အများကြီး ရှိနေလို့
#     ဒီ step က အရေးကြီးဆုံးပဲ။ Folder တစ်ခုလုံး ကော်ပီကူးထားပါ:
#     D:\xmapp\htdocs\DataPOS  →  D:\backup\DataPOS-20260813
```

### Step 2 — filter-repo ကို **fresh clone** ပေါ်မှာ ပြေးပါ (main checkout ကို မထိ)
> ⚠️ filter-repo သည် **dirty working tree ရှိရင် ငြင်းပယ်သည်**။ ဒီ repo မှာ uncommitted အလုပ်တွေ
> အများကြီးရှိနေလို့ **မူလ folder ထဲ မပြေးပါနဲ့** — အောက်ပါအတိုင်း clone အသစ်မှာ ပြေးပါ။

```bash
cd D:\xmapp\htdocs
git clone https://github.com/shwepyithit568-commits/DataPOS.git DataPOS-scrub
cd DataPOS-scrub

# secrets ပါသော ဖိုင်ကို history အားလုံးကနေ ဖျက်
git filter-repo --path docs/production-env-datapos.md --invert-paths

# filter-repo က origin remote ကို ဖျက်လိုက်လို့ ပြန်ထည့်ပါ
git remote add origin https://github.com/shwepyithit568-commits/DataPOS.git
```

> `--invert-paths` က **ဖိုင်တစ်ခုလုံးကို** commit အားလုံးကနေ ဖျက်သည် (values ချည်း မဟုတ်) —
> အန္တရာယ်ကင်းဆုံး နည်းဖြစ်သည်။ ဖိုင်ရဲ့ structure ကို ထိန်းထားလိုပါက အစား
> `--replace-text` (values စာရင်း ပါသော ဖိုင်) သုံးနိုင်သည် — သို့သော် key/value ပုံစံကို
> ထားထားလိုပါက `--invert-paths` က ပိုသန့်သည်။

### Step 3 — Verify (မတင်မီ)
```bash
# ဖိုင် မရှိတော့ကြောင်း စစ်
git log --all --oneline -- docs/production-env-datapos.md     # (output ဘာမှ မရှိရမည်)

# commit ၄ ခု ကျန်နေဆဲ ဖြစ်ကြောင်း စစ်
git rev-list --count HEAD                                      # → 4

# value တွေ history ထဲ မကျန်တော့ကြောင်း စစ် (ကိုယ်ပိုင် secret string နဲ့ အစားထိုး)
git rev-list --all | xargs git grep -l "APP_KEY=base64" 2>/dev/null || echo "CLEAN"
```

### Step 4 — Force-push
```bash
git push --force --all origin
# (branch က main တစ်ခုတည်းဆိုရင်:  git push --force origin main)
```

### Step 5 — Main checkout (မူရင်း folder) ကို ပြန်ညှိပါ
> ⚠️ ဒီ step က **သင့်ရဲ့ uncommitted အလုပ်တွေကို မဖျက်ပါစေနဲ့** — မလုပ်မီ stash လုပ်ထားပါ။

```bash
cd D:\xmapp\htdocs\DataPOS
git stash push -u -m "wip-before-history-rewrite"   # uncommitted + untracked အကုန် သိမ်း
git fetch origin
git reset --hard origin/main                        # rewrite ပြီးသား history ကို ယူ
git stash pop                                        # အလုပ်တွေ ပြန်ထုတ်
git status                                           # အလုပ်တွေ ကျန်နေသေးကြောင်း စစ်
```

> ရွေးစရာ (ပိုလုံခြုံ): main checkout ကို မထိဘဲ **DataPOS-scrub ကနေ ဆက်အလုပ်လုပ်ပါ** — folder နာမည်ပြောင်းပြီး
> သုံးနိုင်သည်။ သို့သော် uncommitted အလုပ်တွေက scrub clone ထဲ မရောက်လို့ stash/ကော်ပီ နည်းက ပိုသင့်သည်။

---

## 4. Option A — ပြီးပြီးနောက် မဖြစ်မနေ လုပ်ရမည့်ဟာများ

1. **Collaborators အားလုံး re-clone / hard reset လုပ်ရမည်** — အဟောင်း clone တွေက SHA mismatch ဖြစ်လိမ့်မည်။
2. **GitHub cache purge:** force-push ပြီးနောက် GitHub က အဟောင်း commits တွေကို SHA တိုက်ရိုက်ဖွင့်ချင်း
   ကြည့်လို့ ရနေနိုင်သည်။ GitHub Support ကို
   (github.com/contact → "Sensitive data" request) ဖြင့် cached views ဖျက်ခိုင်းပါ။
3. **Protected branch ဆိုပါက** force-push မရနိုင် — admin ခွင့်ပြုချက် လိုမည်။
4. **Secrets များကို rotate လုပ်ပါ** (အောက် §6) — history ဖျက်တာ လုံလောက်မှု မဟုတ်ပါ။

---

## 5. BFG (အခြားရွေးချယ်စရာ)

Java ရှိပါက BFG Repo-Cleaner သုံးနိုင်သည် (filter-repo ထက် အသုံးပြုရ လွယ်သည်၊
ရလဒ် အတူတူ — history ကို rewrite လုပ်သည်):

```bash
# https://rtyley.github.io/bfg-repo-cleaner/ မှ bfg.jar ဒေါင်းလုဒ်
java -jar bfg.jar --delete-files production-env-datapos.md
git reflog expire --expire=now --all && git gc --prune=now --aggressive
git push --force --all origin
```

---

## 6. Credential Rotation (မဖြစ်မနေ — ဘယ် option ပဲ ယူယူ)

History ထဲက ထွက်သွားပြီးသား values တွေကို ပြန်ပျက်စေလို့ မရပါ။ ဒါကြောင့် Hostinger server မှာ:

1. **Laravel APP_KEY** ပြောင်းပါ — server ပေါ်ရှိ `.env` မှာ
   `php artisan key:generate` ပြေးပြီး ရလာတဲ့ key ကို ထည့်ပါ (sessions/encrypted data invalid ဖြစ်မှာ — ပုံမှန်ပဲ)။
2. **DB_PASSWORD ပြောင်းပါ** — Hostinger MySQL panel မှာ password အသစ်တည်ပြီး `.env` update + `php artisan config:cache`။
3. **MAIL_PASSWORD ပြောင်းပါ** — email provider (SMTP) password ပြောင်းပါ။
4. GitHub က public repo ရှိရင် Secret Scanning က auto-detect လုပ်ပြီး alert ပို့ထားနိုင်သည် — သတိပြုပါ။

---

## 7. အကျိုးဆက် (Consequences) အကျဉ်းချုပ်

| ဆိုးကျိုး | ရှင်းလင်းချက် |
|---|---|
| **အားလုံး commit hashes ပြောင်းသွားမည်** | 4 commits လုံး — SHA အသစ်တွေ ရသည်; ဘယ်သူမဆို အဟောင်း clone ရှိရင် ပြန်စရမည် |
| **Force-push လိုအပ်သည်** | `--force` မသုံးရင် push ငြင်းမည်; protected branch ဆိုရင် admin လို |
| **GitHub မှာ အဟောင်း objects ကျန်နေနိုင်သည်** | SHA တိုက်ရိုက်နဲ့ ကြည့်လို့ရနိုင်သေးသည် — Support နဲ့ purge လုပ်ရမည် |
| **Working tree ကို မထိရ** | filter-repo က dirty repo ကို ငြင်းသည် → fresh clone / stash နည်း သုံးရမည် |
| **origin remote ဖျက်ခံရသည်** | filter-repo ပြီးတိုင်း `git remote add origin …` ပြန်လုပ်ရမည် |
| **Credentials တွေ ပေါက်ကြားပြီးသားပါ** | history ဖျက်တာက အနာဂတ်အတွက်; **rotate လုပ်ဖို့ မမေ့ပါနဲ့** |

---

## 8. လုံးဝ မလုပ်သင့်သော အရာ (သတိပေးချက်)

- ⛔ `git filter-branch` ကို **မသုံးပါနဲ့** (deprecated, နှေးပြီး မှားလွယ်) — filter-repo / BFG သုံးပါ။
- ⛔ Dirty tree ပေါ်မှာ filter-repo ပြေးဖို့ မကြိုးစားပါနဲ့ — အလုပ်တွေ ဆုံးရှုံးနိုင်သည်။ အမြဲ backup + fresh clone မှ လုပ်ပါ။
- ⛔ Push မလုပ်မီ verify (Step 3) ကို မကျော်ပါနဲ့။
