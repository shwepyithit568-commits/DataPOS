{{-- ============================================================
     Master Data Hub — Seed Data Import Tab
     Multi-business-type: Tech / Fashion / General
     ============================================================ --}}

@php
    $groups = [
        'brands'          => ['label' => 'Brand များ',              'icon' => '🏷️', 'current_key' => 'brands',          'color' => 'sky'],
        'categories'      => ['label' => 'ကုန်ပစ္စည်း အမျိုးအစား',  'icon' => '📂', 'current_key' => 'categories',      'color' => 'violet'],
        'connectors'      => ['label' => 'သတ်မှတ်ချက် Presets (Specs)', 'icon' => '⚙️', 'current_key' => 'connectors',      'color' => 'emerald'],
        'colors'          => ['label' => 'Color Presets',             'icon' => '🎨', 'current_key' => 'colors',          'color' => 'pink'],
        'shelves'         => ['label' => 'Shelf Locations',           'icon' => '🗄️', 'current_key' => 'shelves',         'color' => 'amber'],
        'warranties'      => ['label' => 'Warranty Templates',        'icon' => '🛡️', 'current_key' => 'warranties',      'color' => 'blue'],
        'return_policies' => ['label' => 'Return Policy Templates',   'icon' => '🔄', 'current_key' => 'return_policies', 'color' => 'orange'],
        'variant_presets' => ['label' => 'Variant Preset Matrix',     'icon' => '⚡', 'current_key' => 'variant_presets', 'color' => 'indigo'],
    ];

    $colorMap = [
        'sky'    => ['bg' => 'bg-sky-50/60 dark:bg-slate-900',       'border' => 'border-sky-200/80 dark:border-slate-800',       'icon' => 'bg-sky-100 text-sky-600 dark:bg-slate-800 dark:text-sky-400 dark:border dark:border-sky-900/40',       'badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 dark:border dark:border-sky-800/50',       'new' => 'text-sky-600 dark:text-sky-400'],
        'violet' => ['bg' => 'bg-violet-50/60 dark:bg-slate-900', 'border' => 'border-violet-200/80 dark:border-slate-800', 'icon' => 'bg-violet-100 text-violet-600 dark:bg-slate-800 dark:text-violet-400 dark:border dark:border-violet-900/40','badge' => 'bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 dark:border dark:border-violet-800/50','new' => 'text-violet-600 dark:text-violet-400'],
        'emerald'=> ['bg' => 'bg-emerald-50/60 dark:bg-slate-900','border'=> 'border-emerald-200/80 dark:border-slate-800','icon' => 'bg-emerald-100 text-emerald-600 dark:bg-slate-800 dark:text-emerald-400 dark:border dark:border-emerald-900/40','badge'=>'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border dark:border-emerald-800/50','new'=>'text-emerald-600 dark:text-emerald-400'],
        'pink'   => ['bg' => 'bg-pink-50/60 dark:bg-slate-900',     'border' => 'border-pink-200/80 dark:border-slate-800',     'icon' => 'bg-pink-100 text-pink-600 dark:bg-slate-800 dark:text-pink-400 dark:border dark:border-pink-900/40',     'badge' => 'bg-pink-100 text-pink-700 dark:bg-pink-950/70 dark:text-pink-300 dark:border dark:border-pink-800/50',     'new' => 'text-pink-600 dark:text-pink-400'],
        'amber'  => ['bg' => 'bg-amber-50/60 dark:bg-slate-900',   'border' => 'border-amber-200/80 dark:border-slate-800',   'icon' => 'bg-amber-100 text-amber-600 dark:bg-slate-800 dark:text-amber-400 dark:border dark:border-amber-900/40',   'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 dark:border dark:border-amber-800/50',   'new' => 'text-amber-600 dark:text-amber-400'],
        'blue'   => ['bg' => 'bg-blue-50/60 dark:bg-slate-900',     'border' => 'border-blue-200/80 dark:border-slate-800',     'icon' => 'bg-blue-100 text-blue-600 dark:bg-slate-800 dark:text-blue-400 dark:border dark:border-blue-900/40',     'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 dark:border dark:border-blue-800/50',     'new' => 'text-blue-600 dark:text-blue-400'],
        'orange' => ['bg' => 'bg-orange-50/60 dark:bg-slate-900', 'border' => 'border-orange-200/80 dark:border-slate-800', 'icon' => 'bg-orange-100 text-orange-600 dark:bg-slate-800 dark:text-orange-400 dark:border dark:border-orange-900/40','badge'=>'bg-orange-100 text-orange-700 dark:bg-orange-950/70 dark:text-orange-300 dark:border dark:border-orange-800/50','new'=>'text-orange-600 dark:text-orange-400'],
        'indigo' => ['bg' => 'bg-indigo-50/60 dark:bg-slate-900', 'border' => 'border-indigo-200/80 dark:border-slate-800', 'icon' => 'bg-indigo-100 text-indigo-600 dark:bg-slate-800 dark:text-indigo-400 dark:border dark:border-indigo-900/40','badge'=>'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border dark:border-indigo-800/50','new'=>'text-indigo-600 dark:text-indigo-400'],
    ];

    $currentBiz = (string) ($activeBizType ?? 'tech');
