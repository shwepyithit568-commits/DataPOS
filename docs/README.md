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

## Active architecture and product direction

| Document | Role |
|---|---|
| [`Source_of_Truth_Master_MM.md`](Source_of_Truth_Master_MM.md) | Platform master domain architecture, invariants and multi-store source of truth |
| [`pos-resale-plan/02-target-design.md`](pos-resale-plan/02-target-design.md) | Cloud/local deployment models၊ ledger၊ branch၊ POS နှင့် offline architecture |
| [`pos-resale-plan/ROADMAP.md`](pos-resale-plan/ROADMAP.md) | Implementation sequence and release gates |
| [`SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md`](SMART_PRODUCT_AND_STORE_ARCHITECTURE_SPEC.md) | Product/store domain design |
| [`guides/ALINN_THIT_MOBILE_SKU_LOGIC_MM.md`](guides/ALINN_THIT_MOBILE_SKU_LOGIC_MM.md) | Client-specific mobile accessories SKU normalization standard |
| [`MULTI_STORE_DATA_ISOLATION_AUDIT_PLAN.md`](MULTI_STORE_DATA_ISOLATION_AUDIT_PLAN.md) | Tenant-isolation controls and audit coverage |
| [`reports_implementation_plan_v2.md`](reports_implementation_plan_v2.md) | Reporting architecture and remaining roadmap; baseline section is historical |
| [`DataPOS Theme Plan.md`](DataPOS%20Theme%20Plan.md) | Theme governance and storefront customization |

## Product planning and commercialization

These documents are related but not duplicates: the Growth Plan explains product strategy; the Checklist tracks execution; the Commercialization Guide describes release-to-market gates.

| Document | Role |
|---|---|
| [`DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md`](DATAPOS_SINGLE_CODEBASE_GROWTH_PLAN_MM.md) | Product strategy; proposed items must not be treated as implemented |
| [`DATAPOS_GROWTH_EXECUTION_CHECKLIST_MM.md`](DATAPOS_GROWTH_EXECUTION_CHECKLIST_MM.md) | Execution tracker; checked items still require current evidence |
| [`ADMIN_MODULES_EXECUTION_ROADMAP.md`](ADMIN_MODULES_EXECUTION_ROADMAP.md) | Admin-module sequencing |
| [`MYANMAR_SME_COMMERCIALIZATION_GUIDE.md`](MYANMAR_SME_COMMERCIALIZATION_GUIDE.md) | Commercial readiness and pilot strategy |
| [`myanmar_business_commercial_readiness_plan_v1.md`](myanmar_business_commercial_readiness_plan_v1.md) | Detailed business readiness plan |
| [`DATAPOS_TEAM_RECRUITMENT_AND_TRAINING_GUIDE_MM.md`](DATAPOS_TEAM_RECRUITMENT_AND_TRAINING_GUIDE_MM.md) | Team, QA and training operations |

## Engineering and UI standards

| Document | Role |
|---|---|
| [`PRODUCTION_READINESS_AND_CLEANUP_CHECKLIST.md`](PRODUCTION_READINESS_AND_CLEANUP_CHECKLIST.md) | Production verification checklist; never substitute checked boxes for a fresh run |
| [`prompts/ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md`](prompts/ADMIN_UI_UX_STANDARD_GUIDE_v4_1.md) | Canonical Admin UI standard despite the legacy filename |
| [`STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md`](STOREFRONT_UI_UX_STANDARD_GUIDE_v1_0.md) | Canonical Storefront UI standard |
| [`prompts/AI_AGENT_QA_PLAYBOOK.md`](prompts/AI_AGENT_QA_PLAYBOOK.md) | Consolidated reusable QA/fix instructions |
| [`AI_AGENT_SHOP_OWNER_MANAGER_E2E_AUDIT_PROMPT_MM.md`](AI_AGENT_SHOP_OWNER_MANAGER_E2E_AUDIT_PROMPT_MM.md) | Store operation end-to-end audit |
| [`prompts/DATAPOS_ADMIN_PREPRODUCTION_AUDIT_PROMPT.md`](prompts/DATAPOS_ADMIN_PREPRODUCTION_AUDIT_PROMPT.md) | Admin pre-production audit |
| [`prompts/DATAPOS_ECOMMERCE_PREPRODUCTION_AUDIT_PROMPT.md`](prompts/DATAPOS_ECOMMERCE_PREPRODUCTION_AUDIT_PROMPT.md) | Ecommerce pre-production audit |

## Runtime, deployment and recovery

| Document | Role |
|---|---|
| [`windows_offline_installer_plan_v2.md`](windows_offline_installer_plan_v2.md) | Current installer plan; v1 is archived |
| [`windows_runtime_and_storage_architecture.md`](windows_runtime_and_storage_architecture.md) | Supporting Windows runtime/storage specification |
| [`ops/DEPLOYMENT.md`](ops/DEPLOYMENT.md) | Primary deployment runbook |
| [`ops/hostinger-deploy-checklist.md`](ops/hostinger-deploy-checklist.md) | Hostinger-specific checklist |
| [`ops/backup-restore-production-drill.md`](ops/backup-restore-production-drill.md) | Backup/restore drill |
| [`ops/pilot-recovery-cutover-runbook.md`](ops/pilot-recovery-cutover-runbook.md) | Pilot recovery/cutover procedure |
| [`release_snapshot_and_backup_guide.md`](release_snapshot_and_backup_guide.md) | Release artifact taxonomy and backup policy |
| [`PROJECT_COMMANDS_CHEATSHEET.md`](PROJECT_COMMANDS_CHEATSHEET.md) | Common project commands |
| [`DEMO_STORES_AND_TEST_ACCOUNTS.md`](DEMO_STORES_AND_TEST_ACCOUNTS.md) | Local/UAT-only demo identities and flows |

## Archive policy

`docs/archive/` အောက်ကဖိုင်များသည် historical audit၊ completed phase၊ superseded plan သို့မဟုတ် merged legacy prompt ဖြစ်သည်။ ၎င်းတို့ကို implementation instruction သို့မဟုတ် current completion evidence အဖြစ် မသုံးရ။

- `archive/audits/` — point-in-time audit results
- `archive/completed-phases/` — historical completion reports
- `archive/superseded-plans/` — replaced plan versions
- `archive/legacy-prompts/` — consolidated prompt fragments
- `archive/source-of-truth-history/` — historical source-of-truth drafts and original notes
- `archive/deployment-runbook.md` — legacy deployment history

## Maintenance rules

- Active document တစ်ခုစီတွင် purpose, status, last-verified date နှင့် supersession note ကို ရှင်းလင်းစွာရေးပါ။
- Snapshot report ကို update လုပ်မည့်အစား historical report အဖြစ် archive ရွှေ့ပါ။
- Absolute local links (`file:///...`) မသုံးပါနှင့်။ Repository-relative links သာသုံးပါ။
- “Passed”, “production-ready”, “deployed” စသည့် claims တွင် command၊ date၊ branch/SHA နှင့် limitation ပါရမည်။
- Plan၊ checklist နှင့် completion report ကို ဖိုင်တစ်မျိုးတည်းအဖြစ် မရောပါနှင့်။
