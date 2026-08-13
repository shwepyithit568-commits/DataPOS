# Database & Performance Optimization Audit

**Project:** DataPOS  
**Date:** 2026-07-28  
**Audit Type:** Production Database & Performance Optimization  
**Stack:** Laravel 12 / Livewire 4 / MySQL / PHP 8.2

---

## Audit Scope

- 16 migration files (full schema + index analysis)
- 13 Eloquent models (relationships, casts, boot methods)
- 16 controllers (query patterns, N+1 risk, pagination)
- 3 services (import logic, code normalization)
- 5 config files (cache, database, queue, filesystem)
- 25+ Blade views (relationship access patterns)
- CSV import logic (product + glass finder)
- Image storage strategy

---

## Findings Summary

| # | Finding | Category | Severity | Status |
|---|---------|----------|----------|--------|
| 1 | Missing indexes on `products.stock_status`, `products.is_featured`, `orders.created_at` | Indexes | Medium | ✅ New migration |
| 2 | CSV imports run 1 `SELECT` per row for duplicate checks | Import | Medium | ✅ Pre-loaded sets |
| 3 | CSV imports not wrapped in DB transactions | Import | Medium | ✅ `DB::transaction()` |
| 4 | Dashboard runs 7 uncached COUNT queries per page load | Caching | Low | ✅ `Cache::remember()` |
| 5 | Cache driver defaults to `database` (DB reads cache from DB) | Caching | Medium | ✅ Doc recommendation |
| 6 | No N+1 queries found | N+1 | — | ✅ Clean |
| 7 | No image compression/optimization | Storage | Low | ✅ Doc recommendation |
| 8 | `LIKE '%term%'` search (full table scan) | Query | Low | Noted — acceptable <5K products |

---

## Finding Details

### Finding 1: Missing Indexes

**Tables and columns queried without purpose-built indexes:**

| Table | Column | Queried By | Frequency |
|-------|--------|-----------|-----------|
| `products` | `stock_status` | Dashboard COUNT (where stock_status = 'in_stock' / 'out_of_stock') | Every admin page load |
| `products` | `is_featured` | Future product listing feature | N/A (column exists) |
| `orders` | `created_at` | `latest()` sort on admin + customer order lists | Every order list page |

**Note on FK columns:** InnoDB automatically creates indexes on foreign key columns.  
Columns like `store_id`, `category_id`, `brand_id`, `user_id`, `order_id`, `product_id`  
all have implicit FK indexes and are **not** missing.

**Fix:** Created migration `2026_07_28_020000_add_performance_indexes.php`.

---

### Finding 2: Per-Row Duplicate SELECTs in CSV Imports

**Before (Product Import):**
```php
// Inside loop — runs per row:
Product::where('store_id', $store->id)->where('sku', $data['sku'])->exists();
Brand::firstOrCreate(...);  // SELECT + maybe INSERT
Category::firstOrCreate(...); // SELECT + maybe INSERT
// ~3-4 queries per row → 3000-4000 for 1000 products
```

**Before (Glass Finder Import):**
```php
GlassFinderItem::where('store_id', $store->id)
    ->where('phone_model', $phoneModel)
    ->where('normalized_glass_code', $normalizedCode)
    ->exists();
// ~2 queries per row → 2000 for 1000 items
```

**Fix:** Pre-load existing keys into memory before the loop:

```php
// Product — one query:
$existingSkus = Product::where('store_id', $id)->pluck('sku')->toArray();
// Then O(1) check: isset($existingSkuSet[$skuKey])

// Glass Finder — one query:
$existingKeys = GlassFinderItem::where('store_id', $id)->get(...);
// Then O(1) check: isset($existingKeySet[$lookupKey])
```

**For 1000 rows:** ~3000 queries → ~1001 queries (one pre-load + one INSERT per row).

---

### Finding 3: Missing DB Transactions in Imports

Both importers ran row-by-row outside a transaction. On failure midway through:
- Previously imported rows were **not rolled back**.
- Partial imports could leave the database in an inconsistent state.

**Fix:** Wrapped both import loops in `DB::beginTransaction()` / `DB::commit()` with `try/catch` rollback.

---

### Finding 4: Uncache Dashboard Aggregation Queries

`DashboardController::index()` ran these 7 COUNT queries on every page load:

1. `SELECT COUNT(*) FROM products WHERE store_id = ?`
2. `SELECT COUNT(*) FROM products WHERE store_id = ? AND stock_status = 'in_stock'`
3. `SELECT COUNT(*) FROM products WHERE store_id = ? AND stock_status = 'out_of_stock'`
4. `SELECT COUNT(*) FROM orders WHERE store_id = ? AND status = 'pending_contact'`
5. `SELECT COUNT(*) FROM orders WHERE store_id = ? AND status = 'confirmed'`
6. `SELECT COUNT(*) FROM wholesale_applications WHERE store_id = ? AND status = 'pending'`
7. `SELECT COUNT(*) FROM glass_finder_items WHERE store_id = ?`

These are aggregation stats — they don't need real-time precision.  
Admin users can tolerate 60-second staleness.

**Fix:** Wrapped in `Cache::remember('dashboard.stats.' . $storeId, 60, function() { ... })`.

---

### Finding 5: No N+1 Queries Found

Traced every controller → view path:

| Controller | Eager Loading | N+1 Risk |
|-----------|---------------|----------|
| CatalogController::index() | `->with(['category', 'brand'])` | ✅ None |
| CatalogController::show() | `->with(['category', 'brand'])` | ✅ None |
| OrderAdminController::index() | `->with(['items', 'user'])` | ✅ None |
| OrderAdminController::show() | `->with(['items', 'user'])` | ✅ None |
| AccountController::orders() | `->with(['items'])` | ✅ None |
| AccountController::favorites() | `->with(['glassItem'])` | ✅ None |
| DashboardController::index() | Recent queries use `->with()` | ✅ None |

---

### Finding 6: Image Storage

| Aspect | Current | Recommendation |
|--------|---------|---------------|
| Storage driver | Local `public` disk | Adequate for single-server MVP |
| Max upload size | 2 MB | Adequate |
| Accepted formats | PNG, JPG, JPEG, WebP | Good |
| Compression | None (originals stored) | Pre-compress to ~100-200 KB before uploading |
| Thumbnails | None | Acceptable for MVP |
| CDN | None | Deferred — add when multi-server |

---

## Files Changed / Created

| File | Change |
|------|--------|
| `database/migrations/2026_07_28_020000_add_performance_indexes.php` | **Created** — indexes on `stock_status`, `is_featured`, `created_at` |
| `app/Services/ProductImportService.php` | Pre-loaded SKU/brand/category sets + DB transaction |
| `app/Http/Controllers/Admin/GlassFinderAdminController.php` | Pre-loaded duplicate key set + DB transaction |
| `app/Http/Controllers/Admin/DashboardController.php` | 60-second Cache::remember for aggregation stats |
| `docs/production-deployment-guide.md` | Performance recommendations section + cache/image/import docs |

---

## Pre-Deployment Performance Checklist

- [ ] Run `php artisan migrate` to apply performance indexes
- [ ] Set `CACHE_STORE=file` in `.env` (or `redis` if available)
- [ ] Run `php artisan optimize` for route/config/view caching
- [ ] Compress product/category images before uploading (target <200 KB)
- [ ] For large CSV imports (>5000 rows), increase PHP `memory_limit` to 256M

---

*This report was generated as part of the Database & Performance Optimization Audit task.*