@endphp

<div x-data="{
    confirmOpen: false,
    selectedGroups: ['brands', 'categories', 'connectors', 'colors', 'shelves', 'warranties', 'return_policies', 'variant_presets'],
    allChecked: true,
    allGroupKeys: ['brands', 'categories', 'connectors', 'colors', 'shelves', 'warranties', 'return_policies', 'variant_presets'],
    toggleAll() {
        this.allChecked = !this.allChecked;
        this.selectedGroups = this.allChecked ? ['brands', 'categories', 'connectors', 'colors', 'shelves', 'warranties', 'return_policies', 'variant_presets'] : [];
    },
    toggleGroup(key) {
        var idx = this.selectedGroups.indexOf(key);
        if (idx !== -1) {
            this.selectedGroups.splice(idx, 1);
        } else {
            this.selectedGroups.push(key);
        }
        this.allChecked = this.selectedGroups.length === 8;
    },
    isSelected(key) {
        return this.selectedGroups.indexOf(key) !== -1;
    },
    openConfirm() {
        if (this.selectedGroups.length === 0) return;
        this.confirmOpen = true;
    }
}" class="space-y-2">

    {{-- Flash --}}
    @if (session('success'))
        <div class="flex items-start gap-2 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs rounded-lg px-3 py-2">
            <span class="shrink-0 mt-0.5">✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Banner --}}
    <div class="bg-gradient-to-r from-violet-50 via-indigo-50 to-sky-50 dark:from-violet-950/30 dark:via-indigo-950/20 dark:to-sky-950/30 border border-violet-200/70 dark:border-violet-800/60 rounded-lg p-3 flex gap-3">
        <span class="text-2xl shrink-0">🌱</span>
        <div class="min-w-0 flex-1">
            <h2 class="text-xs font-black text-violet-900 dark:text-violet-200 mb-0.5">Seed Data Import — လုပ်ငန်းအမျိုးအစားအလိုက် Ready-Made Data</h2>
            <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                Brand များ၊ Category Tree၊ Preset များ၊ Shelf Location များ၊ Warranty Template များ၊ Return Policy များနှင့် Variant Preset Matrix တို့ကို
                <strong class="text-violet-700 dark:text-violet-300">တစ်ချက်နှိပ်၍</strong> ထည့်သွင်းနိုင်သည်။
                Data များသည် <strong>idempotent</strong> — ထပ်ကာ run နိုင်သည် (updateOrCreate safe)။
            </p>
        </div>
    </div>

    {{-- ════ Business Type Selector (Relative URLs for instant switching) ════ --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg p-2.5">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[11px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide shrink-0">လုပ်ငန်းအမျိုးအစား :</span>
            <div class="flex items-center gap-1.5 flex-wrap">
                @foreach ($businessTypes as $bizKey => $bizMeta)
                @php
                    $isActive = ($currentBiz === $bizKey);
                    $pillActive = match($bizKey) {
                        'fashion' => 'bg-pink-600 text-white border-transparent shadow-sm',
                        'general' => 'bg-slate-700 text-white border-transparent shadow-sm',
                        default   => 'bg-sky-600 text-white border-transparent shadow-sm',
                    };
                    $pillInactive = match($bizKey) {
                        'fashion' => 'text-pink-600 dark:text-pink-400 border-pink-200 dark:border-pink-800 hover:bg-pink-50 dark:hover:bg-pink-950/30',
                        'general' => 'text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800',
                        default   => 'text-sky-600 dark:text-sky-400 border-sky-200 dark:border-sky-800 hover:bg-sky-50 dark:hover:bg-sky-950/30',
                    };
                @endphp
                <a href="?tab=seed-data&biz={{ $bizKey }}"
                   class="inline-flex items-center gap-1.5 h-7 px-3 rounded-full text-[11px] font-black transition border {{ $isActive ? $pillActive : $pillInactive }}">
                    <span>{{ $bizMeta['icon'] }}</span>
                    <span>{{ $bizMeta['label'] }}</span>
                    <span class="inline-flex items-center justify-center h-4 px-1.5 rounded-full text-[9px] font-mono {{ $isActive ? 'bg-white/25' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400' }}">
                        {{ $bizMeta['count'] }}
                    </span>
                </a>
                @endforeach
            </div>
        </div>
        @if (isset($businessTypes[$currentBiz]))
        @php $activeMeta = $businessTypes[$currentBiz]; @endphp
        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
            <span class="text-lg">{{ $activeMeta['icon'] }}</span>
            <div>
                <span class="text-[11px] font-black text-slate-800 dark:text-slate-200">{{ $activeMeta['label'] }}</span>
                <span class="text-[10px] text-slate-500 dark:text-slate-400 ml-1.5">— {{ $activeMeta['description'] ?? '' }}</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Group Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-1.5">
        @foreach ($groups as $key => $group)
        @php
            $c = $colorMap[$group['color']];
            $preview = $seedPreview[$key] ?? ['count' => 0, 'label' => $group['label']];
            $current = $seedCurrentCounts[$group['current_key']] ?? 0;
        @endphp
        <label for="grp-{{ $key }}" class="flex items-start gap-2.5 p-2.5 rounded-lg border {{ $c['border'] }} {{ $c['bg'] }} cursor-pointer hover:shadow-xs transition group relative"
               :class="isSelected('{{ $key }}') ? 'ring-2 ring-violet-500 dark:ring-violet-400 shadow-xs border-violet-400 dark:border-violet-500' : 'opacity-70 dark:opacity-60'">
            <input type="checkbox" id="grp-{{ $key }}" name="dummy_{{ $key }}"
                   :checked="isSelected('{{ $key }}')"
                   @change="toggleGroup('{{ $key }}')"
                   class="sr-only">
            <span class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full border-2 transition"
                  :class="isSelected('{{ $key }}') ? 'bg-violet-600 border-violet-600' : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600'">
                <svg x-show="isSelected('{{ $key }}')" class="w-full h-full p-0.5 text-white" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5"/>
                </svg>
            </span>
            <div class="shrink-0 w-8 h-8 rounded-md grid place-items-center text-sm {{ $c['icon'] }} shadow-inner">{{ $group['icon'] }}</div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-black text-slate-900 dark:text-slate-100 leading-tight mb-0.5 pr-5">{{ $group['label'] }}</div>
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded {{ $c['badge'] }}">
                        {{ $preview['count'] }} items
                    </span>
                    @if ($current > 0)
                        <span class="text-[10px] text-slate-500 dark:text-slate-400">(လက်ရှိ: {{ $current }})</span>
                    @else
                        <span class="text-[10px] font-bold {{ $c['new'] }}">✦ Empty</span>
                    @endif
                </div>
            </div>
        </label>
        @endforeach
    </div>

    {{-- Action Bar --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg px-3 py-2 flex flex-wrap items-center justify-between gap-2">
        <label class="flex items-center gap-2 cursor-pointer select-none">
            <button type="button" @click="toggleAll()"
                    class="w-4 h-4 rounded border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition"
                    :class="allChecked ? 'bg-violet-600 border-violet-600' : 'bg-white dark:bg-slate-800'">
                <svg x-show="allChecked" class="w-3 h-3 text-white" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5"/>
                </svg>
            </button>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                အားလုံး ရွေးချယ်ပါ
                <span class="font-mono text-slate-400 dark:text-slate-500 ml-1" x-text="'(' + selectedGroups.length + '/8)'"></span>
            </span>
        </label>
        <div class="flex items-center gap-1.5">
            @php $ab = $businessTypes[$currentBiz] ?? null; @endphp
            @if ($ab)
            <span class="hidden sm:inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                {{ $ab['icon'] }} {{ $ab['label'] }}
            </span>
            @endif
            <span class="text-[11px] text-slate-500 dark:text-slate-400 hidden sm:inline">ⓘ Safe: updateOrCreate</span>
            <button type="button" @click="openConfirm()"
                    :disabled="selectedGroups.length === 0"
                    :class="selectedGroups.length === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-emerald-500 active:scale-95 cursor-pointer'"
                    class="h-8 px-4 rounded-md text-xs font-black bg-emerald-600 text-white shadow-sm transition flex items-center gap-1.5">
                🌱 <span>Seed Data Import</span>
                <span class="inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1.5 rounded bg-white/20 font-mono text-[11px]" x-text="selectedGroups.length"></span>
            </button>
        </div>
    </div>

    {{-- Data Preview --}}
    <div class="space-y-1.5">

        {{-- Brands --}}
        <details class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg overflow-hidden" :class="isSelected('brands') ? '' : 'opacity-50'">
            <summary class="flex items-center gap-2 px-3 py-2 text-xs font-black text-slate-800 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/60 select-none list-none">
                <span>🏷️ Brands Preview</span>
                <span class="ml-auto inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-sky-100 text-sky-700 dark:bg-sky-950/70 dark:text-sky-300 dark:border dark:border-sky-800/50">{{ $seedPreview['brands']['count'] }} items</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="border-t border-slate-100 dark:border-slate-800 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="text-left px-3 py-1.5 font-bold text-slate-500 uppercase text-[10px]">#</th>
                            <th class="text-left px-3 py-1.5 font-bold text-slate-500 uppercase text-[10px]">Brand Name</th>
                            <th class="text-left px-3 py-1.5 font-bold text-slate-500 uppercase text-[10px]">Code</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach (($seedPreview['brands']['sample'] ?? []) as $i => $b)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                            <td class="px-3 py-1 text-slate-400 tabular-nums">{{ $i + 1 }}</td>
                            <td class="px-3 py-1 font-semibold text-slate-800 dark:text-slate-200">{{ $b['name'] }}</td>
                            <td class="px-3 py-1"><span class="font-mono text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-1.5 py-0.5 rounded">{{ $b['code'] }}</span></td>
                        </tr>
                        @endforeach
                        @if ($seedPreview['brands']['count'] > count($seedPreview['brands']['sample'] ?? []))
                        <tr class="bg-slate-50 dark:bg-slate-800/30">
                            <td colspan="3" class="px-3 py-1 text-[10px] text-slate-400 italic">
                                … နှင့် {{ $seedPreview['brands']['count'] - count($seedPreview['brands']['sample'] ?? []) }} brands ထပ်ရှိသည် (လက်ရှိ: {{ $seedCurrentCounts['brands'] }})
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </details>

        {{-- Categories --}}
        <details class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg overflow-hidden" :class="isSelected('categories') ? '' : 'opacity-50'">
            <summary class="flex items-center gap-2 px-3 py-2 text-xs font-black text-slate-800 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/60 select-none list-none">
                <span>📂 Categories Preview</span>
                <span class="ml-auto inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 dark:border dark:border-violet-800/50">{{ $seedPreview['categories']['count'] }} parent groups</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="border-t border-slate-100 dark:border-slate-800 px-3 py-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1">
                    @foreach (($seedPreview['categories']['sample'] ?? []) as $cat)
                    <div class="bg-slate-50 dark:bg-slate-800/40 rounded-md px-2 py-1.5">
                        <div class="text-[11px] font-black text-slate-800 dark:text-slate-200 mb-0.5">
                            {{ $cat['icon'] ?? '📁' }} {{ $cat['name'] }}
                        </div>
                        @if (!empty($cat['subs']))
                        <div class="text-[10px] text-slate-500 dark:text-slate-400 leading-relaxed">
                            {{ implode(', ', array_column(array_slice($cat['subs'], 0, 4), 'name')) }}{{ count($cat['subs']) > 4 ? ', +' . (count($cat['subs']) - 4) . ' more' : '' }}
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5 italic">လက်ရှိ: {{ $seedCurrentCounts['categories'] }} ခု</p>
            </div>
        </details>

        {{-- Mini stat cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-1.5">
            @foreach ([
                ['connectors',      '⚙️', 'Specs / Presets', 'emerald', 'connectors'],
                ['colors',          '🎨', 'Colors',     'pink',    'colors'],
                ['shelves',         '🗄️', 'Shelves',    'amber',   'shelves'],
                ['warranties',      '🛡️', 'Warranties', 'blue',    'warranties'],
                ['return_policies', '🔄', 'Returns',    'orange',  'return_policies'],
            ] as [$key, $icon, $label, $color, $ck])
            @php $c2 = $colorMap[$color]; @endphp
            <div class="p-2.5 rounded-lg border {{ $c2['border'] }} {{ $c2['bg'] }}" :class="isSelected('{{ $key }}') ? '' : 'opacity-40'">
                <div class="flex items-center gap-1.5 mb-1.5">
                    <span class="w-6 h-6 rounded grid place-items-center text-sm {{ $c2['icon'] }}">{{ $icon }}</span>
                    <span class="text-[11px] font-black text-slate-800 dark:text-slate-200">{{ $label }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-sm font-black text-slate-900 dark:text-slate-100 tabular-nums">{{ $seedPreview[$key]['count'] }}</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400">items</span>
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">လက်ရှိ: {{ $seedCurrentCounts[$ck] }} ခု</div>
            </div>
            @endforeach
        </div>

        {{-- Variant Presets --}}
        <details class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-lg overflow-hidden" :class="isSelected('variant_presets') ? '' : 'opacity-50'">
            <summary class="flex items-center gap-2 px-3 py-2 text-xs font-black text-slate-800 dark:text-slate-200 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/60 select-none list-none">
                <span>⚡ Variant Presets Preview</span>
                <span class="ml-auto inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">{{ $seedPreview['variant_presets']['count'] }} presets</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="border-t border-slate-100 dark:border-slate-800 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="text-left px-3 py-1.5 font-bold text-slate-500 uppercase text-[10px]">Preset Name</th>
                            <th class="text-left px-3 py-1.5 font-bold text-slate-500 uppercase text-[10px]">Family</th>
                            <th class="text-left px-3 py-1.5 font-bold text-slate-500 uppercase text-[10px]">Options (ဥပမာ)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach (($seedPreview['variant_presets']['sample'] ?? []) as $vp)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                            <td class="px-3 py-1.5 font-semibold text-slate-800 dark:text-slate-200">{{ $vp['name'] }}</td>
                            <td class="px-3 py-1.5"><span class="text-[10px] font-mono bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-300 px-1.5 py-0.5 rounded">{{ $vp['category_family'] ?? '-' }}</span></td>
                            <td class="px-3 py-1.5 text-slate-500 dark:text-slate-400 text-[10px]">
                                {{ implode(', ', array_column(array_slice($vp['options'] ?? [], 0, 5), 'name')) }}{{ count($vp['options'] ?? []) > 5 ? ' …' : '' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>

    </div>

    {{-- Confirm Modal --}}
    <div x-show="confirmOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="confirmOpen = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-5 space-y-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 grid place-items-center text-xl shrink-0">🌱</div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Seed Data Import အတည်ပြုပါ</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        ရွေးချယ်ထားသော <strong x-text="selectedGroups.length"></strong> groups ကို
                        <strong class="text-emerald-600 dark:text-emerald-400">{{ $store->name }}</strong> store တွင် ထည့်သွင်းမည်ဖြစ်သည်။
                    </p>
                    @if ($ab)
                    <div class="mt-1.5 inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full
                        {{ $currentBiz === 'fashion' ? 'bg-pink-100 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300' : ($currentBiz === 'general' ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300') }}">
                        {{ $ab['icon'] }} {{ $ab['label'] }} Data
                    </div>
                    @endif
                    <div class="text-[10px] text-slate-400 mt-0.5">စိတ်ကြိုက်ရွေးချယ်ထားသော Mode ဖြင့် သွင်းပါမည်</div>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-lg px-3 py-2 text-xs space-y-1">
                <template x-for="g in selectedGroups" :key="g">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 grid place-items-center">
                            <svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 10 10" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2 5l2 2 4-4"/></svg>
                        </span>
                        <span class="text-slate-700 dark:text-slate-300 capitalize" x-text="g.replace(/_/g, ' ')"></span>
                    </div>
                </template>
            </div>

            <form method="POST"
                  action="{{ route('store.admin.products.master-data.seed', ['store_slug' => $store->slug], false) }}"
                  id="seed-import-form"
                  class="space-y-3">
                @csrf
                <input type="hidden" name="business_type" value="{{ $currentBiz }}">
                <template x-for="g in selectedGroups" :key="g">
                    <input type="hidden" name="groups[]" :value="g">
                </template>

                {{-- Clean Mode Option --}}
                <div class="border-t border-slate-200 dark:border-slate-700 pt-2.5 space-y-1.5">
                    <span class="text-[11px] font-black text-slate-700 dark:text-slate-300 block">
                        ဒေတာသွင်းယူမည့် ပုံစံ ရွေးချယ်ပါ:
                    </span>
                    <div class="space-y-1 text-xs">
                        <label class="flex items-center gap-2 p-1.5 rounded-lg border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800">
                            <input type="radio" name="clean_mode" value="none" checked class="text-emerald-600 focus:ring-emerald-500">
                            <span class="text-slate-700 dark:text-slate-300">
                                <strong>ပုံမှန်သွင်းမည် (Update Only)</strong> — ရှိပြီးဒေတာ မဖျက်ပါ
                            </span>
                        </label>
                        <label class="flex items-center gap-2 p-1.5 rounded-lg border border-amber-200 dark:border-amber-900/60 bg-amber-50/50 dark:bg-amber-950/20 cursor-pointer hover:bg-amber-50 dark:hover:bg-amber-950/40">
                            <input type="radio" name="clean_mode" value="master_only" class="text-amber-600 focus:ring-amber-500">
                            <span class="text-amber-900 dark:text-amber-300">
                                <strong>Master Data အဟောင်းများသာ ရှင်းလင်းမည်</strong>
                            </span>
                        </label>
                        <label class="flex items-center gap-2 p-1.5 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/20 cursor-pointer hover:bg-rose-50 dark:hover:bg-rose-950/40">
                            <input type="radio" name="clean_mode" value="full" class="text-rose-600 focus:ring-rose-500">
                            <span class="text-rose-900 dark:text-rose-300">
                                <strong>ဒေတာဟောင်းအားလုံး အကုန်ရှင်းမည် (Full Reset)</strong> — Products ပါ ရှင်းလင်းမည်
                            </span>
                        </label>
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-1">
                    <button type="button" @click="confirmOpen = false"
                            class="h-8 px-4 rounded-md text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition cursor-pointer">
                        မလုပ်တော့ပါ
                    </button>
                    <button type="submit"
                            class="h-8 px-5 rounded-md text-xs font-black bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition active:scale-95 cursor-pointer flex items-center gap-1.5">
                        🌱 အတည်ပြုပြီး Import လုပ်မည်
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>