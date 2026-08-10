@props([
    'name' => 'content',
    'value' => '',
    'rows' => 220,
    'placeholder' => '',
])

{{-- Lightweight WYSIWYG editor — contenteditable + document.execCommand, no library.
     The hidden textarea (name={{ $name }}) is synced on input/blur. --}}
<div class="rounded-xl border border-gray-300 dark:border-slate-600 overflow-hidden bg-white dark:bg-slate-900 shadow-sm focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/30"
    x-data="{ sync() { $refs.editorInput.value = $refs.editorBody.innerHTML; $dispatch('richtext-sync', { name: '{{ $name }}' }) } }">
    <div class="flex flex-wrap items-center gap-0.5 px-1.5 py-1.5 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/60">
        <button type="button" @mousedown.prevent @click="$refs.editorBody.focus(); document.execCommand('bold')" title="Bold" class="w-8 h-8 rounded-lg text-sm font-black text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">B</button>
        <button type="button" @mousedown.prevent @click="$refs.editorBody.focus(); document.execCommand('italic')" title="Italic" class="w-8 h-8 rounded-lg text-sm italic font-black text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">I</button>
        <button type="button" @mousedown.prevent @click="$refs.editorBody.focus(); document.execCommand('underline')" title="Underline" class="w-8 h-8 rounded-lg text-sm underline font-black text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">U</button>
        <span class="mx-1 w-px h-5 bg-gray-200 dark:bg-slate-700"></span>
        <button type="button" @mousedown.prevent @click="$refs.editorBody.focus(); document.execCommand('formatBlock', false, 'H2')" title="Heading 2" class="w-8 h-8 rounded-lg text-[11px] font-black text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">H2</button>
        <button type="button" @mousedown.prevent @click="$refs.editorBody.focus(); document.execCommand('formatBlock', false, 'H3')" title="Heading 3" class="w-8 h-8 rounded-lg text-[11px] font-black text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">H3</button>
        <span class="mx-1 w-px h-5 bg-gray-200 dark:bg-slate-700"></span>
        <button type="button" @mousedown.prevent @click="$refs.editorBody.focus(); document.execCommand('insertUnorderedList')" title="Bullet list" class="w-8 h-8 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">•≡</button>
        <button type="button" @mousedown.prevent @click="$refs.editorBody.focus(); document.execCommand('insertOrderedList')" title="Numbered list" class="w-8 h-8 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">1≡</button>
        <span class="mx-1 w-px h-5 bg-gray-200 dark:bg-slate-700"></span>
        <button type="button" @mousedown.prevent @click="let u = prompt('Link URL:'); if (u) { $refs.editorBody.focus(); document.execCommand('createLink', false, u) }" title="Insert link" class="w-8 h-8 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">🔗</button>
        <button type="button" @mousedown.prevent @click="$refs.editorBody.focus(); document.execCommand('removeFormat'); document.execCommand('formatBlock', false, 'p')" title="Clear formatting" class="w-8 h-8 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-violet-100 dark:hover:bg-slate-700 transition">⌫</button>
    </div>
    <div x-ref="editorBody" contenteditable="true" @input="sync()" @blur="sync()"
        class="p-4 text-sm text-gray-800 dark:text-slate-100 prose prose-sm max-w-none leading-relaxed focus:outline-none"
        style="min-height: {{ $rows }}px"
        data-placeholder="{{ $placeholder }}"
        x-init="$nextTick(() => { $refs.editorBody.innerHTML = $refs.editorInput.value })">{!! $value !!}</div>
    <textarea name="{{ $name }}" x-ref="editorInput" class="hidden">{{ $value }}</textarea>
</div>
