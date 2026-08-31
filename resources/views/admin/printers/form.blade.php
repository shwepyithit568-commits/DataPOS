@extends('layouts.admin.app')

@section('title', ($printer->exists ? __('messages.edit') : __('messages.printers_add_new')) . ' - ' . ($store->name ?? 'DataPOS'))

@section('content')
<script nonce="{{ $cspNonce }}">
window.printerFormData = function () {
    return {
        connectionType: '{{ old('connection_type', $printer->connection_type ?? 'browser') }}',
        paperWidth: '{{ old('paper_width', $printer->paper_width ?? '80mm') }}',
        isNetwork: function () {
            return this.connectionType === 'network';
        },
        isUsbOrBt: function () {
            return this.connectionType === 'usb' || this.connectionType === 'bluetooth';
        }
    };
};
</script>

<div x-data="window.printerFormData()" class="w-full max-w-4xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-4">
        <div>
            <a href="{{ route('store.admin.printers.index', ['store_slug' => $store->slug]) }}"
               class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1 mb-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>{{ __('messages.back_to_printers') }}</span>
            </a>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-slate-100 font-outfit">
                {{ $printer->exists ? __('messages.edit') . ': ' . $printer->name : __('messages.printers_add_new') }}
            </h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-sm text-rose-800 dark:text-rose-200">
            <div class="font-bold mb-1">{{ __('messages.fix_errors_prompt') ?? 'ဖြည့်သွင်းချက်များ မှားယွင်းနေပါသည်:' }}</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $printer->exists ? route('store.admin.printers.update', ['store_slug' => $store->slug, 'printer' => $printer->id]) : route('store.admin.printers.store', ['store_slug' => $store->slug]) }}"
          class="space-y-6">
        @csrf
        @if($printer->exists)
            @method('PUT')
        @endif

        {{-- Main Settings Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-7 shadow-sm space-y-5">

            <h2 class="text-sm font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 font-mono">
                {{ __('messages.printers_section_hardware') }}
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Printer Name --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_name') }} *
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', $printer->name) }}"
                           required
                           placeholder="e.g. Main Counter 80mm ESC/POS"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Connection Type --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_connection_type') }} *
                    </label>
                    <select name="connection_type"
                            x-model="connectionType"
                            required
                            class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                        <option value="browser">{{ __('messages.printers_conn_browser') }}</option>
                        <option value="network">{{ __('messages.printers_conn_network') }}</option>
                        <option value="usb">{{ __('messages.printers_conn_usb') }}</option>
                        <option value="bluetooth">{{ __('messages.printers_conn_bluetooth') }}</option>
                    </select>
                </div>

                {{-- Paper Width --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_paper_width') }} *
                    </label>
                    <select name="paper_width"
                            x-model="paperWidth"
                            required
                            class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                        <option value="80mm">{{ __('messages.printers_paper_80mm') }}</option>
                        <option value="58mm">{{ __('messages.printers_paper_58mm') }}</option>
                    </select>
                </div>

                {{-- Printer Role --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_role') }} *
                    </label>
                    <select name="printer_role"
                            required
                            class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-semibold focus:ring-2 focus:ring-violet-500 shadow-sm">
                        <option value="receipt" {{ old('printer_role', $printer->printer_role) === 'receipt' ? 'selected' : '' }}>{{ __('messages.printers_role_receipt') }}</option>
                        <option value="kitchen" {{ old('printer_role', $printer->printer_role) === 'kitchen' ? 'selected' : '' }}>{{ __('messages.printers_role_kitchen') }}</option>
                        <option value="service" {{ old('printer_role', $printer->printer_role) === 'service' ? 'selected' : '' }}>{{ __('messages.printers_role_service') }}</option>
                        <option value="label" {{ old('printer_role', $printer->printer_role) === 'label' ? 'selected' : '' }}>{{ __('messages.printers_role_label') }}</option>
                    </select>
                </div>

                {{-- LAN Network IP & Port (Conditional) --}}
                <div x-show="isNetwork()" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/60 rounded-2xl">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            {{ __('messages.printers_ip_address') }} *
                        </label>
                        <input type="text"
                               name="ip_address"
                               value="{{ old('ip_address', $printer->ip_address) }}"
                               placeholder="192.168.1.200"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm font-mono bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-blue-500 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            {{ __('messages.printers_port') }}
                        </label>
                        <input type="number"
                               name="port"
                               value="{{ old('port', $printer->port ?? 9100) }}"
                               placeholder="9100"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm font-mono bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-blue-500 shadow-sm">
                    </div>
                </div>

                {{-- USB / Bluetooth Path (Conditional) --}}
                <div x-show="isUsbOrBt()" class="sm:col-span-2 p-4 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/60 rounded-2xl">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_device_path') }}
                    </label>
                    <input type="text"
                           name="device_path"
                           value="{{ old('device_path', $printer->device_path) }}"
                           placeholder="COM3 or /dev/usb/lp0 or Bluetooth MAC Address"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm font-mono bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-bold focus:ring-2 focus:ring-indigo-500 shadow-sm">
                </div>

            </div>
        </div>

        {{-- Automation Controls Card --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-3xl p-5 sm:p-7 shadow-sm space-y-5">

            <h2 class="text-sm font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 font-mono">
                {{ __('messages.printers_section_automation') }}
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- Auto Paper Cutter --}}
                <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <input type="checkbox"
                           name="auto_cut"
                           value="1"
                           {{ old('auto_cut', $printer->auto_cut) ? 'checked' : '' }}
                           class="w-4 h-4 mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.printers_auto_cut') }}</span>
                        <span class="block text-[11px] text-slate-500">{{ __('messages.printers_auto_cut_hint') }}</span>
                    </div>
                </label>

                {{-- Cash Drawer Kick --}}
                <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <input type="checkbox"
                           name="cash_drawer_kick"
                           value="1"
                           {{ old('cash_drawer_kick', $printer->cash_drawer_kick) ? 'checked' : '' }}
                           class="w-4 h-4 mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.printers_cash_drawer_kick') }}</span>
                        <span class="block text-[11px] text-slate-500">{{ __('messages.printers_cash_drawer_hint') }}</span>
                    </div>
                </label>

                {{-- Print Logo --}}
                <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <input type="checkbox"
                           name="print_logo"
                           value="1"
                           {{ old('print_logo', $printer->print_logo) ? 'checked' : '' }}
                           class="w-4 h-4 mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.printers_print_logo') }}</span>
                        <span class="block text-[11px] text-slate-500">{{ __('messages.printers_print_logo_hint') }}</span>
                    </div>
                </label>

                {{-- Beep on Print --}}
                <label class="flex items-start gap-3 p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <input type="checkbox"
                           name="beep_on_print"
                           value="1"
                           {{ old('beep_on_print', $printer->beep_on_print) ? 'checked' : '' }}
                           class="w-4 h-4 mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.printers_beep_on_print') }}</span>
                        <span class="block text-[11px] text-slate-500">{{ __('messages.printers_beep_on_print_hint') }}</span>
                    </div>
                </label>

                {{-- Print Copies --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_print_copies') }}
                    </label>
                    <input type="number"
                           name="print_copies"
                           min="1"
                           max="5"
                           value="{{ old('print_copies', $printer->print_copies ?? 1) }}"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Feed Lines --}}
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_feed_lines') }}
                    </label>
                    <input type="number"
                           name="feed_lines"
                           min="0"
                           max="10"
                           value="{{ old('feed_lines', $printer->feed_lines ?? 2) }}"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 font-mono font-bold focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Header Text --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_header_text') }}
                    </label>
                    <input type="text"
                           name="header_text"
                           value="{{ old('header_text', $printer->header_text) }}"
                           placeholder="e.g. Welcome to DataPOS Electronics Store"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Footer Text --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        {{ __('messages.printers_footer_text') }}
                    </label>
                    <input type="text"
                           name="footer_text"
                           value="{{ old('footer_text', $printer->footer_text) }}"
                           placeholder="e.g. Thank you for shopping with us! Please keep receipt for warranty."
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500 shadow-sm">
                </div>

                {{-- Make Default --}}
                <div class="sm:col-span-2 flex items-center gap-6 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="is_default"
                               value="1"
                               {{ old('is_default', $printer->is_default) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.printers_is_default') }}</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $printer->is_active ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100">{{ __('messages.printers_is_active') }}</span>
                    </label>
                </div>

            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('store.admin.printers.index', ['store_slug' => $store->slug]) }}"
               class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                {{ __('messages.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold shadow-md transition">
                {{ $printer->exists ? __('messages.save') : __('messages.printers_add_new') }}
            </button>
        </div>

    </form>
</div>
@endsection
