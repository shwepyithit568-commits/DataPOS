{{-- Reusable Camera Barcode Scanner Modal for Purchase Orders (Create & Edit) --}}
{{-- Follows Admin UI/UX Standard v4.1 (Ultra-dense rhythm, Tri-lingual, Audio feedback) --}}

<div x-show="cameraModalOpen"
     x-cloak
     @keydown.escape.window="if (cameraModalOpen) closeCameraScanner()"
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="camera-modal-title"
     role="dialog"
     aria-modal="true">

    {{-- Backdrop --}}
    <div x-show="cameraModalOpen"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity"
         @click="closeCameraScanner()"></div>

    {{-- Dialog Box --}}
    <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
        <div x-show="cameraModalOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.outside="closeCameraScanner()"
             class="relative w-full max-w-md transform rounded-lg bg-white dark:bg-slate-900 text-left shadow-2xl transition-all border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden">

            {{-- Modal Header --}}
            <div class="p-2.5 sm:p-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2 shrink-0 bg-slate-50/70 dark:bg-slate-800/40">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="w-8 h-8 rounded-md bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-base font-black shrink-0 border border-sky-200/80 dark:border-sky-800">
                        📷
                    </div>
                    <div class="min-w-0">
                        <h2 id="camera-modal-title" class="text-xs sm:text-sm font-black text-slate-900 dark:text-slate-100 truncate">
                            {{ __('messages.po_camera_scanner_title') }}
                        </h2>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">
                            {{ __('messages.po_camera_scanner_desc') }}
                        </p>
                    </div>
                </div>

                <button type="button"
                        @click="closeCameraScanner()"
                        class="w-6 h-6 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm font-bold grid place-items-center cursor-pointer transition">
                    &times;
                </button>
            </div>

            {{-- Scanner Viewport Box --}}
            <div class="p-2 sm:p-3 space-y-2 bg-slate-950 flex flex-col items-center justify-center min-h-[260px] relative">
                {{-- Html5Qrcode Render Element --}}
                <div id="po-camera-scanner-viewport" class="w-full max-w-[340px] h-[220px] rounded overflow-hidden bg-black relative flex items-center justify-center border border-slate-700 shadow-inner">
                    <div x-show="!scannerActive && !scannerError" class="text-slate-400 text-xs flex flex-col items-center gap-1">
                        <svg class="w-6 h-6 animate-spin text-sky-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        <span>Starting camera...</span>
                    </div>
                </div>

                {{-- Live Scan Status / Toast Overlay --}}
                <div x-show="scannerNotice" x-cloak
                     x-transition:enter="ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute bottom-4 inset-x-4 mx-auto max-w-[320px] py-1.5 px-3 rounded-md bg-slate-900/90 text-white border border-sky-500/60 shadow-lg text-xs font-bold text-center truncate backdrop-blur-xs"
                     x-text="scannerNotice">
                </div>

                {{-- Error Message (Permission / Device) --}}
                <div x-show="scannerError" x-cloak class="w-full p-2 rounded bg-rose-950/80 border border-rose-800 text-rose-200 text-xs text-center space-y-1">
                    <p class="font-bold">⚠️ {{ __('messages.po_camera_permission_denied') }}</p>
                </div>
            </div>

            {{-- Modal Controls Toolbar --}}
            <div class="p-2 sm:p-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2 bg-slate-50/50 dark:bg-slate-800/30">
                {{-- Continuous Scan Toggle --}}
                <label class="flex items-center gap-1.5 cursor-pointer select-none">
                    <input type="checkbox" x-model="continuousScan" class="rounded border-slate-300 dark:border-slate-700 text-sky-600 focus:ring-sky-500 w-3.5 h-3.5">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.po_continuous_scan') }}</span>
                </label>

                <div class="flex items-center gap-1.5">
                    {{-- Snap/Upload Fallback --}}
                    <label class="h-6 px-2 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition flex items-center gap-1 cursor-pointer">
                        <span>📁 Upload Photo</span>
                        <input type="file" accept="image/*" capture="environment" class="sr-only" @change="scanUploadedBarcodeFile($event)">
                    </label>

                    {{-- Close Button --}}
                    <button type="button"
                            @click="closeCameraScanner()"
                            class="h-6 px-3 rounded text-[11px] font-bold bg-sky-600 hover:bg-sky-500 text-white transition cursor-pointer">
                        {{ __('messages.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
