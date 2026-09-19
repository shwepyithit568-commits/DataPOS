# DataPOS E2E Browser Test Defects & Findings Log (Run: UAT-20260919-01)

> Target Store: `shwe-pyi-thit-mobile` (ရွှေပြည်သစ် မိုဘိုင်းနှင့် အီလက်ထရွန်းနစ်)  
> Environment: MariaDB 10.4.32, PHP 8.2.12, Laravel 12.64.0  
> Database: `datapos_browser_uat_20260919_01` (Isolated Disposable DB)  

---

### BUG-001 — Duplicate Device Serial Number Allowed in Warranty Registration
- Severity: P2
- Case ID / HEAD / environment: PUR-01 / REG-04, HEAD `e7fad0e9`, MariaDB 10.4.32
- Role / store / shift / URL / viewport: Store Manager / shwe-pyi-thit-mobile / - / `/admin/warranty`
- Preconditions + exact record IDs: Product ID 3 (POWERBANK), DeviceWarranty with serial `RMX-2026-001` exists.
- Reproduction steps:
  1. Register a warranty card with serial `RMX-2026-001`.
  2. Attempt to register another warranty card for the same store with identical serial `RMX-2026-001`.
- Expected: Server/database rejects duplicate serial number with validation error `duplicate_serial` or unique constraint.
- Actual: Registration succeeds; duplicate serial is inserted into `device_warranties`.
- Business impact: Risk of fraudulent double warranty claims or inventory tracking collision for serialized devices.
- Evidence links: `scratch/test_duplicate_serial.php`, output `DUPLICATE_ACCEPTED`.
- Suspected code path / root cause: `WarrantyTrackerController::store` and `WarrantyTrackerService::register` lack `Rule::unique('device_warranties', 'serial_number')->where('store_id', $store->id)`.
- Smallest proposed fix: Add `Rule::unique('device_warranties', 'serial_number')->where('store_id', $store->id)` in `WarrantyTrackerController` validation rules.
- Regression checks after fix: Attempt duplicate serial registration and verify HTTP 422 with validation error.
- Status: Open (Finding catalogued during UAT).
