<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use App\Support\AdminListReturn;
use App\Support\ImageOptimizer;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeBannerController extends Controller
{
    private const IMAGE_MAX_KB = 10240;
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $currentPage = $request->query('page', 'home');

        $query = $store->homeBanners();

        if ($currentPage !== 'all') {
            $query->where('page', $currentPage);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('link_url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $sort = $request->input('sort', 'sort_order');
        match ($sort) {
            'newest'     => $query->latest(),
            'oldest'     => $query->oldest(),
            'title_asc'  => $query->orderBy('title', 'asc'),
            default      => $query->orderBy('sort_order', 'asc')->latest(),
        };

        $banners = $query->get();

        // Calculate summary KPI stats for the store
        $allBanners = $store->homeBanners()->get();
        $stats = [
            'total'        => $allBanners->count(),
            'active'       => $allBanners->where('is_active', true)->count(),
            'hidden'       => $allBanners->where('is_active', false)->count(),
            'home'         => $allBanners->where('page', 'home')->count(),
            'glass_finder' => $allBanners->where('page', 'glass_finder')->count(),
        ];

        AdminListReturn::capture($request, 'admin_banners_return');

        $imageMaxMb = self::IMAGE_MAX_KB / 1024;

        return view('admin.banners.index', compact('store', 'banners', 'currentPage', 'stats', 'imageMaxMb', 'search', 'sort'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        $validated = $request->validate([
            'title'      => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'page'       => ['required', 'string', 'in:home,glass_finder'],
            'image'      => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
            // Accept both full URLs (https://...) and in-store relative links (/products?...).
            'link_url'   => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $isValidUrl = filter_var($value, FILTER_VALIDATE_URL) !== false;
                $isValidRelative = is_string($value)
                    && str_starts_with($value, '/')
                    && filter_var('http://example.com' . $value, FILTER_VALIDATE_URL) !== false;
                if (! $isValidUrl && ! $isValidRelative) {
                    $fail('The link URL must be a valid full URL or a path starting with "/".');
                }
            }],
            'sort_order' => ['integer', 'min:0'],
            'is_active'  => ['boolean'],
        ]);

        $imagePath = ImageOptimizer::store($request->file('image'), 'banners', 1600);

        $store->homeBanners()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'page' => $validated['page'],
            'image_path' => $imagePath,
            'link_url' => $validated['link_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Banner created successfully.');
    }

    public function destroy(string $store_slug, HomeBanner $banner, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        // Ensure banner belongs to current store
        if ($banner->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store banner.');
        }

        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        return back()->with('success', 'Banner deleted successfully.');
    }

    public function edit(string $store_slug, HomeBanner $banner, StoreContext $context): View
    {
        $store = $context->getStore();

        // Ensure banner belongs to current store
        if ($banner->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store banner.');
        }

        $imageMaxMb = self::IMAGE_MAX_KB / 1024;
        $returnTo = AdminListReturn::peek('admin_banners_return', '/store/' . $store->slug . '/admin/banners');

        return view('admin.banners.edit', compact('store', 'banner', 'imageMaxMb', 'returnTo'));
    }

    public function update(Request $request, string $store_slug, HomeBanner $banner, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        // Ensure banner belongs to current store
        if ($banner->store_id !== $store->id) {
            abort(403, 'Unauthorized action on store banner.');
        }

        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            // Accept both full URLs (https://...) and in-store relative links (/products?...).
            'link_url' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                $isValidUrl = filter_var($value, FILTER_VALIDATE_URL) !== false;
                $isValidRelative = is_string($value)
                    && str_starts_with($value, '/')
                    && filter_var('http://example.com' . $value, FILTER_VALIDATE_URL) !== false;
                if (! $isValidUrl && ! $isValidRelative) {
                    $fail('The link URL must be a valid full URL or a path starting with "/".');
                }
            }],
            'image'    => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:' . self::IMAGE_MAX_KB],
        ]);

        $data = [
            'title'    => $validated['title'],
            'description' => $validated['description'] ?? null,
            'link_url' => $validated['link_url'] ?? null,
        ];

        // Uploading a new image replaces the current one.
        if ($request->hasFile('image')) {
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }
            $data['image_path'] = ImageOptimizer::store($request->file('image'), 'banners', 1600);
        }

        $banner->update($data);

        return redirect(AdminListReturn::resolve('admin_banners_return', '/store/' . $store->slug . '/admin/banners'))
            ->with('success', 'Banner updated successfully.');
    }

    public function export(Request $request, StoreContext $context): BinaryFileResponse|StreamedResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $currentPage = $request->query('page', 'home');
        $query = $store->homeBanners();

        if ($currentPage !== 'all') {
            $query->where('page', $currentPage);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('link_url', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $sort = $request->input('sort', 'sort_order');
        match ($sort) {
            'newest'     => $query->latest(),
            'oldest'     => $query->oldest(),
            'title_asc'  => $query->orderBy('title', 'asc'),
            default      => $query->orderBy('sort_order', 'asc')->latest(),
        };

        $banners = $query->get();
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            $filename = 'banners-' . $store->slug . '-' . now()->format('Ymd-His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->streamDownload(function () use ($banners) {
                $stream = fopen('php://output', 'w');
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, ['#', 'Title', 'Description', 'Page', 'Link URL', 'Sort Order', 'Status', 'Created At']);

                foreach ($banners as $idx => $banner) {
                    fputcsv($stream, [
                        $idx + 1,
                        $this->csvCell($banner->title),
                        $this->csvCell($banner->description ?? ''),
                        $this->csvCell($banner->page),
                        $this->csvCell($banner->link_url ?? ''),
                        (int) $banner->sort_order,
                        $banner->is_active ? 'Active' : 'Hidden',
                        $banner->created_at ? $banner->created_at->format('Y-m-d H:i') : '',
                    ]);
                }

                fclose($stream);
            }, $filename, $headers);
        }

        $filename = 'Banners_' . $store->slug . '_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'datapos_banner_');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Banners');

        // Header Title Block
        $sheet->setCellValue('A1', $store->name . ' - Storefront Banners');
        $sheet->setCellValue('A2', 'Export Date: ' . now()->format('d/m/Y h:i A') . ' | Total Count: ' . $banners->count());
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('0284C7');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB('64748B');

        $row = 4;
        $headers = [
            'A' => '#',
            'B' => 'Title',
            'C' => 'Description',
            'D' => 'Page',
            'E' => 'Link URL',
            'F' => 'Sort Order',
            'G' => 'Status',
            'H' => 'Created At',
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue("{$col}{$row}", $title);
        }

        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0284C7']],
        ]);

        $row++;
        foreach ($banners as $idx => $banner) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $banner->title);
            $sheet->setCellValue("C{$row}", $banner->description ?? '');
            $sheet->setCellValue("D{$row}", $banner->page);
            $sheet->setCellValue("E{$row}", $banner->link_url ?? '');
            $sheet->setCellValue("F{$row}", (int) $banner->sort_order);
            $sheet->setCellValue("G{$row}", $banner->is_active ? 'Active' : 'Hidden');
            $sheet->setCellValue("H{$row}", $banner->created_at ? $banner->created_at->format('d/m/Y h:i A') : '');

            // Alternate row shading
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            $row++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function csvCell(mixed $value): string
    {
        $value = (string) $value;
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
