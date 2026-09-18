# DataPOS Documentation — Consolidated Edition

ဤ package သည် မူရင်း `docs.zip` ထဲရှိ Markdown documentation များကို အကြောင်းအရာအလိုက် master volumes အဖြစ် ပေါင်းစည်းထားခြင်းဖြစ်သည်။

## Current documentation

1. [Core Architecture](01_CORE_ARCHITECTURE.md)
2. [UI, Theme and Storefront](02_UI_THEME_AND_STOREFRONT.md)
3. [Products and SKU](03_PRODUCTS_AND_SKU.md)
4. [Roadmaps and Implementation Plans](04_ROADMAPS_AND_IMPLEMENTATION_PLANS.md)
5. [Operations and Deployment](05_OPERATIONS_AND_DEPLOYMENT.md)
6. [QA and Release Checklists](06_QA_AND_RELEASE_CHECKLISTS.md)
7. [AI Agent Prompts](07_AI_AGENT_PROMPTS.md)

## Standalone references

1. [Shop Owner/Manager E2E Audit Prompt (Myanmar)](AI_AGENT_SHOP_OWNER_MANAGER_E2E_AUDIT_PROMPT_MM.md) — production မတင်ခင် စစ်ဆေးရန် ကူးထည့်လို့ရတဲ့ prompt
2. [Project Commands Cheatsheet](PROJECT_COMMANDS_CHEATSHEET.md) — အမြန်ကြည့် command စာရင်း
3. [QA and Release Checklists](06_QA_AND_RELEASE_CHECKLISTS.md) — release အခါ စစ်ရမည့် စာရင်း
4. [Production Readiness Audit Report](PRODUCTION_READINESS_AUDIT_REPORT.md)
5. [Admin Print Audit Report](ADMIN_PRINT_AUDIT_REPORT.md)
6. [Service Voucher Print Standard](SERVICE_VOUCHER_PRINT_STANDARD.md)
7. [Closing Cash-Out Audit Fix](DataPOS_Closing_Cash_Out_Audit_Fix_MM.md) — ပထမ pass (`4de2cb3`)
8. [Closing Follow-up Fix Prompt](DataPOS_Closing_Followup_Fix_Prompt_MM.md)
9. [Closing Cash-Out Remaining Defects Fix](DataPOS_Closing_CashOut_Remaining_Defects_Fix_MM.md) — ဒုတိယ pass: retry protection, drawer attribution, unknown source, closed-shift lock, snapshot integrity
10. [MySQL Portability Fixes](DataPOS_MySQL_Portability_Fixes_MM.md) — production အင်ဂျင်တွင်သာ crash ဖြစ်သော bug ၆ မျိုး (SQLite က ဖုံးထားခဲ့သည်)
11. [UAT Day-Run Defects Fix](DataPOS_UAT_Day_Run_Defects_Fix_MM.md) — 2026-09-18 လက်တွေ့ UAT: ကောင်တာငွေ အံဆွဲမရောက်ခြင်း၊ ငွေသွင်း/ထုတ် အဝိုင်းဂဏန်းရိုက်မရခြင်း၊ 403 လင့် ၄ ခု၊ dashboard ဝင်ငွေ
12. [Coupon Redemption & Loyalty Earning](DataPOS_Coupon_And_Loyalty_Earning_MM.md) — 2026-09-19: ကောင်တာတွင် coupon သုံးနိုင်ခြင်း + အရောင်းမှ Points ရရှိခြင်း (gap ၂ ခု ဖြည့်)
13. [Storefront Coupon, Points Redemption & BOGO](DataPOS_Storefront_Coupon_Points_BOGO_MM.md) — 2026-09-19: အွန်လိုင်း checkout coupon၊ Points နဲ့ ငွေလျှော့ခြင်း၊ BOGO (၂ လမ်းကြောင်းလုံး)
14. [Online Order → Counter Fulfillment](DataPOS_Online_Order_Counter_Fulfillment_MM.md) — 2026-09-19: ဖောက်သည်အကောင့်ဖြင့် အွန်လိုင်းဝယ် → အတည်ပြု → ကောင်တာမှ ပေးအပ် (ချို့ယွင်းချက် ၃ ခု ပြင်ဆင်ပြီး)၊ **stock ဘယ်တုန်းလျှော့သွားသလဲ** ဇယား၊ ပြေစာလင့်နှင့် ငွေပေးချေမှုအခြေအနေ
15. [Production Go-Live Checklist](DataPOS_Production_GoLive_Checklist_MM.md) — 2026-09-19: deploy နေ့ `.env` (APP_ENV/APP_DEBUG/SHOW_QUICK_LOGIN)၊ deploy command စဉ်၊ migration ၂ ခု၊ ပရင်တာ သတိပြုရန်၊ မစမ်းရသေးသည့်စာရင်း
16. [Account Security Fix](DataPOS_Account_Security_Fix_MM.md) — 2026-09-19: ဖုန်းနံပါတ်ဖြင့် အကောင့်လွှဲယူနိုင်ခဲ့သည့် အန္တရာယ် ပိတ်ခြင်း (password_set_at၊ owner guard၊ အော်ဒါမှတ်တမ်း၊ ဆိုင်မှ reset) + လက်တွေ့စမ်းသပ်ချက်
17. [Consolidation Manifest](CONSOLIDATION_MANIFEST.md)

## Historical archive

1. [Historical Audits](archive/01_HISTORICAL_AUDITS.md)
2. [Completed Phases](archive/02_COMPLETED_PHASES.md)
3. [Superseded Plans](archive/03_SUPERSEDED_PLANS.md)
4. [Legacy Prompts](archive/04_LEGACY_PROMPTS.md)
5. [Legacy Operations](archive/05_LEGACY_OPERATIONS.md)
6. [Source-of-Truth History](archive/06_SOURCE_OF_TRUTH_HISTORY.md)

## Verification

[Consolidation Manifest](CONSOLIDATION_MANIFEST.md) တွင် မူရင်းဖိုင်တစ်ခုချင်းစီ၏ path၊ SHA-256 နှင့် ပေါင်းထည့်ထားသည့် target volume ကို စစ်ဆေးနိုင်သည်။

Archive documents များသည် historical evidence ဖြစ်ပြီး current implementation instruction အဖြစ် မသုံးသင့်ပါ။ Current behavior အတွက် code၊ tests နှင့် active documentation ကို source of truth အဖြစ်သုံးပါ။
