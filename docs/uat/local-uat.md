# Local User Acceptance Test (UAT) Checklist

**Project:** DataPOS
**Store A:** DataPOS — slug: `datapos-mobile` (25 products, 5 categories, 5 brands, 15 Glass Finder items, 4 orders)
**Store B:** UAT Test Store B — slug: `uat-store-b` (2 products, 1 category, 1 brand, 1 Glass Finder item, 1 order - multi-store isolation testing)
**Date:** _________

## Storefront URLs

The storefront uses **hybrid store-context routing**:
- Homepage, Products, and Glass Finder resolve store context using the `store_slug` query parameter.
- Product details, guest order submissions, order confirmation, wholesale application, and store admin dashboards use path-based routing (`/store/{store_slug}/...`).
- `X-Store-Slug` HTTP header is supported as an internal/test fallback.

| Page | Real Browser URL |
|---|---|
| Homepage | `http://localhost:8500/?store_slug=datapos-mobile` |
| Products | `http://localhost:8500/products?store_slug=datapos-mobile` |
| Product detail | `http://localhost:8500/store/datapos-mobile/product/{slug}` |
| Glass Finder | `http://localhost:8500/glass-finder?store_slug=datapos-mobile` |
| Order submit | `POST http://localhost:8500/store/datapos-mobile/orders` |
| Order confirmation | `http://localhost:8500/store/datapos-mobile/orders/{id}/confirmation?token={token}` |
| Admin dashboard | `http://localhost:8500/store/datapos-mobile/admin/dashboard` |
| Store B admin | `http://localhost:8500/store/uat-store-b/admin/dashboard` |

**Instructions:**
- ✅ = Pass
- ❌ = Fail (log defect in **Appendix A** below)
- 🖐 = Manual/browser check required (cannot be automated)
- 🔧 = Can be tested via automated test

---

## A. Guest Customer (No Login)

### Homepage & Navigation

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.1 | Open homepage `/?store_slug=datapos-mobile` | Homepage loads, store name and banners displayed | 🔧 | ☐ | | | | |
| A.2 | Mobile hamburger menu opens/closes | Navigation drawer appears on click | 🖐 | ☐ | | | | |
| A.3 | Bottom navigation bar visible on mobile | Home, Products, Glass Finder, Account icons visible | 🖐 | ☐ | | | | |
| A.4 | Myanmar text renders without overflow | Burmese characters display correctly | 🖐 | ☐ | | | | |
| A.5 | Dark/light theme toggle works | Theme switches on click, preference persists | 🖐 | ☐ | | | | |

### Product Catalog

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.6 | Click "Products" in navigation | Product listing page loads with 25 products for Store A | 🔧 | ☐ | | | | |
| A.7 | Product grid displays correctly | Desktop 5 cols, Tablet 3 cols, Mobile 2 cols | 🖐 | ☐ | | | | |
| A.8 | Featured products badge visible | Featured items show "Featured" label | 🖐 | ☐ | | | | |
| A.9 | Search by product name | Type "Samsung" → filtered results appear | 🔧 | ☐ | | | | |
| A.10 | Filter by category | Select "Tempered Glass" → only TG products shown | 🖐 | ☐ | | | | |
| A.11 | Filter by brand | Select "iPhone" → only iPhone products shown | 🖐 | ☐ | | | | |
| A.12 | Click product → detail page | Product detail loads with name, price, description, warranty | 🔧 | ☐ | | | | |
| A.13 | Retail price visible on detail | Price displays as "Ks 15,000" | 🔧 | ☐ | | | | |
| A.14 | Wholesale price hidden from guest | Wholesale price not shown | 🔧 | ☐ | | | | |
| A.15 | Out-of-stock badge | "Out of Stock" badge shown; order button disabled/hidden | 🔧 | ☐ | | | | |
| A.16 | Product with warranty text | Warranty information displayed in detail | 🖐 | ☐ | | | | |
| A.17 | Product with return policy | Return policy displayed in detail | 🖐 | ☐ | | | | |

