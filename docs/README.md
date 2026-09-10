# DataPOS Documentation Index

ဒီ directory သည် DataPOS ၏ active documentation entry point ဖြစ်သည်။ Documentation နှင့် code ကွဲလွဲပါက လက်ရှိ code၊ migrations၊ routes၊ tests နှင့် `AGENTS.md` ကို အရင်စစ်ပြီး မအတည်ပြုရသေးသော claim ကို fact အဖြစ် မသုံးရ။

## Source-of-truth order

1. `AGENTS.md` နှင့် owner ၏ နောက်ဆုံး approved requirements
2. လက်ရှိ branch ရှိ executable code၊ migrations၊ routes နှင့် configuration
3. လက်ရှိ passing automated tests နှင့် CI evidence
4. Active architecture/operations documents
5. Roadmaps, proposed plans and checklists
6. `docs/archive/` ရှိ historical evidence

Commit SHA၊ test count၊ dependency version နှင့် deployment status များသည် အချိန်နှင့်အမျှ ပြောင်းလဲနိုင်သည်။ ထိုအချက်များကို documentation ထဲက cached value တစ်ခုတည်းဖြင့် မဆုံးဖြတ်ဘဲ Git နှင့် verification commands ဖြင့် ပြန်စစ်ရမည်။

---

## Active Documentation Categories

Active documentation များအားလုံးကို အောက်ပါ Subfolders များဖြင့် စနစ်တကျ ခွဲခြားထားပါသည်—

### 1. Architecture (`docs/architecture/`)
စနစ်၏ အခြေခံ Architecture၊ Single-Codebase Multi-Store Isolation၊ Domain Specifications နှင့် Runtime Storage ပုံစံများ။

| Document | Role |
|---|---|
| [`Source_of_Truth_Master_MM.md`](architecture/Source_of_Truth_Master_MM.md) | Platform master domain architecture, invariants and multi-store source of truth |
| [`SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md`](architecture/SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md) | Product/store domain design and smart auto-generator engine |
| [`MULTI_STORE_DATA_ISOLATION_AUDIT_PLAN.md`](architecture/MULTI_STORE_DATA_ISOLATION_AUDIT_PLAN.md) | Tenant-isolation controls and 4-layer audit coverage |
| [`windows_runtime_and_storage_architecture.md`](architecture/windows_runtime_and_storage_architecture.md) | Canonical Windows runtime, SQLite path resolution and writable storage matrix |
| [`DATAPOS_THEME_PLAN.md`](architecture/DATAPOS_THEME_PLAN.md) | Theme system, dark/light modes and token governance |

### 2. Strategic Plans & Roadmaps (`docs/plans/`)
ထုတ်ကုန်တိုးတက်မှု ဗျူဟာ၊ Modules စီမံခန့်ခွဲမှု၊ Reporting Suite နှင့် Offline Installer အကောင်အထည်ဖော်မှု အစီအစဉ်များ။

| Document | Role |
|---|---|
| [`DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md`](plans/DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md) | Product strategy; proposed items must not be treated as implemented |
| [`ADMIN_MODULES_EXECUTION_ROADMAP.md`](plans/ADMIN_MODULES_EXECUTION_ROADMAP.md) | Admin-module readiness matrix and implementation roadmap |
| [`reports_implementation_plan_v2.md`](plans/reports_implementation_plan_v2.md) | Reporting architecture and remaining roadmap; baseline section is historical |
| [`windows_offline_installer_plan_v2.md`](plans/windows_offline_installer_plan_v2.md) | Windows offline installer specification and decision log |
| [`myanmar_business_commercial_readiness_plan_v1.md`](plans/myanmar_business_commercial_readiness_plan_v1.md) | Comprehensive Myanmar SME commercial readiness plan |
| [`MYANMAR_SME_COMMERCIALIZATION_GUIDE.md`](plans/MYANMAR_SME_COMMERCIALIZATION_GUIDE.md) | Commercial readiness, live demo script and pilot sales strategy |

### 3. Checklists (`docs/checklists/`)
လုပ်ငန်းစဉ်အဆင့်လိုက် စစ်ဆေးချက်များနှင့် Production Go-Live အရည်အသွေးစစ်ဆေးချက်များ။

| Document | Role |
|---|---|
| [`DATAPOS_GROWTH_EXECUTION_CHECKLIST_MM.md`](checklists/DATAPOS_GROWTH_EXECUTION_CHECKLIST_MM.md) | Growth execution master tracker |
| [`PRODUCTION_READINESS_AND_CLEANUP_CHECKLIST.md`](checklists/PRODUCTION_READINESS_AND_CLEANUP_CHECKLIST.md) | Production verification checklist; never substitute checked boxes for a fresh run |

### 4. Operations & Deployment (`docs/operations/`)
နေ့စဉ် Run/Test/Debug commands များ၊ Demo အကောင့်များ၊ Production Deployment နှင့် Backup/Restore လုပ်ထုံးလုပ်နည်းများ။

