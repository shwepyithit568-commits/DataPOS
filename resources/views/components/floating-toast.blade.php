@php
    $initialToasts = [];
    if (session('success')) {
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'success',
            'title' => 'Success',
            'message' => session('success'),
            'autoClose' => 6000,
        ];
    }
    if (session('error')) {
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'error',
            'title' => 'Error',
            'message' => session('error'),
            'autoClose' => 8000,
        ];
    }
    if (session('warning')) {
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'warning',
            'title' => 'Warning',
            'message' => session('warning'),
            'autoClose' => 7000,
        ];
    }
    if (session('info')) {
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'info',
            'title' => 'Info',
            'message' => session('info'),
            'autoClose' => 6000,
        ];
    }
    if ($errors->any()) {
        $errorList = $errors->all();
        $initialToasts[] = [
            'id' => 'toast_' . uniqid(),
            'type' => 'error',
            'title' => count($errorList) > 1 ? count($errorList) . ' Errors' : 'Error',
            'message' => count($errorList) === 1 ? $errorList[0] : null,
            'messages' => count($errorList) > 1 ? $errorList : null,
            'autoClose' => 9000,
        ];
    }
@endphp

<div x-data="{
    toasts: {{ Js::from($initialToasts) }},
    add(toast) {
        if (!toast || (!toast.message && !toast.messages)) return;
        const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
        const item = {
            id: id,
            type: toast.type || 'info',
            title: toast.title || (toast.type === 'success' ? 'Success' : (toast.type === 'error' ? 'Error' : 'Notification')),
            message: toast.message || null,
            messages: toast.messages || null,
            autoClose: typeof toast.autoClose === 'number' ? toast.autoClose : 6000,
            timer: null,
        };
        this.toasts.push(item);
        this.scheduleDismiss(item);
    },
    remove(id) {
        const idx = this.toasts.findIndex(t => t.id === id);
        if (idx !== -1) {
            if (this.toasts[idx].timer) clearTimeout(this.toasts[idx].timer);
            this.toasts.splice(idx, 1);
        }
    },
    scheduleDismiss(item) {
        if (!item.autoClose || item.autoClose <= 0) return;
        item.timer = setTimeout(() => {
            this.remove(item.id);
        }, item.autoClose);
    },
    pause(item) {
        if (item.timer) {
            clearTimeout(item.timer);
            item.timer = null;
        }
    },
    resume(item) {
        if (!item.timer && item.autoClose > 0) {
            item.timer = setTimeout(() => {
                this.remove(item.id);
            }, 3000);
        }
    },
    init() {
        this.toasts.forEach(t => this.scheduleDismiss(t));
    }
}"
@notify.window="add($event.detail)"
@toast.window="add($event.detail)"
class="fixed top-[calc(3.75rem+env(safe-area-inset-top))] right-2.5 sm:top-[calc(4rem+env(safe-area-inset-top))] sm:right-4 z-[110] flex flex-col gap-2 max-w-sm sm:max-w-md w-[calc(100vw-1.25rem)] pointer-events-none select-none"
aria-live="polite">

    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @mouseenter="pause(toast)"
             @mouseleave="resume(toast)"
             class="pointer-events-auto rounded-lg p-2.5 sm:p-3 shadow-xl backdrop-blur-md border transition flex items-start gap-2.5 relative group"
             :class="{
                 'bg-emerald-50/95 dark:bg-emerald-950/90 border-emerald-300/90 dark:border-emerald-700/80 text-emerald-950 dark:text-emerald-100 shadow-emerald-950/10 dark:shadow-emerald-950/40': toast.type === 'success',
                 'bg-rose-50/95 dark:bg-rose-950/90 border-rose-300/90 dark:border-rose-700/80 text-rose-950 dark:text-rose-100 shadow-rose-950/10 dark:shadow-rose-950/40': toast.type === 'error',
                 'bg-amber-50/95 dark:bg-amber-950/90 border-amber-300/90 dark:border-amber-700/80 text-amber-950 dark:text-amber-100 shadow-amber-950/10 dark:shadow-amber-950/40': toast.type === 'warning',
                 'bg-sky-50/95 dark:bg-sky-950/90 border-sky-300/90 dark:border-sky-700/80 text-sky-950 dark:text-sky-100 shadow-sky-950/10 dark:shadow-sky-950/40': toast.type === 'info'
             }">

            {{-- Left Type Icon --}}
            <div class="shrink-0 w-6 h-6 rounded-md grid place-items-center text-xs font-black"
                 :class="{
                     'bg-emerald-200/70 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200': toast.type === 'success',
                     'bg-rose-200/70 dark:bg-rose-900/60 text-rose-800 dark:text-rose-200': toast.type === 'error',
                     'bg-amber-200/70 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200': toast.type === 'warning',
                     'bg-sky-200/70 dark:bg-sky-900/60 text-sky-800 dark:text-sky-200': toast.type === 'info'
                 }">
                <template x-if="toast.type === 'success'">
                    <span>✓</span>
                </template>
                <template x-if="toast.type === 'error'">
                    <span>⚠️</span>
                </template>
                <template x-if="toast.type === 'warning'">
                    <span>⚡</span>
                </template>
                <template x-if="toast.type === 'info'">
                    <span>ℹ️</span>
                </template>
            </div>

            {{-- Content Area --}}
            <div class="flex-1 min-w-0 pr-4">
                <template x-if="toast.message">
                    <p class="text-xs font-bold leading-relaxed break-words" x-text="toast.message"></p>
                </template>
                <template x-if="toast.messages && toast.messages['length']">
                    <ul class="text-xs space-y-0.5 list-disc pl-3.5 font-medium leading-relaxed">
                        <template x-for="(msg, i) in toast.messages" :key="i">
                            <li x-text="msg"></li>
                        </template>
                    </ul>
                </template>
            </div>

            {{-- Close Button [x] --}}
            <button type="button"
                    @click="remove(toast.id)"
                    class="absolute top-2 right-2 w-5 h-5 rounded-md flex items-center justify-center text-xs font-black opacity-60 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition cursor-pointer active:scale-90"
                    title="Close"
                    aria-label="Close notification">
                ✕
            </button>
        </div>
    </template>
</div>