### Glass Finder

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.18 | Open Glass Finder page | Search form loads with brand/model/code fields | 🔧 | ☐ | | | | |
| A.19 | Search by phone model | Type "Samsung Galaxy S24 Ultra" → compatible glasses shown | 🔧 | ☐ | | | | |
| A.20 | Search by glass code | Type "G-S24U-F" → matching records found | 🔧 | ☐ | | | | |
| A.21 | Search by brand | Select "iPhone" → all iPhone glasses shown | 🖐 | ☐ | | | | |
| A.22 | Glass compatibility row shows stock status | "In Stock" / "Out of Stock" label visible | 🔧 | ☐ | | | | |
| A.23 | Guest cannot favorite glass (redirects to login) | Clicking heart icon → redirect to login | 🔧 | ☐ | | | | |

### Order Request (Guest)

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.24 | Click "Order" on in-stock product | Order form appears with name, phone, address, contact channel | 🖐 | ☐ | | | | |
| A.25 | Name field validation | Submit empty → validation error shown | 🔧 | ☐ | | | | |
| A.26 | Phone field validation | Submit invalid phone → validation error shown | 🔧 | ☐ | | | | |
| A.27 | Address field validation (Required) | Submit without address → validation error shown, no order created | 🔧 | ☐ | | | | |
| A.28 | Select Viber channel | Order request submitted; redirects to confirmation page | 🔧 | ☐ | | | | |
| A.29 | Select Telegram channel | Order request submitted; redirects to confirmation page | 🔧 | ☐ | | | | |

### Order Confirmation (Guest)

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.30 | Confirmation page shows success banner | Green "Order Successful" banner displayed | 🔧 | ☐ | | | | |
| A.31 | Order summary shows correct data | Order number, name, phone, total, status all correct | 🔧 | ☐ | | | | |
| A.32 | Order items listed | Product name, quantity, subtotal displayed | 🔧 | ☐ | | | | |
| A.33 | Viber button visible with pre-filled message | Viber link opens with order details | 🖐 | ☐ | | | | |
| A.34 | Telegram button visible with pre-filled message | Telegram link opens with order details | 🖐 | ☐ | | | | |
| A.35 | Invalid confirmation token returns 404 | Modify token in URL → 404 error | 🔧 | ☐ | | | | |
| A.36 | No token returns 404 | Remove token parameter → 404 error | 🔧 | ☐ | | | | |
| A.37 | Order data saved before external link | Database contains order record | 🔧 | ☐ | | | | |
| A.38 | Cross-store token access blocked | Token from Store A used on Store B URL → 404 | 🔧 | ☐ | | | | |

### SEO

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.39 | GET `/robots.txt` returns 200 | Dynamic robots.txt with sitemap URL | 🔧 | ☐ | | | | |
| A.40 | GET `/sitemap.xml` returns 200 | XML sitemap with /, /products, /glass-finder | 🔧 | ☐ | | | | |

### Security

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| A.41 | Homepage returns security headers | X-Frame-Options, CSP, X-Content-Type-Options present | 🔧 | ☐ | | | | |

---

## B. Retail Customer (Logged In)

**Test credentials:** Phone `09100000006`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| B.1 | Register new account | Registration form accepts phone & password | 🔧 | ☐ | | | | |
| B.2 | Login with phone & password | Successful login → redirect to home | 🔧 | ☐ | | | | |
| B.3 | Login with wrong password | Error message displayed | 🔧 | ☐ | | | | |
| B.4 | Logout | Session cleared, redirect to home | 🔧 | ☐ | | | | |
| B.5 | Account page accessible | Account dashboard loads with name and phone | 🔧 | ☐ | | | | |
| B.6 | Favorites page accessible | Shows favorited products (if any) | 🔧 | ☐ | | | | |
| B.7 | Order history shows own orders | Order list shows ORD-UAT-004 and any new orders | 🔧 | ☐ | | | | |
| B.8 | Click order → order detail opens | Order detail page shows items and status | 🔧 | ☐ | | | | |
| B.9 | Own order confirmation accessible | Logged in → confirmation page loads without token | 🔧 | ☐ | | | | |
| B.10 | Cannot access another customer's order | Try another user's order URL → 403 | 🔧 | ☐ | | | | |
| B.11 | Retail price visible (same as guest) | Price shows retail price | 🔧 | ☐ | | | | |
| B.12 | Glass Finder — can favorite result | Heart icon toggles favorite (requires login) | 🔧 | ☐ | | | | |

---

## C. Wholesale Customer

### Pending Wholesale Customer

