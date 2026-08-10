@php
    use App\Support\SafeHtml;
    use App\Support\ProductSpecifications;
    $specRows = ProductSpecifications::rowsFor($product);
    $hasDescription = trim(strip_tags((string) $product->description)) !== '';
@endphp

<div class="min-w-0">
    <div class="mb-4 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="break-words text-base font-bold text-gray-900 dark:text-slate-100">{{ $product->name }}</h3>
            @if (!empty($product->sku))
                <p class="mt-0.5 font-mono text-xs text-gray-400 dark:text-slate-400">SKU: {{ $product->sku }}</p>
            @endif
        </div>
        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $product->isInStock() ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' }}">
            {{ $product->isInStock() ? __('messages.in_stock') : __('messages.out_of_stock') }}
        </span>
    </div>

    {{-- Description | Specifications tabs — plain-JS toggle (no Alpine) so the
         partial keeps working when injected into the modal via fetch. --}}
    <div role="tablist" aria-label="{{ __('messages.product_details') }}" class="flex items-center gap-1 border-b border-gray-200 dark:border-slate-700">
        <button type="button" role="tab" id="admin-spec-desc-tab" aria-controls="admin-spec-desc-panel" aria-selected="true"
            data-spec-tab="description"
            class="-mb-px inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-sky-500 px-4 py-2.5 text-xs font-black uppercase tracking-wide text-sky-600 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500/50 dark:text-sky-400">
            {{ __('messages.tab_description') }}
        </button>
        <button type="button" role="tab" id="admin-spec-specs-tab" aria-controls="admin-spec-specs-panel" aria-selected="false"
            data-spec-tab="specifications"
            class="-mb-px inline-flex items-center gap-1.5 rounded-t-lg border-b-2 border-transparent px-4 py-2.5 text-xs font-black uppercase tracking-wide text-gray-500 transition hover:text-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500/50 dark:text-slate-400 dark:hover:text-slate-200">
            {{ __('messages.tab_specifications') }}
        </button>
    </div>

    <div class="pt-4">
        <div role="tabpanel" id="admin-spec-desc-panel" aria-labelledby="admin-spec-desc-tab" data-spec-panel="description">
            @if ($hasDescription)
                <div class="prose prose-sm max-w-none text-sm leading-relaxed text-gray-800 dark:text-slate-100">
                    {!! SafeHtml::sanitize($product->description) !!}
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('messages.spec_description_empty') }}</p>
            @endif
        </div>
        <div role="tabpanel" id="admin-spec-specs-panel" aria-labelledby="admin-spec-specs-tab" data-spec-panel="specifications" hidden>
            @if ($specRows)
                <dl class="divide-y divide-gray-100 dark:divide-slate-800">
                    @foreach ($specRows as $spec)
                        <div class="grid grid-cols-1 gap-x-3 gap-y-0.5 py-2 sm:grid-cols-[minmax(0,9rem)_minmax(0,1fr)] sm:items-start">
                            <dt class="break-words text-xs font-bold text-gray-500 dark:text-slate-400">{{ $spec['label'] }}</dt>
                            <dd class="min-w-0 break-words text-xs font-medium text-gray-800 dark:text-slate-200">{{ $spec['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-gray-500 dark:text-slate-400">{{ __('messages.specs_empty') }}</p>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    var root = document.currentScript && document.currentScript.parentElement;
    if (!root) return;
    var tabs = root.querySelectorAll('[data-spec-tab]');
    var panels = root.querySelectorAll('[data-spec-panel]');
    function show(name) {
        tabs.forEach(function (tab) {
            var active = tab.getAttribute('data-spec-tab') === name;
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.classList.toggle('border-sky-500', active);
            tab.classList.toggle('text-sky-600', active);
            tab.classList.toggle('dark:text-sky-400', active);
            tab.classList.toggle('border-transparent', !active);
            tab.classList.toggle('text-gray-500', !active);
            tab.classList.toggle('dark:text-slate-400', !active);
        });
        panels.forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-spec-panel') !== name;
        });
    }
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () { show(tab.getAttribute('data-spec-tab')); });
        tab.addEventListener('keydown', function (e) {
            var names = ['description', 'specifications'];
            var i = names.indexOf(tab.getAttribute('data-spec-tab'));
            if (i === -1) return;
            var next = null;
            if (e.key === 'ArrowRight') next = names[(i + 1) % names.length];
            else if (e.key === 'ArrowLeft') next = names[(i - 1 + names.length) % names.length];
            else if (e.key === 'Home') next = names[0];
            else if (e.key === 'End') next = names[names.length - 1];
            if (!next) return;
            e.preventDefault();
            show(next);
            var target = root.querySelector('[data-spec-tab="' + next + '"]');
            if (target) target.focus();
        });
    });
})();
</script>
