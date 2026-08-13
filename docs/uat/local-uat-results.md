# Local UAT Results & Defect Tracking

**Project:** DataPOS
**Store A:** DataPOS — slug: `datapos-mobile` (25 products, 5 categories, 5 brands, 15 Glass Finder items, 4 orders)
**Store B:** UAT Test Store B — slug: `uat-store-b` (2 products, 1 category, 1 brand, 1 Glass Finder item, 1 order)
**UAT Status:** MANUAL UAT READY (Manual UAT has NOT been executed yet)
**UAT Date:** _________
**Tester:** _________

---

## Severity Levels

| Severity | Definition | Action |
|---|---|---|
| **Blocker** | Cannot operate the business or exposes customer data | Fix immediately |
| **Critical** | Major workflow broken, no workaround | Fix during UAT |
| **Major** | Important feature works incorrectly | Log for prioritization |
| **Minor** | Cosmetic or low-impact issue | Log for backlog |
| **Warning** | Environment or deployment prerequisite | Document for production setup |

---

## Defect Log

### Blocker

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

### Critical

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

### Major

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

### Minor

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

### Environment Warnings & Deployment Prerequisite Log

| ID | Category | Component | Description | Impact & Empirical Finding | Action Required |
|---|---|---|---|---|---|
| ENV-WARN-001 | PHP Extension | Image Processing (`gd`) | PHP `gd` extension is not loaded in current local CLI PHP (`php -m`). | **Verified Empirical Result:** Real image uploads (JPEG, WebP, PNG) for Product, Category, Brand Logo, and Home Banner succeed using standard file storage. `gd` is NOT required for raw file uploads, but IS required for future image resizing, thumbnail cropping, or `dimensions` validation rules. | Enable `extension=gd` in production `php.ini`. |

---

## Summary

| Category | Count | Notes |
|---|---|---|
| Blocker | 0 | None identified |
| Critical | 0 | None identified |
| Major | 0 | None (GD reclassified to Environment Warning after verified raw upload success) |
| Minor | 0 | None identified |
| **Total Software Defects** | **0** | Application software logic ready for manual UAT |
| Environment Warnings | 1 | `gd` extension recommended for production deployment |

---

## Sign-off

- [ ] All Blocker and Critical defects are resolved
- [ ] Core business workflows verified (order, wholesale, glass finder)
- [ ] Data integrity confirmed
- [ ] Device/screen testing completed on at least one mobile width
- [ ] UAT READY for production hosting purchase

**Tester Signature:** _________________ **Date:** _________
**Project Owner Approval:** _________________ **Date:** _________