**Test credentials:** Phone `09100000005`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| C.1 | Login as pending wholesale user | Login succeeds | 🔧 | ☐ | | | | |
| C.2 | Browse products — retail price only | Wholesale price NOT visible | 🔧 | ☐ | | | | |
| C.3 | Order request uses retail pricing | Total calculated at retail price | 🔧 | ☐ | | | | |
| C.4 | Approval status displayed on account | "Wholesale application pending" message shown | 🖐 | ☐ | | | | |

### Approved Wholesale Customer

**Test credentials:** Phone `09100000004`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| C.5 | Login as approved wholesale user | Login succeeds | 🔧 | ☐ | | | | |
| C.6 | Browse products — wholesale price visible | Both prices shown; retail may be crossed out | 🔧 | ☐ | | | | |
| C.7 | Order request uses wholesale pricing | Total calculated at wholesale price | 🔧 | ☐ | | | | |
| C.8 | Retail-only promotions not accessible | N/A (no retail-only promos exist) | 🖐 | ☐ | | | | |

---

## D. Store Manager

**Test credentials:** Phone `09100000002`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| D.1 | Access `/store/datapos-mobile/admin/dashboard` | Dashboard loads with store statistics | 🔧 | ☐ | | | | |
| D.2 | Dashboard shows order count, product count | Statistics panels render with data | 🖐 | ☐ | | | | |
| D.3 | Product list page loads | All 25 products listed with pagination | 🔧 | ☐ | | | | |
| D.4 | Create new product | Product created; visible in listing | 🔧 | ☐ | | | | |
| D.5 | Edit existing product | Changes saved and displayed | 🔧 | ☐ | | | | |
| D.6 | Delete product | Product removed from listing | 🔧 | ☐ | | | | |
| D.7 | Toggle featured status | Product featured badge toggles | 🔧 | ☐ | | | | |
| D.8 | Upload product image | Image upload works, preview shows | 🖐 | ☐ | | | | |
| D.9 | Change stock status | Switch between in_stock / out_of_stock | 🔧 | ☐ | | | | |
| D.10 | Category list page | All 5 categories listed | 🔧 | ☐ | | | | |
| D.11 | Create category | Category created; assignable to products | 🔧 | ☐ | | | | |
| D.12 | Edit category | Category name/description updated | 🔧 | ☐ | | | | |
| D.13 | Delete category | Category removed | 🔧 | ☐ | | | | |
| D.14 | Brand list page | All 5 brands listed | 🔧 | ☐ | | | | |
| D.15 | Create brand | Brand created; assignable to products | 🔧 | ☐ | | | | |
| D.16 | Edit brand | Brand name updated | 🔧 | ☐ | | | | |
| D.17 | Delete brand | Brand removed | 🔧 | ☐ | | | | |
| D.18 | Product CSV import page loads | Import form with file upload visible | 🖐 | ☐ | | | | |
| D.19 | Valid CSV import creates products | Products from CSV appear in listing | 🔧 | ☐ | | | | |
| D.20 | Duplicate SKU in import is skipped | Second row with existing SKU not duplicated | 🔧 | ☐ | | | | |
| D.21 | Glass Finder admin page loads | Glass items list with CRUD | 🔧 | ☐ | | | | |
| D.22 | Create Glass Finder item | New glass record saves | 🔧 | ☐ | | | | |
| D.23 | Glass Finder CSV import | Records from CSV import correctly | 🔧 | ☐ | | | | |
| D.24 | Wholesale applications page loads | Lists pending + approved applications | 🔧 | ☐ | | | | |
| D.25 | Approve wholesale application | Status changes; user sees wholesale price | 🔧 | ☐ | | | | |
| D.26 | Reject wholesale application | Status changes; notes field available | 🔧 | ☐ | | | | |
| D.27 | Order list page loads | All 4 UAT orders visible | 🔧 | ☐ | | | | |
| D.28 | View order detail | Order items, customer info, status displayed | 🔧 | ☐ | | | | |
| D.29 | Update order status | Status changes correctly (pending_contact → confirmed → cancelled) | 🔧 | ☐ | | | | |
| D.30 | Store settings page loads | Settings form with store info | 🔧 | ☐ | | | | |
| D.31 | Update store settings | Changes saved and visible on storefront | 🖐 | ☐ | | | | |
| D.32 | Banner management page loads | Banner list with CRUD | 🔧 | ☐ | | | | |
| D.33 | Upload new banner | Banner appears on storefront home page | 🖐 | ☐ | | | | |
| D.34 | Product search in admin | Search by name or SKU → filtered results | 🔧 | ☐ | | | | |
| D.35 | Cross-store admin access blocked | `/store/uat-store-b/admin/dashboard` → 403 Forbidden | 🔧 | ☐ | | | | |

