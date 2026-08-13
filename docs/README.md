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
| `docs/prompts/` | Agent conversation templates (new chat မှာ paste လုပ်ရန်) | `TEMPLATES_MM.md` (project-start · bug-fix · new-feature · new-conversation · category-image) · `04_UI_LAYOUT_PROMPT_MM.md` · `05_STOREFRONT_CUSTOMIZATION_ROADMAP_MM.md` · `AI Development Agent Instructions (MM)` |
| `docs/pos-resale-plan/` | POS + Resale စနစ် တည်ဆောက်ရေး အစီအစဉ် (00–04) | overview → current-state → target-design → sales-model → implementation-phases |
| `docs/plans/` | Forward-looking feature plans | `multi-store-ready-plan.md` |
| `docs/ops/` | Deployment / operations / security | `deployment-runbook.md` · `production-deployment-guide.md` · `pilot-recovery-cutover-runbook.md` · `production-env-example.md` · `backup-strategy.md` · `scrub-secrets-from-git-history.md` |
| `docs/uat/` | User acceptance testing | `local-uat.md` (checklist + results/defect log + LAN device test note) |
| `docs/archive/` | Dated / done one-off logs + completed audit reports (ပြီးသွားသော အလုပ် မှတ်တမ်း) | `2026-08-11-order-delete-feature.md` · `database-performance-audit.md` · `production-readiness-audit.md` |

## 🔁 Workflow အတိုချုပ်

1. အလုပ်မလုပ်မီ → `Source_of_Truth_MM.md` (rules) + `2026-08-02_FIXES.md` (history) + `Testing_check.md` (known issues) စစ်ပါ။
2. POS ဆိုင်ရာ → `DataPOS_Mobile_Offline_POS_Project_Source_of_Truth.md` + `docs/pos-resale-plan/` ကို ဦးစားပေးဖတ်ပါ။
3. အလုပ်ပြီးပါက → `2026-08-02_FIXES.md` (item အသစ်) + `Testing_check.md` (bug ဆိုရင်) update လုပ်ပါ။
4. Business/Architecture Rule ပြောင်းမှသာ `Source_of_Truth_MM.md` ကို update လုပ်ပါ။
5. 5+ files ထိမည့် / schema / inventory-payment ထိမည့် change → Affected Files / Approach / Risks ကို အရင် ပြပြီး confirmation ယူပါ။

## ⚠️ Security note

`docs/ops/production-env-datapos.md` မှာ အရင်က **တကယ့် production credentials** (APP_KEY, DB_PASSWORD, MAIL_PASSWORD) ပါခဲ့ပြီး
git history ထဲ ရောက်နေပါသည် — ဖိုင်ကို **2026-08-13 တွင် repo ကနေ ဖျက်လိုက်ပြီ** (safe template = `production-env-example.md`)
သို့သော် **git history ထဲမှာ ကျန်နေဆဲ** — repo ကို public မလုပ်မခင် `docs/ops/scrub-secrets-from-git-history.md` အတိုင်း history scrub လုပ်ရမည်။
မလိုအပ်တော့ပါက ဒီ secrets တွေ သုံးနေတဲ့ Hostinger server ရဲ့ APP_KEY / DB_PASSWORD / MAIL_PASSWORD တွေကို
ပြောင်းလဲ (rotate) လုပ်သင့်သည်။
