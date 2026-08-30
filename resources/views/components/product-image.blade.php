@props([
    'path' => null,
    'alt' => 'Product Image',
    'class' => 'w-full h-40 object-cover',
    'aspect' => 'aspect-square',
    'rounded' => 'rounded-none'
])

<div class="relative overflow-hidden {{ $rounded }} bg-slate-100 dark:bg-slate-800 flex items-center justify-center {{ $aspect }}">
    @if (!empty($path))
        <img 
            src="{{ asset('storage/' . $path) }}" 
            alt="{{ $alt }}" 
            loading="lazy" 
            decoding="async" 
            class="{{ $class }} transition-all duration-500 hover:scale-105"
            data-img-fallback="hide-next"
        />
        <div class="hidden absolute inset-0 w-full h-full bg-gradient-to-br from-violet-50/80 to-slate-100/80 dark:from-slate-800/80 dark:to-slate-900/80 flex-col items-center justify-center p-2 text-center">
            <svg class="w-10 h-10 text-violet-400/60 dark:text-violet-400/40 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider">No Preview</span>
        </div>
    @else
        <div class="w-full h-full bg-gradient-to-br from-violet-50/80 to-slate-100/80 dark:from-slate-800/80 dark:to-slate-900/80 flex flex-col items-center justify-center p-2 text-center group-hover:scale-105 transition-transform duration-500">
            <div class="p-2.5 rounded-full bg-white/60 dark:bg-slate-700/60 backdrop-blur-md shadow-sm mb-1.5">
                <svg class="w-8 h-8 text-violet-500/70 dark:text-violet-400/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </div>
            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">DataPOS</span>
        </div>
    @endif
</div>