---

## E. Staff

**Test credentials:** Phone `09100000003`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| E.1 | Access admin dashboard | Dashboard loads | 🔧 | ☐ | | | | |
| E.2 | Manage products (same as manager) | Product CRUD available | 🔧 | ☐ | | | | |
| E.3 | Manage categories (same as manager) | Category CRUD available | 🔧 | ☐ | | | | |
| E.4 | Manage brands (same as manager) | Brand CRUD available | 🔧 | ☐ | | | | |
| E.5 | Manage orders (same as manager) | Order list + status update available | 🔧 | ☐ | | | | |
| E.6 | Manage Glass Finder (same as manager) | Glass Finder CRUD + import available | 🔧 | ☐ | | | | |
| E.7 | **Blocked:** Store settings page | `/admin/settings` → 403 | 🔧 | ☐ | | | | |
| E.8 | Platform owner dashboard blocked | `/admin/dashboard` → 403 (unless owner) | 🔧 | ☐ | | | | |
| E.9 | Store A staff cannot access Store B | `/store/uat-store-b/admin/dashboard` → 403 | 🔧 | ☐ | | | | |

---

## F. Platform Owner & Multi-Store Isolation

**Test credentials:** Phone `09100000001`, Password `password`

| # | Test | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| F.1 | Access `/admin/dashboard` | Platform owner dashboard loads with store selection | 🔧 | ☐ | | | | |
| F.2 | Access any store dashboard | Platform owner can view both Store A and Store B admin | 🔧 | ☐ | | | | |
| F.3 | No store-level restrictions | Platform owner can switch stores seamlessly | 🔧 | ☐ | | | | |
| F.4 | Cross-store data isolation | Store A catalog (25 products) and Store B catalog (2 products) never leak into each other | 🔧 | ☐ | | | | |

---

## G. Device & Screen Testing

| # | Test | Device/Width | Expected Result | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| G.1 | Homepage layout | Desktop (1920px) | Full layout, no overflow | 🖐 | ☐ | | | | |
| G.2 | Homepage layout | Tablet (768px) | 3-col product grid, nav adapts | 🖐 | ☐ | | | | |
| G.3 | Homepage layout | Mobile 430px | 2-col product grid, bottom nav visible | 🖐 | ☐ | | | | |
| G.4 | Homepage layout | Mobile 390px | No horizontal scroll, form usable | 🖐 | ☐ | | | | |
| G.5 | Homepage layout | Mobile 360px | Smallest width, all content accessible | 🖐 | ☐ | | | | |
| G.6 | Product grid | Desktop | 5 columns | 🖐 | ☐ | | | | |
| G.7 | Product grid | Tablet | 3 columns | 🖐 | ☐ | | | | |
| G.8 | Product grid | Mobile | 2 columns | 🖐 | ☐ | | | | |
| G.9 | Order form at mobile width | 360-430px | Fields stack, Viber/Telegram buttons visible | 🖐 | ☐ | | | | |
| G.10 | Confirmation page at mobile width | 360-430px | Buttons visible, no overflow | 🖐 | ☐ | | | | |
| G.11 | Dark theme | All widths | Readable contrast, images visible | 🖐 | ☐ | | | | |
| G.12 | Myanmar text overflow | All widths | Burmese characters don't break layout | 🖐 | ☐ | | | | |

---

## H. Business Workflow Tests

### Order Lifecycle (Guest)

```
Guest browses → submits order → confirmation page → admin confirms → status updated
```

| # | Step | Expected | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| H.1 | Guest browses products, adds to cart | Product detail visible with Order button | 🔧 | ☐ | | | | |
| H.2 | Submits order request | Order saved in DB, redirects to confirmation | 🔧 | ☐ | | | | |
| H.3 | Confirmation page displays | Shows order summary, Viber/Telegram buttons | 🔧 | ☐ | | | | |
| H.4 | Admin sees pending order | Admin order list shows new order | 🔧 | ☐ | | | | |
| H.5 | Admin confirms order | Status changes from pending_contact to confirmed | 🔧 | ☐ | | | | |
| H.6 | Admin can cancel order | Status changes to cancelled | 🔧 | ☐ | | | | |

