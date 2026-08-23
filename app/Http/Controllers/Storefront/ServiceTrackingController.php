<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\POS\Models\ServiceJob;
use App\Services\StoreContext;
use App\Support\ContactLinkBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public Customer Service Job Tracking Controller (SoT §16-B).
 *
 * Allows customers to track their device service/repair status in real time
 * without logging in, guarded by the unique 40-character `tracking_token`
 * or by looking up via Job # / Voucher # / Phone number.
 */
class ServiceTrackingController extends Controller
{
    /**
     * Show the service job lookup page or handle search query.
     */
    public function index(Request $request, StoreContext $context): View|RedirectResponse
    {
        $store = $context->getStore();
        $query = trim((string) $request->input('q', ''));
        $results = collect();
        $searched = false;

        if ($query !== '') {
            $searched = true;
            $cleanQuery = ltrim($query, '#');

            $jobsQuery = ServiceJob::with(['store', 'customer', 'statusHistory']);

            if ($store) {
                $jobsQuery->where('store_id', $store->id);
            }

            $jobs = $jobsQuery->where(function ($q) use ($cleanQuery, $query) {
                $q->where('job_number', 'like', "%{$cleanQuery}%")
                  ->orWhere('voucher_no', 'like', "%{$cleanQuery}%")
                  ->orWhere('imei_serial', 'like', "%{$cleanQuery}%")
                  ->orWhere('contact_phone', 'like', "%{$cleanQuery}%")
                  ->orWhere('tracking_token', $cleanQuery);
            })->latest('id')->limit(20)->get();

            // If exactly one match, redirect straight to the token tracking page
            if ($jobs->count() === 1) {
                $job = $jobs->first();
                $storeSlug = $job->store?->slug ?? ($store?->slug ?? 'default');
                return redirect()->route('storefront.service.track.token', [
                    'store_slug' => $storeSlug,
                    'token' => $job->tracking_token,
                ]);
            }

            $results = $jobs;
        }

        return view('storefront.service_tracking.index', [
            'store' => $store,
            'setting' => $store?->setting,
            'query' => $query,
            'searched' => $searched,
            'results' => $results,
        ]);
    }

    /**
     * Show the live status tracking view for a specific service job by token.
     * Login-free: authentication is bypassed using the unique tracking_token.
     */
    public function show(string $store_slug, string $token, StoreContext $context): View
    {
        $store = $context->getStore();

        if (! $store || $store->slug !== $store_slug) {
            abort(404, 'Store not found.');
        }

        $job = ServiceJob::where('store_id', $store->id)
            ->where('tracking_token', $token)
            ->with(['customer', 'technician', 'statusHistory.changer', 'payments.creator', 'items.product', 'store.setting'])
            ->firstOrFail();

        $setting = $store->setting;

        // Compose pre-filled messaging text for Viber / Telegram contact
        $deviceLabel = trim(($job->category ?? $job->device_type ?? 'Device') . ' ' . ($job->model ?? ''));
        $jobRef = $job->voucher_no ? "{$job->voucher_no} ({$job->job_number})" : $job->job_number;
        $statusLabel = __('messages.repair_status_' . $job->status);

        $contactMessage = "မင်္ဂလာပါ။\n"
            . "စက်ပြင်မှတ်တမ်း (#{$jobRef}) နှင့် ပတ်သက်၍ စုံစမ်းလိုပါသည် ခင်ဗျာ။\n"
            . "စက်ပစ္စည်း: {$deviceLabel}\n"
            . "လက်ရှိအခြေအနေ: {$statusLabel}\n"
            . "ပိုင်ရှင်: " . ($job->contact_name ?: ($job->customer?->name ?? '—')) . "\n"
            . "ဖုန်း: " . ($job->contact_phone ?: ($job->customer?->phone ?? '—'));

        $viberUrl = ContactLinkBuilder::viberChatUrl(
            $setting?->viber_number ?? $setting?->phone,
            $contactMessage
        );
        $viberIosUrl = ContactLinkBuilder::viberIosContactUrl(
            $setting?->viber_number ?? $setting?->phone,
            $contactMessage
        );
        $telegramUrl = ContactLinkBuilder::telegramUrl(
            $setting?->telegram_username,
            $contactMessage
        );

        return view('storefront.service_tracking.show', [
            'store' => $store,
            'setting' => $setting,
            'job' => $job,
            'viberUrl' => $viberUrl,
            'viberIosUrl' => $viberIosUrl,
            'telegramUrl' => $telegramUrl,
        ]);
    }
}
