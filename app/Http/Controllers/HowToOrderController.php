<?php

namespace App\Http\Controllers;

use App\Services\StoreContext;
use Illuminate\View\View;

class HowToOrderController extends Controller
{
    /**
     * Show the standalone "How to Order / Contact" page (/how-to-order).
     * A static guide page: ordering steps + shop contact info pulled from
     * the store's StorefrontSetting (address, phone, opening hours, Viber,
     * Telegram, delivery & payment details).
     */
    public function index(StoreContext $context): View
    {
        $store = $context->getStore();

        abort_unless($store, 404, 'Store not found.');

        // Pass the setting explicitly — Blade layout @php variables are not
        // visible inside @section content (same as the home route closure).
        $setting = $store->setting;

        return view('storefront.how_to_order.index', compact('store', 'setting'));
    }
}