### Wholesale Lifecycle

```
Customer applies → pending → admin approves → wholesale price visible
```

| # | Step | Expected | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| H.7 | Customer submits wholesale application | Application appears in admin list | 🔧 | ☐ | | | | |
| H.8 | Application shows as pending | Status = pending | 🔧 | ☐ | | | | |
| H.9 | Admin approves application | Pivot role changes to wholesale_customer active | 🔧 | ☐ | | | | |
| H.10 | Customer logs in after approval | Wholesale price visible on products | 🔧 | ☐ | | | | |
| H.11 | Wholesale order uses correct price | Total = wholesale price × quantity | 🔧 | ☐ | | | | |

### Glass Finder Lifecycle

```
Customer searches → compatible results → favorites → order via chat
```

| # | Step | Expected | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| H.12 | Customer searches by model | Compatible glasses listed | 🔧 | ☐ | | | | |
| H.13 | Customer favorites a result | Favorite saved (requires login) | 🔧 | ☐ | | | | |
| H.14 | Order via chat button | Viber/Telegram link opens | 🖐 | ☐ | | | | |
| H.15 | Admin imports glass CSV records | Records added without duplicates | 🔧 | ☐ | | | | |

---

## I. Data Integrity & Multi-Store Isolation Checks

| # | Check | Expected | Type | Result | Tester | Date | Notes | Screenshot |
|---|---|---|---|---|---|---|---|---|
| I.1 | Cross-store product isolation | Store A catalog shows 25 items; Store B catalog shows 2 items; zero leakage | 🔧 | ☐ | | | | |
| I.2 | Cross-store order isolation | Store A admin sees 4 orders; Store B admin sees 1 order; zero leakage | 🔧 | ☐ | | | | |
| I.3 | Cross-store category isolation | Categories are strictly store-scoped (Store A: 5, Store B: 1) | 🔧 | ☐ | | | | |
| I.4 | Cross-store brand isolation | Brands are strictly store-scoped (Store A: 5, Store B: 1) | 🔧 | ☐ | | | | |
| I.5 | Cross-customer order isolation | Customer A cannot view Customer B order confirmation (403) | 🔧 | ☐ | | | | |
| I.6 | Guest token cannot be enumerated | Invalid token → 404, not 200 | 🔧 | ☐ | | | | |
| I.7 | Prices calculated server-side | Price manipulation via request payload invalid | 🔧 | ☐ | | | | |
| I.8 | Wholesale price for approved only | Non-wholesale users see retail price only | 🔧 | ☐ | | | | |
| I.9 | Out-of-stock order blocked | Ordering out-of-stock item → validation error, no order created | 🔧 | ☐ | | | | |
| I.10 | Duplicate SKU protection | Same SKU in same store → rejected with error | 🔧 | ☐ | | | | |
| I.11 | Glass Finder duplicate handling | Duplicate (store, model, glass_code) row in CSV → skipped | 🔧 | ☐ | | | | |
| I.12 | Store manager isolation | Store A manager (`09100000002`) blocked from Store B admin (`uat-store-b`) with 403 | 🔧 | ☐ | | | | |
| I.13 | Store B manager isolation | Store B manager (`09100000007`) blocked from Store A admin (`datapos-mobile`) with 403 | 🔧 | ☐ | | | | |
| I.14 | Glass Finder record isolation | Store A Glass Finder items (15) and Store B items (1) never mix | 🔧 | ☐ | | | | |
| I.15 | Dashboard cache non-leakage | Statistics cached for Store A do not overwrite or appear on Store B dashboard | 🔧 | ☐ | | | | |

---

## J. Local Environment Checks

