# Multi-Agent UAT Testing Prompts (Archive)

ဤ directory ရှိ `Agent_0` မှ `Agent_8` စမ်းသပ်မှု Prompts များသည် ၂၀၂၆ စက်တင်ဘာ ၈ ရက်နေ့က DataPOS application အား end-to-end စစ်ဆေးခဲ့သော Multi-Agent UAT run တွင် အသုံးပြုခဲ့သော Prompts များ ဖြစ်ပါသည်။

အဆိုပါ စမ်းသပ်မှု၏ ရလဒ်များနှင့် UAT run manifest များကို [`../../audits/UAT-20260908-0733/`](../../audits/UAT-20260908-0733/) တွင် ထိန်းသိမ်းထားရှိပါသည်။

လက်ရှိ Active AI Agent QA & Fix Playbook အတွက် [`../../../prompts/AI_AGENT_QA_PLAYBOOK.md`](../../../prompts/AI_AGENT_QA_PLAYBOOK.md) ကိုသာ ကြည့်ရှုအသုံးပြုပါ။

## File Inventory

| File | Agent Role | Test Area |
|---|---|---|
| [`Agent_0_Test_Coordinator.md`](Agent_0_Test_Coordinator.md) | Lead QA Coordinator | UAT Coordination and multi-agent synthesis |
| [`Agent_1_Store_Setup_Modules_and_Permissions.md`](Agent_1_Store_Setup_Modules_and_Permissions.md) | Store Owner | Store setup, modules, channels, staff roles & permissions |
| [`Agent_2_Products_Purchasing_and_Inventory.md`](Agent_2_Products_Purchasing_and_Inventory.md) | Inventory Staff | Purchasing-to-stock workflow |
| [`Agent_3_POS_Cashier_and_Daily_Closing.md`](Agent_3_POS_Cashier_and_Daily_Closing.md) | Cashier | Cashier shifts, POS transactions & daily closing |
| [`Agent_4_Returns_Refunds_and_Cancellation_Integrity.md`](Agent_4_Returns_Refunds_and_Cancellation_Integrity.md) | Supervisor | Returns, refunds, exchanges & cancel integrity |
| [`Agent_5_Ecommerce_and_Online_Offline_Channel.md`](Agent_5_Ecommerce_and_Online_Offline_Channel.md) | Online Customer / Staff | Ecommerce ordering & channel separation |
| [`Agent_6_Repair_and_Service_Workflow.md`](Agent_6_Repair_and_Service_Workflow.md) | Technician / Reception | Repair jobs & service workflow |
| [`Agent_7_Finance_Accounting_and_Reconciliation.md`](Agent_7_Finance_Accounting_and_Reconciliation.md) | Accountant | Financial reconciliation & audit |
| [`Agent_8_Final_Security_Reports_and_Human_Experience_Audit.md`](Agent_8_Final_Security_Reports_and_Human_Experience_Audit.md) | Independent Auditor | Acceptance audit & security |
