<?php

namespace App\Services;

use App\Models\Store;
use App\Models\StorefrontNavigationItem;
use Illuminate\Support\Facades\DB;

class StorefrontNavigationDefaultsService
{
    /**
     * Seed or reset default navigation items for a store based on its current capabilities.
     *
     * @param Store $store
     * @param bool $forceReset If true, removes existing items and recreates defaults.
     * @return void
     */
    public function seedDefaultsForStore(Store $store, bool $forceReset = false): void
    {
        DB::transaction(function () use ($store, $forceReset) {
            if ($forceReset) {
                $store->navigationItems()->delete();
            } elseif ($store->navigationItems()->exists()) {
                return; // Already initialized
            }

            $defaults = [
                [
                    'menu_key' => 'home',
                    'label_my' => 'ပင်မ',
                    'label_en' => 'Home',
                    'label_zh_cn' => '首页',
                    'icon_key' => 'home',
                    'destination_type' => 'system',
                    'destination_key' => 'home',
                    'show_desktop' => true,
                    'show_mobile_drawer' => true,
                    'show_mobile_bottom' => true,
                    'requires_auth' => false,
                    'required_capability' => null,
                    'is_enabled' => true,
                    'sort_order' => 10,
                ],
                [
                    'menu_key' => 'products',
                    'label_my' => 'ပစ္စည်းများ',
                    'label_en' => 'Products',
                    'label_zh_cn' => '商品',
                    'icon_key' => 'products',
                    'destination_type' => 'system',
                    'destination_key' => 'products',
                    'show_desktop' => true,
                    'show_mobile_drawer' => true,
                    'show_mobile_bottom' => true,
                    'requires_auth' => false,
                    'required_capability' => 'storefront.ecommerce',
                    'is_enabled' => true,
                    'sort_order' => 20,
                ],
                [
                    'menu_key' => 'categories',
                    'label_my' => 'အမျိုးအစား',
                    'label_en' => 'Categories',
                    'label_zh_cn' => '分类',
                    'icon_key' => 'categories',
                    'destination_type' => 'system',
                    'destination_key' => 'categories',
                    'show_desktop' => false,
                    'show_mobile_drawer' => true,
                    'show_mobile_bottom' => !$store->hasCapability('service.repair_jobs'),
                    'requires_auth' => false,
                    'required_capability' => 'storefront.ecommerce',
                    'is_enabled' => true,
                    'sort_order' => 30,
                ],
                [
                    'menu_key' => 'glass_finder',
                    'label_my' => 'မှန်ရှာရန်',
                    'label_en' => 'Glass Finder',
                    'label_zh_cn' => '钢化膜查询',
                    'icon_key' => 'glass',
                    'destination_type' => 'system',
                    'destination_key' => 'glass_finder',
                    'show_desktop' => true,
                    'show_mobile_drawer' => true,
                    'show_mobile_bottom' => false,
                    'requires_auth' => false,
                    'required_capability' => 'storefront.glass_finder',
                    'is_enabled' => true,
                    'sort_order' => 40,
                ],
                [
                    'menu_key' => 'service_tracking',
                    'label_my' => 'ဆာဗစ်စစ်',
                    'label_en' => 'Track Service',
                    'label_zh_cn' => '维修查询',
                    'icon_key' => 'repair',
                    'destination_type' => 'system',
                    'destination_key' => 'service_tracking',
                    'show_desktop' => true,
                    'show_mobile_drawer' => true,
                    'show_mobile_bottom' => $store->hasCapability('service.repair_jobs'),
                    'requires_auth' => false,
                    'required_capability' => 'service.repair_jobs',
                    'is_enabled' => true,
                    'sort_order' => 50,
                ],
                [
                    'menu_key' => 'how_to_order',
                    'label_my' => 'မှာယူနည်း',
                    'label_en' => 'How to Order',
                    'label_zh_cn' => '购买指南',
                    'icon_key' => 'book',
                    'destination_type' => 'system',
                    'destination_key' => 'how_to_order',
                    'show_desktop' => true,
                    'show_mobile_drawer' => true,
                    'show_mobile_bottom' => false,
                    'requires_auth' => false,
                    'required_capability' => 'storefront.online_ordering',
                    'is_enabled' => true,
                    'sort_order' => 60,
                ],
                [
                    'menu_key' => 'blog',
                    'label_my' => 'ဆောင်းပါး',
                    'label_en' => 'Blog',
                    'label_zh_cn' => '博客',
                    'icon_key' => 'blog',
                    'destination_type' => 'system',
                    'destination_key' => 'blog',
                    'show_desktop' => true,
                    'show_mobile_drawer' => true,
                    'show_mobile_bottom' => false,
                    'requires_auth' => false,
                    'required_capability' => 'storefront.blog',
                    'is_enabled' => true,
                    'sort_order' => 70,
                ],
                [
                    'menu_key' => 'cart',
                    'label_my' => 'ခြင်းတောင်း',
                    'label_en' => 'Cart',
                    'label_zh_cn' => '购物车',
                    'icon_key' => 'cart',
                    'destination_type' => 'system',
                    'destination_key' => 'cart',
                    'show_desktop' => false,
                    'show_mobile_drawer' => false,
                    'show_mobile_bottom' => true,
                    'requires_auth' => false,
                    'required_capability' => 'storefront.online_ordering',
                    'is_enabled' => true,
                    'sort_order' => 80,
                ],
                [
                    'menu_key' => 'account',
                    'label_my' => 'အကောင့်',
                    'label_en' => 'Account',
                    'label_zh_cn' => '账户',
                    'icon_key' => 'account',
                    'destination_type' => 'system',
                    'destination_key' => 'account',
                    'show_desktop' => false,
                    'show_mobile_drawer' => true,
                    'show_mobile_bottom' => true,
                    'requires_auth' => false, // Can be used for auth or guest (login)
                    'required_capability' => 'storefront.customer_portal',
                    'is_enabled' => true,
                    'sort_order' => 90,
                ],
            ];

            foreach ($defaults as $itemData) {
                StorefrontNavigationItem::create([
                    ...$itemData,
                    'store_id' => $store->id,
                ]);
            }
        });
    }
}
