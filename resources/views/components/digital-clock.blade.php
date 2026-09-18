@props(['class' => ''])
@php
    $locale = app()->getLocale();
@endphp

<div x-data="{
        timeStr: '',
        ampm: '',
        dateStr: '',
        timer: null,
        init() {
            this.update();
            this.timer = setInterval(() => this.update(), 1000);
        },
        destroy() {
            if (this.timer) clearInterval(this.timer);
        },
        update() {
            const now = new Date();
            let h = now.getHours();
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            this.ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            this.timeStr = String(h).padStart(2, '0') + ':' + m + ':' + s;

            const locale = '{{ $locale }}';
            const days = {
                'my': ['တနင်္ဂနွေ', 'တနင်္လာ', 'အင်္ဂါ', 'ဗုဒ္ဓဟူး', 'ကြာသပတေး', 'သောကြာ', 'စနေ'],
                'zh_CN': ['周日', '周一', '周二', '周三', '周四', '周五', '周六'],
                'en': ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
            };
            const months = {
                'my': ['ဇန်', 'ဖေ', 'မတ်', 'ဧပြီ', 'မေ', 'ဇွန်', 'ဇူ', 'ဩ', 'စက်', 'အောက်', 'နို', 'ဒီ'],
                'zh_CN': ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
                'en': ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
            };
            const dList = days[locale] || days['en'];
            const mList = months[locale] || months['en'];
            const dayName = dList[now.getDay()];
            const monthName = mList[now.getMonth()];
            const dayNum = now.getDate();

            if (locale === 'my') {
                this.dateStr = dayNum + ' ' + monthName + ' (' + dayName + ')';
            } else if (locale === 'zh_CN') {
                this.dateStr = monthName + dayNum + '日 ' + dayName;
            } else {
                this.dateStr = dayNum + ' ' + monthName + ', ' + dayName;
            }
        }
    }"
    class="sf-clock-3d h-10 px-2.5 sm:px-3 rounded-xl select-none flex-shrink-0 inline-flex items-center gap-2 transition-all duration-150 {{ $class }}"
    role="timer"
    title="{{ __('messages.current_date_time') }}"
    aria-label="{{ __('messages.current_date_time') }}">

    {{-- Date Section (Desktop & Tablet: hidden below 640px) --}}
    <div class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-100 font-semibold whitespace-nowrap">
        <svg class="h-3.5 w-3.5 text-sky-500 dark:text-sky-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        <span x-text="dateStr" class="tabular-nums"></span>
    </div>

    {{-- Vertical divider (Desktop/Tablet) --}}
    <span class="hidden sm:block h-4 w-px bg-slate-300 dark:bg-slate-700 shrink-0"></span>

    {{-- Digital Clock Section (Always visible) --}}
    <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
        {{-- Live Glowing Pulse Dot --}}
        <span class="relative flex h-2 w-2 shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500 shadow-xs shadow-emerald-500/50"></span>
        </span>

        {{-- Clock icon --}}
        <svg class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>

        {{-- Time string with digital tabular font --}}
        <span class="font-mono font-bold text-xs sm:text-sm tracking-wider text-slate-900 dark:text-emerald-400 tabular-nums" x-text="timeStr"></span>

        {{-- AM/PM pill --}}
        <span class="text-[9px] sm:text-[10px] font-extrabold uppercase px-1 py-0.5 rounded bg-slate-200/80 dark:bg-slate-950 text-slate-700 dark:text-emerald-400 border border-slate-300/60 dark:border-slate-800 leading-tight" x-text="ampm"></span>
    </div>
</div>
