<?php

namespace App\POS\Services;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\Models\VoucherTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VoucherTemplateService
{
    /**
     * Ensure default templates exist for the store (80mm, 58mm, A4, A5).
     */
    public function ensureDefaultTemplates(Store $store): void
    {
        $count = VoucherTemplate::where('store_id', $store->id)->count();
        if ($count > 0) {
            return;
        }

        $defaults = [
            [
                'name' => 'POS Invoice (POS အရောင်းပြေစာ)',
                'document_type' => 'pos_sale',
                'paper_size' => '80mm',
                'style_preset' => 'clean_minimal',
                'header_title' => $store->name,
                'header_subtitle' => 'Mobile Phones, Electronics & POS Services',
                'show_logo' => true,
                'address' => $store->address ?? 'Yangon, Myanmar',
                'phone' => $store->phone ?? '09-123456789',
                'show_qr' => true,
                'qr_type' => 'kpay',
                'qr_label' => 'Scan to pay with KPay / Wave',
                'show_customer_info' => true,
                'show_cashier_name' => true,
                'show_tax_breakdown' => true,
                'show_discount_line' => true,
                'show_barcode' => true,
                'footer_greeting' => 'Thank you for shopping with us! ကျေးဇူးတင်ပါသည်',
                'footer_policy' => 'Goods once sold are not returnable without receipt.',
                'font_size' => 'medium',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Service Invoice (ဖုန်းပြင်/ဝန်ဆောင်မှု ဘောက်ချာ)',
                'document_type' => 'repair',
                'paper_size' => 'a5',
                'style_preset' => 'modern_tech',
                'header_title' => $store->name,
                'header_subtitle' => 'Service Job & Parts Delivery Voucher',
                'show_logo' => true,
                'address' => $store->address ?? 'Yangon, Myanmar',
                'phone' => $store->phone ?? '09-123456789',
                'show_qr' => true,
                'qr_type' => 'kpay',
                'qr_label' => 'KPay / Wave QR',
                'show_customer_info' => true,
                'show_cashier_name' => true,
                'show_tax_breakdown' => true,
                'show_discount_line' => true,
                'show_barcode' => true,
                'footer_greeting' => 'We appreciate your trust in our service!',
                'footer_policy' => '30-day warranty on replaced parts and service labor.',
                'font_size' => 'medium',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Commercial Tax Invoice (ကုမ္ပဏီသုံး အခွန်ပြေစာ)',
                'document_type' => 'invoice',
                'paper_size' => 'a4',
                'style_preset' => 'classic_border',
                'header_title' => $store->name,
                'header_subtitle' => 'Official Commercial Sales & Tax Invoice',
                'show_logo' => true,
                'address' => $store->address ?? 'No. 123, Bogyoke Road, Yangon, Myanmar',
                'phone' => $store->phone ?? '09-123456789 / 09-987654321',
                'show_qr' => true,
                'qr_type' => 'bank',
                'qr_label' => 'Direct Bank Transfer / Pay with Mobile Banking',
                'show_customer_info' => true,
                'show_cashier_name' => true,
                'show_tax_breakdown' => true,
                'show_discount_line' => true,
                'show_barcode' => true,
                'footer_greeting' => 'Thank you for your business!',
                'footer_policy' => '1. Warranty void if seal is broken. 2. Please retain invoice for warranty claims.',
                'font_size' => 'medium',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Mini Slip (လက်ကိုင်ပြေစာအကျဉ်း)',
                'document_type' => 'mini_slip',
                'paper_size' => '58mm',
                'style_preset' => 'clean_minimal',
                'header_title' => $store->name,
                'header_subtitle' => null,
                'show_logo' => false,
                'address' => $store->address ?? 'Yangon',
                'phone' => $store->phone ?? '09-123456789',
                'show_qr' => true,
                'qr_type' => 'kpay',
                'qr_label' => 'Scan to Pay',
                'show_customer_info' => true,
                'show_cashier_name' => true,
                'show_tax_breakdown' => false,
                'show_discount_line' => true,
                'show_barcode' => true,
                'footer_greeting' => 'Thank you! ကျေးဇူးတင်ပါသည်',
                'footer_policy' => 'No return without slip.',
                'font_size' => 'small',
                'is_default' => true,
                'is_active' => true,
            ],
        ];

        foreach ($defaults as $tmpl) {
            VoucherTemplate::create(array_merge($tmpl, ['store_id' => $store->id]));
        }
    }

