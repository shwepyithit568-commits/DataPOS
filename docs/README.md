# DataPOS — Documentation Index

ဒီဖိုင်က project ထဲက `.md` documentation တွေရဲ့ **တည်နေရာ မြေပုံ** ဖြစ်ပါတယ်။
Coding မစမီ သက်ဆိုင်ရာ documentation ကို ဒီကနေ ရှာပါ။

---

## 📌 Root — Active working docs (အရေးအကြီးဆုံး — အမြဲ update လုပ်ရမည့်ဟာများ)

| File | အကြောင်း |
|---|---|
| `README.md` | Project ခြုံငုံ မိတ်ဆက် + run နည်း — entry point |
| `Source_of_Truth_MM.md` | **Business Rules + Architecture Rules** — business/architecture ပြောင်းမှသာ update |
| `2026-08-02_FIXES.md` | **Implementation History / Change Log** — meaningful change တိုင်း item အသစ်ထည့်ရမည် |
| `Testing_check.md` | Bug / issue / pre-production testing အခြေအနေ |
| `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` | **POS စနစ် စည်းမျဉ်းစာချုပ် (MUST READ)** — POS module အတွက် |
| `RELEASE_NOTES.md` | Release / version notes |

## 📂 docs/ — Reference documentation

| Folder | အကြောင်း | Files |
|---|---|---|
| `docs/prompts/` | Agent conversation start templates (new chat မှာ paste လုပ်ရန်) | `01_PROJECT_START` · `02_BUG_FIX` · `03_NEW_FEATURE` · `04_UI_LAYOUT` · `05_STOREFRONT_CUSTOMIZATION_ROADMAP` · `NEW_CONVERSATION_START` · `AI Development Agent Instructions (MM)` |
| `docs/pos-resale-plan/` | POS + Resale စနစ် တည်ဆောက်ရေး အစီအစဉ် (00–04) | overview → current-state → target-design → sales-model → implementation-phases |
| `docs/plans/` | Forward-looking feature plans | `multi-store-ready-plan.md` |
| `docs/ops/` | Deployment / operations / security | `deployment-runbook.md` · `production-deployment-guide.md` · `production-env-datapos.md` (⚠️ values redacted) · `production-env-example.md` · `production-readiness-audit.md` · `backup-strategy.md` · `database-performance-audit.md` |
| `docs/uat/` | User acceptance testing | `local-uat-checklist.md` · `local-uat-results.md` · `local-device-test-note.md` |
| `docs/assets/` | Utility / creative prompts | `category-image-prompts.md` |
| `docs/archive/` | Dated / done one-off logs (ပြီးသွားသော အလုပ် မှတ်တမ်း) | `2026-08-11-order-delete-feature.md` |

## 🔁 Workflow အတိုချုပ်

1. အလုပ်မလုပ်မီ → `Source_of_Truth_MM.md` (rules) + `2026-08-02_FIXES.md` (history) + `Testing_check.md` (known issues) စစ်ပါ။
2. POS ဆိုင်ရာ → `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` + `docs/pos-resale-plan/` ကို ဦးစားပေးဖတ်ပါ။
3. အလုပ်ပြီးပါက → `2026-08-02_FIXES.md` (item အသစ်) + `Testing_check.md` (bug ဆိုရင်) update လုပ်ပါ။
4. Business/Architecture Rule ပြောင်းမှသာ `Source_of_Truth_MM.md` ကို update လုပ်ပါ။
5. 5+ files ထိမည့် / schema / inventory-payment ထိမည့် change → Affected Files / Approach / Risks ကို အရင် ပြပြီး confirmation ယူပါ။

## ⚠️ Security note

`docs/ops/production-env-datapos.md` ထဲမှာ အရင်က **တကယ့် production credentials** (APP_KEY, DB_PASSWORD, MAIL_PASSWORD) ပါခဲ့ပြီး
git history ထဲ ရောက်နေပါသည်။ လက်ရှိ file မှာ values အားလုံး REDACTED ဖြစ်ပြီးသားဖြစ်သည်။
**အကြံပြုချက်:** လုံခြုံရေးအတွက် (a) ဒီဖိုင်ကို repo ကနေ ဖျက်ပြီး (b) မလိုအပ်တော့ပါက
ဒီ secrets တွေ သုံးနေတဲ့ Hostinger server ရဲ့ APP_KEY / DB_PASSWORD / MAIL_PASSWORD တွေကို
ပြောင်းလဲ (rotate) လုပ်သင့်သည်။