| Document | Role |
|---|---|
| [`PROJECT_COMMANDS_CHEATSHEET.md`](operations/PROJECT_COMMANDS_CHEATSHEET.md) | Local development, artisan, test, and build quick commands |
| [`DEMO_STORES_AND_TEST_ACCOUNTS.md`](operations/DEMO_STORES_AND_TEST_ACCOUNTS.md) | 6 Demo stores and role-based test account credentials |
| [`DEPLOYMENT.md`](operations/DEPLOYMENT.md) | Production server setup, Nginx, environment security and hardening guide |
| [`release_snapshot_and_backup_guide.md`](operations/release_snapshot_and_backup_guide.md) | Four distinct artifact taxonomy, git bundle and customer data backup guide |
| [`DATAPOS_TEAM_RECRUITMENT_AND_TRAINING_GUIDE_MM.md`](operations/DATAPOS_TEAM_RECRUITMENT_AND_TRAINING_GUIDE_MM.md) | Team onboarding, junior QA SOP and training operations |

### 5. UI/UX & Domain Guides (`docs/guides/`)
အသုံးပြုသူမျက်နှာပြင် (UI/UX) စံနှုန်းများနှင့် အထူးပြု Domain လမ်းညွှန်များ။

| Document | Role |
|---|---|
| [`ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md`](guides/ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md) | Canonical Admin UI standard (2px rhythm, table/cards switcher, tri-lingual) |
| [`STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md`](guides/STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md) | Canonical Storefront UI standard (mobile-first, offline cart resilience) |
| [`ALINN_THIT_MOBILE_SKU_LOGIC_MM.md`](guides/ALINN_THIT_MOBILE_SKU_LOGIC_MM.md) | Client-specific mobile accessories SKU normalization standard |

### 6. AI Agent Prompts & Playbooks (`docs/prompts/`)
AI Coding Agents များအတွက် စံသတ်မှတ်ထားသော QA နှင့် Audit Prompts များ။

| Document | Role |
|---|---|
| [`AI_AGENT_QA_PLAYBOOK.md`](prompts/AI_AGENT_QA_PLAYBOOK.md) | Consolidated reusable QA, multi-store isolation and safe-fix playbook |
| [`DATAPOS_ADMIN_PREPRODUCTION_AUDIT_PROMPT.md`](prompts/DATAPOS_ADMIN_PREPRODUCTION_AUDIT_PROMPT.md) | Admin pre-production audit and verification prompt |
| [`DATAPOS_ECOMMERCE_PREPRODUCTION_AUDIT_PROMPT.md`](prompts/DATAPOS_ECOMMERCE_PREPRODUCTION_AUDIT_PROMPT.md) | Storefront pre-production audit prompt |
| [`AI_AGENT_SHOP_OWNER_MANAGER_E2E_AUDIT_PROMPT_MM.md`](prompts/AI_AGENT_SHOP_OWNER_MANAGER_E2E_AUDIT_PROMPT_MM.md) | Store operation end-to-end audit prompt |
| [`datapos_ai_agents_pre_installer_prompts_v1.md`](prompts/datapos_ai_agents_pre_installer_prompts_v1.md) | Windows pre-installer baseline verification prompts |

---

## Archive Policy (`docs/archive/`)

`docs/archive/` အောက်ကဖိုင်များသည် historical audit၊ completed phase၊ superseded plan သို့မဟုတ် merged legacy prompt ဖြစ်သည်။ ၎င်းတို့ကို active implementation instruction သို့မဟုတ် current completion evidence အဖြစ် မသုံးရ။

- [`archive/README.md`](archive/README.md) — Archive index and usage guidelines
- `archive/audits/` — point-in-time audit results (UAT, Phase A commercial readiness, pre-installer baseline)
- `archive/completed-phases/` — historical completion reports (Phase 2 daily closing, Phase F)
- `archive/superseded-plans/` — replaced plan versions (v1 plans and early `pos-resale-plan/`)
- `archive/legacy-ops/` — historical Hostinger pilot cutover and backup drills
- `archive/legacy-prompts/` — legacy prompt fragments and early testing agent prompts (`testing-agents/`)
- `archive/source-of-truth-history/` — historical source-of-truth drafts (v1, v2, 2026-07-31)
- `archive/deployment-runbook.md` — early deployment history

## Maintenance Rules

- Active document တစ်ခုစီတွင် purpose, status, last-verified date နှင့် supersession note ကို ရှင်းလင်းစွာရေးပါ။
- Snapshot report ကို update လုပ်မည့်အစား historical report အဖြစ် archive ရွှေ့ပါ။
- Absolute local links (`file:///...`) မသုံးပါနှင့်။ Repository-relative links သာသုံးပါ။
- “Passed”, “production-ready”, “deployed” စသည့် claims တွင် command၊ date၊ branch/SHA နှင့် limitation ပါရမည်။
- Plan၊ checklist နှင့် completion report ကို ဖိုင်တစ်မျိုးတည်းအဖြစ် မရောပါနှင့်။