    /**
     * Get all voucher templates for store.
     *
     * @return Collection<int, VoucherTemplate>
     */
    public function getTemplates(Store $store): Collection
    {
        $this->ensureDefaultTemplates($store);

        return VoucherTemplate::where('store_id', $store->id)
            ->orderByDesc('is_default')
            ->orderBy('paper_size')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the active default template for a paper size.
     */
    public function getActiveTemplate(Store $store, string $paperSize = '80mm'): ?VoucherTemplate
    {
        $this->ensureDefaultTemplates($store);

        $template = VoucherTemplate::where('store_id', $store->id)
            ->where('paper_size', $paperSize)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (!$template) {
            $template = VoucherTemplate::where('store_id', $store->id)
                ->where('paper_size', $paperSize)
                ->where('is_active', true)
                ->first();
        }

        return $template ?? VoucherTemplate::where('store_id', $store->id)->first();
    }

    /**
     * Get default paper sizes for all printable documents.
     *
     * @return array<string, string>
     */
    public function getDocumentDefaults(Store $store): array
    {
        return $store->setting?->documentVoucherDefaults() ?? \App\Models\StorefrontSetting::DEFAULT_DOCUMENT_VOUCHER_SIZES;
    }

    /**
     * Get configured default paper size for a specific document type.
     */
    public function getDocumentPaperSize(Store $store, string $documentType, string $fallback = '80mm'): string
    {
        return $store->setting?->documentVoucherSize($documentType) ?? (\App\Models\StorefrontSetting::DEFAULT_DOCUMENT_VOUCHER_SIZES[$documentType] ?? $fallback);
    }

    /**
     * Get active template for a document type (automatically resolving its default paper size).
     */
    public function getTemplateForDocument(Store $store, string $documentType, ?string $paperSizeOverride = null): ?VoucherTemplate
    {
        $this->ensureDefaultTemplates($store);

        // 1. Direct document_type match
        $query = VoucherTemplate::where('store_id', $store->id)
            ->where('document_type', $documentType)
            ->where('is_active', true);

        if ($paperSizeOverride) {
            $template = (clone $query)->where('paper_size', $paperSizeOverride)->first()
                ?? $query->first();
        } else {
            $template = $query->first();
        }

        // 2. Fallback to active template for document type's default paper size
        if (!$template) {
            $paperSize = $paperSizeOverride ?: $this->getDocumentPaperSize($store, $documentType);
            $template = $this->getActiveTemplate($store, $paperSize);
        }

        return $template ?? VoucherTemplate::where('store_id', $store->id)->first();
    }

    /**
     * Save document voucher default mappings.
     *
     * @param array<string, string> $defaults
     */
    public function saveDocumentDefaults(Store $store, array $defaults, ?User $user = null): void
    {
        $allowedSizes = ['58mm', '80mm', 'a4', 'a5'];
        $clean = [];
        foreach (\App\Models\StorefrontSetting::DEFAULT_DOCUMENT_VOUCHER_SIZES as $docType => $defSize) {
            $val = $defaults[$docType] ?? $defSize;
            $clean[$docType] = in_array($val, $allowedSizes, true) ? $val : $defSize;
        }

        $setting = $store->setting ?? \App\Models\StorefrontSetting::create([
            'store_id' => $store->id,
            'store_name' => $store->name,
        ]);

        $posSettings = $setting->pos_settings ?? [];
        $posSettings['document_voucher_defaults'] = $clean;
        $setting->pos_settings = $posSettings;
        $setting->save();

        AuditLog::write(
            storeId: $store->id,
            action: 'document_voucher_defaults_updated',
            entityType: 'storefront_setting',
            entityId: $setting->id,
            metadata: ['defaults' => $clean],
            actorId: $user?->id,
        );
    }

    /**
     * Save (create or update) a voucher template.
     */
    public function saveTemplate(Store $store, array $data, ?VoucherTemplate $template = null, ?User $user = null): VoucherTemplate
    {
        return DB::transaction(function () use ($store, $data, $template, $user) {
            $paperSize = $data['paper_size'] ?? '80mm';
            $isDefault = !empty($data['is_default']);

            // If store has no other template for this size, make default
            $otherCount = VoucherTemplate::where('store_id', $store->id)
                ->where('paper_size', $paperSize)
                ->when($template, fn($q) => $q->where('id', '!=', $template->id))
                ->count();

            if ($otherCount === 0) {
                $isDefault = true;
            }

            if ($isDefault) {
                VoucherTemplate::where('store_id', $store->id)
                    ->where('paper_size', $paperSize)
                    ->update(['is_default' => false]);

                if (!empty($data['document_type'])) {
                    VoucherTemplate::where('store_id', $store->id)
                        ->where('document_type', $data['document_type'])
                        ->update(['is_default' => false]);
                }
            }

            // Handle logo file upload
            $logoPath = $template?->logo_path;
            if (isset($data['logo_file']) && $data['logo_file'] instanceof UploadedFile) {
                $logoPath = $data['logo_file']->store('vouchers/logos', 'public');
            }

            // Handle QR code image upload
            $qrPath = $template?->qr_image_path;
            if (isset($data['qr_file']) && $data['qr_file'] instanceof UploadedFile) {
                $qrPath = $data['qr_file']->store('vouchers/qrs', 'public');
            }

            $attributes = [
                'name' => $data['name'],
                'document_type' => $data['document_type'] ?? $template?->document_type ?? null,
                'paper_size' => $paperSize,
                'style_preset' => $data['style_preset'] ?? 'clean_minimal',
                'header_title' => $data['header_title'] ?? $store->name,
                'header_subtitle' => $data['header_subtitle'] ?? null,
                'show_logo' => !empty($data['show_logo']),
                'logo_path' => $logoPath,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'show_qr' => !empty($data['show_qr']),
                'qr_type' => $data['qr_type'] ?? 'kpay',
                'qr_image_path' => $qrPath,
                'qr_label' => $data['qr_label'] ?? null,
                'show_customer_info' => !empty($data['show_customer_info']),
                'show_cashier_name' => !empty($data['show_cashier_name']),
                'show_tax_breakdown' => !empty($data['show_tax_breakdown']),
                'show_discount_line' => !empty($data['show_discount_line']),
                'show_barcode' => !empty($data['show_barcode']),
                'footer_greeting' => $data['footer_greeting'] ?? null,
                'footer_policy' => $data['footer_policy'] ?? null,
                'font_size' => $data['font_size'] ?? 'medium',
                'is_default' => $isDefault,
                'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            ];

            if ($template) {
                $template->update($attributes);
                $action = 'voucher_template_updated';
            } else {
                $template = VoucherTemplate::create(array_merge($attributes, ['store_id' => $store->id]));
                $action = 'voucher_template_created';
            }

            AuditLog::write(
                $store->id,
                $action,
                'voucher_templates',
                $template->id,
                [
                    'name' => $template->name,
                    'paper_size' => $template->paper_size,
                    'style_preset' => $template->style_preset,
                    'is_default' => $template->is_default,
                ],
                $user?->id
            );

            return $template;
        });
    }

    /**
     * Set template as default for its paper size.
     */
    public function setDefault(Store $store, VoucherTemplate $template, ?User $user = null): void
    {
        DB::transaction(function () use ($store, $template, $user) {
            VoucherTemplate::where('store_id', $store->id)
                ->where('paper_size', $template->paper_size)
                ->update(['is_default' => false]);

            $template->update(['is_default' => true, 'is_active' => true]);

            AuditLog::write(
                $store->id,
                'voucher_template_set_default',
                'voucher_templates',
                $template->id,
                ['name' => $template->name, 'paper_size' => $template->paper_size],
                $user?->id
            );
        });
    }

    /**
     * Delete a voucher template.
     */
    public function deleteTemplate(Store $store, VoucherTemplate $template, ?User $user = null): bool
    {
        return DB::transaction(function () use ($store, $template, $user) {
            $wasDefault = $template->is_default;
            $paperSize = $template->paper_size;

            if ($template->logo_path) {
                Storage::disk('public')->delete($template->logo_path);
            }
            if ($template->qr_image_path) {
                Storage::disk('public')->delete($template->qr_image_path);
            }

            $template->delete();

            if ($wasDefault) {
                $next = VoucherTemplate::where('store_id', $store->id)
                    ->where('paper_size', $paperSize)
                    ->where('is_active', true)
                    ->first();
                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }

            AuditLog::write(
                $store->id,
                'voucher_template_deleted',
                'voucher_templates',
                $template->id,
                ['name' => $template->name],
                $user?->id
            );

            return true;
        });
    }
}