| # | Check | Expected | Actual Evidence | Result |
|---|---|---|---|---|
| J.1 | PHP version | 8.2.x | `PHP 8.2.12 (cli)` | PASS (☐) |
| J.2 | Required extensions | `mbstring`, `fileinfo`, `pdo_sqlite`, `pdo_mysql` loaded | All present in `php -m` | PASS (☐) |
| J.3 | GD extension | Optional warning / prerequisite | Not loaded in CLI `php -m`. Verified raw image uploads (JPEG, WebP, PNG) succeed via storage disk. Warning recorded for future image resizing. | WARNING (☐) |
| J.4 | SQLite connection | Database file writable, migrations active | `database.sqlite` loaded via SQLite connection | PASS (☐) |
| J.5 | Storage link exists | `public/storage` → `storage/app/public` | `<project-root>\public\storage` LINKED | PASS (☐) |
| J.6 | Storage directories writable | Cache, logs, sessions, views | All framework storage subdirectories writable | PASS (☐) |
| J.7 | APP_KEY set | 32-byte key generated | `APP_KEY` set (base64 string verified present) | PASS (☐) |
| J.8 | APP_DEBUG | `true` (local environment) | `Debug Mode: ENABLED` | PASS (☐) |
| J.9 | Cache driver | `file` or `database` | `database` cache driver active | PASS (☐) |
| J.10 | Queue driver | `sync` for local UAT | `database` driver active (0 pending jobs). Recommended: `QUEUE_CONNECTION=sync` for synchronous UAT execution. | PASS (☐) |
| J.11 | Mail dependency | Log driver | `MAIL_MAILER=log` (Viber/Telegram customer contact) | PASS (☐) |

---

## K. Manual UAT Execution Sessions

### Session 1: Guest and Mobile Storefront

**Focus:** Unauthenticated browsing, product discovery, Glass Finder, mobile responsiveness.
**Requires:** No login. Open browser dev tools for mobile widths.

| Step | What to do | Key tests to check |
|---|---|---|
| 1.1 | Open `/?store_slug=datapos-mobile` | A.1–A.5, G.1–G.5 |
| 1.2 | Browse to `/products?store_slug=datapos-mobile` | A.6–A.11, G.6–G.8 |
| 1.3 | Click a product → detail page | A.12–A.17 |
| 1.4 | Open `/glass-finder?store_slug=datapos-mobile` | A.18–A.23 |
| 1.5 | Submit a guest order (with address) | A.24–A.32, H.1–H.3 |
| 1.6 | Verify confirmation page renders | A.33–A.34, H.3 |
| 1.7 | Try invalid token → 404 | A.35–A.36 |
| 1.8 | Verify robots.txt and sitemap.xml | A.39–A.40 |
| 1.9 | Verify security headers in browser dev tools | A.41 |

**Session 1 estimated time:** 30 minutes

---

### Session 2: Retail and Wholesale Accounts

**Focus:** Registration, login, account management, price visibility, ordering.
**Requires:** Retail customer and wholesale user accounts.

| Step | What to do | Key tests to check |
|---|---|---|
| 2.1 | Register a new account | B.1 |
| 2.2 | Login as Ma Su (`09100000006` / `password`) | B.2–B.4 |
| 2.3 | Browse account, favorites, orders | B.5–B.10 |
| 2.4 | Verify retail price only | B.11 |
| 2.5 | Login as U Mya (`09100000005` / `password`) — pending wholesale | C.1–C.4 |
| 2.6 | Verify wholesale price hidden | C.2–C.3 |
| 2.7 | Login as Daw Aye (`09100000004` / `password`) — approved wholesale | C.5–C.8 |
| 2.8 | Verify wholesale price visible | C.6–C.7 |

**Session 2 estimated time:** 25 minutes

---

### Session 3: Manager and Staff Operations

**Focus:** Admin panels, CRUD operations, order management, CSV import.
**Requires:** Store Manager and Staff accounts.

| Step | What to do | Key tests to check |
|---|---|---|
| 3.1 | Login as Mg Hla (`09100000002` / `password`) | D.1–D.2 |
| 3.2 | Browse admin dashboard | D.1–D.2 |
| 3.3 | Create/edit/delete a product | D.3–D.9 |
| 3.4 | Create/edit/delete categories and brands | D.10–D.17 |
| 3.5 | View and update order status | D.27–D.29 |
| 3.6 | Browse wholesale applications, approve pending | D.24–D.26 |
| 3.7 | Browse Glass Finder admin | D.21–D.23 |
| 3.8 | Access store settings | D.30–D.31 |
| 3.9 | Login as Ko Kyaw (`09100000003` / `password`) | E.1–E.9 |
| 3.10 | Verify staff cannot access settings | E.7 |

**Session 3 estimated time:** 40 minutes

---

### Session 4: Platform Owner and Cross-Store Isolation

**Focus:** Store switching, cross-store data isolation.
**Requires:** Platform Owner and Store B Manager accounts.

