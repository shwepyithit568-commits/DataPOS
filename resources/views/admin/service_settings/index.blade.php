@extends('layouts.admin.app')

@section('content')
<div class="space-y-6" x-data="{
    createOpen: false,
    editOpen: false,
    editItem: { id: null, type: '', name: '', code: '', description: '', sort_order: 0, is_active: true, parent_id: null },
    openEdit(item) {
        this.editItem = {
            id: item.id,
            type: item.type,
            name: item.name,
            code: item.code || '',
            description: item.description || '',
            sort_order: item.sort_order || 0,
            is_active: !!item.is_active,
            parent_id: item.parent_id || null
        };
        this.editOpen = true;
    }
}">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-violet-600/10 text-violet-600 dark:text-violet-400 grid place-items-center">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </span>
                {{ __('messages.sidebar_service_settings') }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ __('messages.service_settings_subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('store.admin.repairs.index', $storeRouteParams) }}"
               class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                ← {{ __('messages.sidebar_repair_center') }}
            </a>
            <button type="button" @click="createOpen = true"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-500/20 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                <span>{{ __('messages.add_new') }}</span>
            </button>
        </div>
    </div>

    {{-- Tabs Navigation Bar --}}
    @php
        /** @var string $tab */
        /** @var array<string, \Illuminate\Support\Collection> $grouped */
        $tabIcons = [
            'brand'     => '🏢',
            'category'  => '📁',
            'model'     => '📱',
            'color'     => '🎨',
            'storage'   => '💾',
            'defect'    => '⚡',
            'accessory' => '📦',
            'status'    => '🏷️',
        ];
        $tabLabels = [
            'brand'     => __('messages.repair_brands'),
            'category'  => __('messages.repair_categories'),
            'model'     => __('messages.repair_models'),
            'color'     => __('messages.repair_colors'),
            'storage'   => __('messages.repair_storage'),
            'defect'    => __('messages.repair_defects'),
            'accessory' => __('messages.repair_accessories_tab'),
            'status'    => __('messages.repair_statuses'),
        ];
    @endphp

    <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700/60 overflow-x-auto scrollbar-none text-xs font-bold">
        @foreach ($types as $key => $typeTitle)
            @php $isActive = $tab === $key; @endphp
            <a href="{{ route('store.admin.service_settings.index', [...$storeRouteParams, 'tab' => $key]) }}"
               class="px-3.5 py-2.5 rounded-xl transition-all whitespace-nowrap flex items-center gap-2 {{ $isActive ? 'bg-white dark:bg-slate-900 text-violet-600 dark:text-violet-400 shadow-sm border border-slate-200/60 dark:border-slate-800 font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                <span>{{ $tabIcons[$key] ?? '•' }}</span>
                <span>{{ $tabLabels[$key] ?? $typeTitle }}</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $isActive ? 'bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-mono' : 'bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400' }}">
                    {{ count($grouped[$key] ?? []) }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- Search & Filter strip --}}
    <div class="flex items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('store.admin.service_settings.index', $storeRouteParams) }}" class="flex-1 max-w-md relative">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none">🔍</span>
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="{{ __('messages.search') }} {{ $tabLabels[$tab] ?? '' }}..."
                   class="w-full pl-9 pr-8 py-2 text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none transition">
            @if ($search !== '')
                <a href="{{ route('store.admin.service_settings.index', [...$storeRouteParams, 'tab' => $tab]) }}"
                   class="absolute right-2.5 top-1/2 -translate-y-1/2 w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 text-xs grid place-items-center hover:bg-slate-300">✕</a>
            @endif
        </form>

        <div class="text-xs text-slate-500 dark:text-slate-400 font-semibold">
            {{ count($grouped[$tab] ?? []) }} {{ __('messages.items') ?? 'items' }}
        </div>
    </div>

    {{-- Items List Table --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3 px-4 w-16 text-center">#</th>
                        <th class="py-3 px-4">{{ __('messages.name') }}</th>
                        @if ($tab === 'model')
                            <th class="py-3 px-4">{{ __('messages.repair_brand') }}</th>
                        @else
                            <th class="py-3 px-4">{{ __('messages.code') ?? 'Code' }}</th>
                        @endif
                        <th class="py-3 px-4">{{ __('messages.description') }}</th>
                        <th class="py-3 px-4 text-center">{{ __('messages.status') }}</th>
                        <th class="py-3 px-4 text-right">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($grouped[$tab] ?? [] as $index => $item)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition group">
                            <td class="py-3 px-4 text-center text-slate-400 font-mono text-xs">
                                {{ $item->sort_order ?: ($index + 1) }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 dark:text-slate-100 block">{{ $item->name }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400 font-mono text-xs">
                                @if ($tab === 'model' && $item->parent)
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold">{{ $item->parent->name }}</span>
                                @else
                                    {{ $item->code ?: '—' }}
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400 text-xs truncate max-w-xs">
                                {{ $item->description ?: '—' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if ($item->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-900">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ __('messages.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-400 border border-slate-200 dark:border-slate-700">
                                        {{ __('messages.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="openEdit({{ $item->toJson() }})"
                                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-violet-50 hover:text-violet-600 dark:hover:bg-violet-950/60 dark:hover:text-violet-300 font-bold text-xs transition">
                                        ✏️ {{ __('messages.edit') }}
                                    </button>
                                    <form method="POST" action="{{ route('store.admin.service_settings.destroy', [...$storeRouteParams, 'service_setting' => $item->id]) }}"
                                          onsubmit="return confirm('{{ __('messages.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/60 dark:hover:text-rose-400 font-bold text-xs transition">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 grid place-items-center mx-auto mb-2 text-xl">
                                    {{ $tabIcons[$tab] ?? '📂' }}
                                </div>
                                <p class="text-sm font-bold text-slate-600 dark:text-slate-300">{{ __('messages.no_records_found') }}</p>
                                <p class="text-xs text-slate-400 mt-1">{{ __('messages.service_settings_add_prompt') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create Item Modal --}}
    <div x-show="createOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4"
         @keydown.escape.window="createOpen = false">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="createOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>{{ $tabIcons[$tab] ?? '•' }}</span>
                    <span>+ {{ __('messages.add_new') }} ({{ $tabLabels[$tab] ?? $types[$tab] }})</span>
                </h3>
                <button type="button" @click="createOpen = false" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold hover:bg-slate-200">✕</button>
            </div>

            <form method="POST" action="{{ route('store.admin.service_settings.store', $storeRouteParams) }}" class="space-y-3.5">
                @csrf
                <input type="hidden" name="type" value="{{ $tab }}">

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required maxlength="120"
                           placeholder="e.g. {{ $tab === 'brand' ? 'Apple, Samsung' : ($tab === 'color' ? 'Black, Gold' : ($tab === 'storage' ? '128 GB' : 'Name')) }}"
                           class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                </div>

                @if ($tab === 'model')
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.parent_brand') }}</label>
                        <select name="parent_id" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                            <option value="">-- {{ __('messages.select_parent_brand') }} --</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.code') ?? 'Code' }}</label>
                        <input type="text" name="code" maxlength="60"
                               placeholder="e.g. code or short identifier"
                               class="w-full px-3 py-2 text-sm font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.sort_order') }}</label>
                        <input type="number" name="sort_order" value="0" min="0" step="1"
                               class="w-full px-3 py-2 text-sm font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="inline-flex items-center gap-2 cursor-pointer pb-2">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.active') }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" rows="2" maxlength="500"
                              class="w-full px-3 py-2 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none"></textarea>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="createOpen = false" class="flex-1 py-2.5 rounded-xl font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-500/20 transition">+ {{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Item Modal --}}
    <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center p-4"
         @keydown.escape.window="editOpen = false">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="editOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>✏️</span>
                    <span>{{ __('messages.edit') }} ({{ $tabLabels[$tab] ?? $types[$tab] }})</span>
                </h3>
                <button type="button" @click="editOpen = false" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 font-bold hover:bg-slate-200">✕</button>
            </div>

            <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/service-settings') }}/' + editItem.id" class="space-y-3.5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.name') }} <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" x-model="editItem.name" required maxlength="120"
                           class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                </div>

                @if ($tab === 'model')
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.parent_brand') }}</label>
                        <select name="parent_id" x-model="editItem.parent_id" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                            <option value="">-- {{ __('messages.select_parent_brand') }} --</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.code') ?? 'Code' }}</label>
                        <input type="text" name="code" x-model="editItem.code" maxlength="60"
                               class="w-full px-3 py-2 text-sm font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.sort_order') }}</label>
                        <input type="number" name="sort_order" x-model="editItem.sort_order" min="0" step="1"
                               class="w-full px-3 py-2 text-sm font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none">
                    </div>
                    <div class="flex flex-col justify-end">
                        <label class="inline-flex items-center gap-2 cursor-pointer pb-2">
                            <input type="checkbox" name="is_active" value="1" :checked="editItem.is_active" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.active') }}</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1">{{ __('messages.description') }}</label>
                    <textarea name="description" x-model="editItem.description" rows="2" maxlength="500"
                              class="w-full px-3 py-2 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-violet-500 outline-none"></textarea>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="editOpen = false" class="flex-1 py-2.5 rounded-xl font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="flex-1 py-2.5 rounded-xl font-black text-xs bg-violet-600 hover:bg-violet-500 text-white shadow-lg shadow-violet-500/20 transition">{{ __('messages.update') }}</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