| Step | What to do | Key tests to check |
|---|---|---|
| 4.1 | Login as Owner (`09100000001` / `password`) | F.1 |
| 4.2 | Access admin dashboard `/admin/dashboard` | F.1 |
| 4.3 | Access Store A admin | F.2 |
| 4.4 | Access Store B admin | F.2 |
| 4.5 | Login as U Ko Ko (`09100000007` / `password`) | E.1–E.9 |
| 4.6 | Verify Store B manager sees only Store B data | I.1–I.4, I.13 |
| 4.7 | Try to access Store A admin from Store B account | I.1, I.5, I.13 |
| 4.8 | Verify Store A order confirmation cannot be viewed by Store B | I.2, I.5–I.6 |

**Session 4 estimated time:** 20 minutes

---

### Session 5: Complete Business Workflows

**Focus:** End-to-end workflow verification for all 3 core business processes.
**Requires:** Guest browser, Manager login.

| Step | What to do | Key tests to check |
|---|---|---|
| 5.1 | **Order lifecycle:** Guest browses → submits order → confirmation page → manager confirms order | H.1–H.6 |
| 5.2 | **Wholesale lifecycle:** Customer applies → manager approves → wholesale price visible | H.7–H.11 |
| 5.3 | **Glass Finder lifecycle:** Search → favorite → order via chat | H.12–H.15 |
| 5.4 | **Data integrity:** Verify all I.1–I.15 pass | I.1–I.15 |

**Session 5 estimated time:** 20 minutes

---

### Results Summary

| Session | Tests Passed | Tests Failed | Not Tested | Tester Signature | Date |
|---|---|---|---|---|---|
| 1. Guest & Mobile | 0 | 0 | 25 | | |
| 2. Accounts | 0 | 0 | 12 | | |
| 3. Admin | 0 | 0 | 35 | | |
| 4. Isolation | 0 | 0 | 8 | | |
| 5. Workflows | 0 | 0 | 15 | | |

*Note: Manual UAT execution has NOT occurred yet. All manual checklist items remain NOT TESTED.*

---

## Appendix A — UAT Results & Defect Log

**Project:** DataPOS
**Store A:** DataPOS — slug: `datapos-mobile` (25 products, 5 categories, 5 brands, 15 Glass Finder items, 4 orders)
**Store B:** UAT Test Store B — slug: `uat-store-b` (2 products, 1 category, 1 brand, 1 Glass Finder item, 1 order)
**UAT Status:** MANUAL UAT READY (Manual UAT has NOT been executed yet)
**UAT Date:** _________
**Tester:** _________

### Severity Levels

| Severity | Definition | Action |
|---|---|---|
| **Blocker** | Cannot operate the business or exposes customer data | Fix immediately |
| **Critical** | Major workflow broken, no workaround | Fix during UAT |
| **Major** | Important feature works incorrectly | Log for prioritization |
| **Minor** | Cosmetic or low-impact issue | Log for backlog |
| **Warning** | Environment or deployment prerequisite | Document for production setup |

### Defect Log

#### Blocker

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

#### Critical

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

#### Major

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

#### Minor

| ID | Role | Page/Workflow | Steps to Reproduce | Expected | Actual | Screenshot? | Fix Status |
|---|---|---|---|---|---|---|---|
| *(none)* | | | | | | | |

#### Environment Warnings & Deployment Prerequisite Log

| ID | Category | Component | Description | Impact & Empirical Finding | Action Required |
|---|---|---|---|---|---|
| ENV-WARN-001 | PHP Extension | Image Processing (`gd`) | PHP `gd` extension is not loaded in current local CLI PHP (`php -m`). | **Verified Empirical Result:** Real image uploads (JPEG, WebP, PNG) for Product, Category, Brand Logo, and Home Banner succeed using standard file storage. `gd` is NOT required for raw file uploads, but IS required for future image resizing, thumbnail cropping, or `dimensions` validation rules. | Enable `extension=gd` in production `php.ini`. |

### Summary

| Category | Count | Notes |
|---|---|---|
| Blocker | 0 | None identified |
| Critical | 0 | None identified |
| Major | 0 | None (GD reclassified to Environment Warning after verified raw upload success) |
| Minor | 0 | None identified |
| **Total Software Defects** | **0** | Application software logic ready for manual UAT |
| Environment Warnings | 1 | `gd` extension recommended for production deployment |

### Sign-off

- [ ] All Blocker and Critical defects are resolved
- [ ] Core business workflows verified (order, wholesale, glass finder)
- [ ] Data integrity confirmed
- [ ] Device/screen testing completed on at least one mobile width
- [ ] UAT READY for production hosting purchase

**Tester Signature:** _________________ **Date:** _________
**Project Owner Approval:** _________________ **Date:** _________

---

## Appendix B — Local Device / LAN Test Note

### Project Path

```bat
D:\xmapp\htdocs\DataPOS
```

### Tonight — Stop the Laravel Server

Laravel server running terminal တွင်:

```bat
Ctrl + C
```

ပြီးလျှင် Command Prompt window ကိုပိတ်ပြီး PC ကို shutdown လုပ်နိုင်သည်။

### Tomorrow Morning — Start Again

1. **Open Command Prompt**

2. **Go to the project folder**

```bat
cd /d D:\xmapp\htdocs\DataPOS
```

3. **Check the current PC IPv4 address**

```bat
ipconfig
```

Expected IPv4:

```text
192.168.10.161
```

4. **Clear Laravel caches**

```bat
D:\xmapp\php\php.exe artisan optimize:clear
```

5. **Start the Laravel LAN server**

```bat
D:\xmapp\php\php.exe artisan serve --host=0.0.0.0 --port=8500
```

Command Prompt window ကို မပိတ်ရပါ။ Window ပိတ်လျှင် Laravel server ရပ်သွားမည်။

### Phone URLs

#### Storefront

```text
http://192.168.10.161:8500/store/datapos-mobile
```

#### Admin

```text
http://192.168.10.161:8500/store/datapos-mobile/admin/
```

#### Admin Login

- Local DB (SQLite) ထဲမှာ seeded/existing admin account ရှိပြီးသားဆိုရင် အဲဒါကို သုံးပါ။
- မရှိသေးဘူးဆိုရင် interactive prompt နဲ့ ဖန်တီးပါ (platform_owner role):

```bat
D:\xmapp\php\php.exe artisan production:create-admin
```

  (phone format: `09xxxxxxxxx`, password min 12 characters + uppercase + number + symbol)

### Phone Test Requirements

- Phone နှင့် PC ကို router တစ်လုံးတည်း၏ private network တွင်ချိတ်ထားပါ။
- Phone ကို Guest Wi-Fi မချိတ်ပါနှင့်။
- Mobile Data ကို ယာယီပိတ်ထားပါ။
- VPN / Proxy ကို ယာယီပိတ်ထားပါ။
- Router port forwarding မဖွင့်ပါနှင့်။

### If the PC IPv4 Address Changes

ဥပမာ IPv4 အသစ်က:

```text
192.168.10.165
```

`.env` ထဲက:

```env
APP_URL=http://192.168.10.161:8500
```

ကို:

```env
APP_URL=http://192.168.10.165:8500
```

အဖြစ်ပြောင်းပါ။

ပြီးလျှင်:

```bat
D:\xmapp\php\php.exe artisan optimize:clear
D:\xmapp\php\php.exe artisan serve --host=0.0.0.0 --port=8500
```

Phone URL ကိုလည်း IP အသစ်ဖြင့်ဖွင့်ပါ:

```text
http://192.168.10.165:8500/store/datapos-mobile
```

### Current Temporary LAN Configuration

```env
APP_URL=http://192.168.10.161:8500
FORCE_HTTPS=false
SESSION_SECURE_COOKIE=false
```

### Important Notes

- Current local database is SQLite, so XAMPP MySQL ကို start လုပ်ရန်မလိုပါ။
- Windows network profile ကို Private အဖြစ်သတ်မှတ်ထားသည်။
- Firewall rule name:

```text
DataPOS LAN Test TCP 8500
```

- Firewall rule သည် Private profile, TCP port 8500, subnet `192.168.10.0/24` အတွက်သာဖြစ်သည်။
- Tailwind/Vite class အသစ်တွေ ထည့်ပြီးရင် CSS ပြောင်းလဲမှု ဖုန်းမှာ မမြင်ရရင်:

```bat
cd /d D:\xmapp\htdocs\DataPOS
npm run build
```

ပြီးမှ `optimize:clear` + server restart လုပ်ပါ။
