@extends('layouts.admin.app')

@php
    $isEdit = $page->exists;
    $title = $isEdit ? __('messages.edit_custom_page') : __('messages.new_custom_page');
    $actionUrl = $isEdit
        ? route('admin.pages.update', ['store_slug' => $store->slug, 'id' => $page->id])
        : route('admin.pages.store', ['store_slug' => $store->slug]);
@endphp

@section('title', $title . ' - ' . ($store->setting?->store_name ?? $store->name))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full max-w-5xl mx-auto space-y-1 pb-10">
    {{-- Top Header Row --}}
    <div class="flex items-center justify-between bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl px-3 py-2 shadow-2xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.pages.index', ['store_slug' => $store->slug]) }}" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight">
                    {{ $title }}
                </h1>
                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    {{ __('messages.create_and_publish_storefront_pages') }}
                </p>
            </div>
        </div>

        @if ($isEdit && $page->isPublished())
            <a
                href="{{ url('/store/' . $store->slug . '/page/' . $page->slug) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-[11px] font-extrabold text-sky-600 dark:text-sky-400 hover:bg-slate-50 transition"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
                <span>{{ __('messages.view_live_page') }}</span>
            </a>
        @endif
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200">
            <div class="font-bold flex items-center gap-1.5 mb-1">
                <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                <span>{{ __('messages.please_fix_errors_below') }}</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Form --}}
    <form
        method="POST"
        action="{{ $actionUrl }}"
        enctype="multipart/form-data"
        class="space-y-1.5"
        x-data="{
            activeTab: 'my',
            editorTab: 'edit',
            slug: '{{ old('slug', $page->slug) }}',
            titleEn: '{{ old('title_en', $page->title_en) }}',
            titleMy: '{{ old('title_my', $page->title_my) }}',
            status: '{{ old('status', $page->status ?? 'draft') }}',
            slugify(text) {
                return text.toString().toLowerCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^\w\-]+/g, '')
                    .replace(/\-\-+/g, '-')
                    .replace(/^-+/, '')
                    .replace(/-+$/, '');
            },
            autoSlug() {
                if (!this.slug || this.slug.trim() === '') {
                    this.slug = this.slugify(this.titleEn || this.titleMy);
                }
            },
            insertMd(prefix, suffix = '', locale = 'my') {
                const textarea = document.getElementById('content_' + locale);
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const selected = textarea.value.substring(start, end);
                const replacement = prefix + (selected || 'text') + suffix;
                textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
                textarea.focus();
                textarea.setSelectionRange(start + prefix.length, start + prefix.length + (selected ? selected.length : 4));
            }
        }"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        {{-- 1. Language Tabs & Content Editor --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-3">
            {{-- Language Tab Switcher Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-0.5 text-xs font-extrabold">
                    <button
                        type="button"
                        @click="activeTab = 'my'"
                        class="rounded-md px-3 py-1 transition flex items-center gap-1.5"
                        :class="activeTab === 'my' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'"
                    >
                        <span>🇲🇲</span>
                        <span>{{ __('messages.language_myanmar') }}</span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'en'"
                        class="rounded-md px-3 py-1 transition flex items-center gap-1.5"
                        :class="activeTab === 'en' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'"
                    >
                        <span>🇬🇧</span>
                        <span>{{ __('messages.language_english') }}</span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'zh'"
                        class="rounded-md px-3 py-1 transition flex items-center gap-1.5"
                        :class="activeTab === 'zh' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'"
                    >
                        <span>🇨🇳</span>
                        <span>{{ __('messages.language_chinese') }}</span>
                    </button>
                </div>

                <div class="text-[11px] font-semibold text-slate-400">
                    <span x-show="activeTab === 'my'">🇲🇲 မြန်မာဘာသာ အချက်အလက်များ</span>
                    <span x-show="activeTab === 'en'">🇬🇧 English Content Fields</span>
                    <span x-show="activeTab === 'zh'">🇨🇳 中文内容字段</span>
                </div>
            </div>

            {{-- Tab 1: Myanmar --}}
            <div x-show="activeTab === 'my'" class="space-y-3">
                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.page_title') }} ({{ __('messages.language_myanmar') }}) <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="title_my"
                        x-model="titleMy"
                        @blur="autoSlug()"
                        value="{{ old('title_my', $page->title_my) }}"
                        required
                        placeholder="ဥပမာ- ကုမ္ပဏီအကြောင်း"
                        class="h-8 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.summary_excerpt') }} ({{ __('messages.language_myanmar') }})
                    </label>
                    <textarea
                        name="summary_my"
                        rows="2"
                        placeholder="အကျဉ်းချုပ် ဖော်ပြချက်..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-2 text-xs font-medium text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    >{{ old('summary_my', $page->summary_my) }}</textarea>
                </div>

                {{-- Markdown Toolbar + Content Area (Myanmar) --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-[11px] font-black text-slate-700 dark:text-slate-300">
                            {{ __('messages.page_content_markdown') }} ({{ __('messages.language_myanmar') }})
                        </label>
                        {{-- Markdown Quick Toolbar --}}
                        <div class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                            <button type="button" @click="insertMd('**', '**', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Bold">B</button>
                            <button type="button" @click="insertMd('*', '*', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs italic font-serif" title="Italic">I</button>
                            <button type="button" @click="insertMd('# ', '', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 1">H1</button>
                            <button type="button" @click="insertMd('## ', '', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 2">H2</button>
                            <button type="button" @click="insertMd('### ', '', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 3">H3</button>
                            <button type="button" @click="insertMd('- ', '', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Bullet List">• List</button>
                            <button type="button" @click="insertMd('> ', '', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-serif" title="Quote">“ Quote</button>
                            <button type="button" @click="insertMd('`', '`', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-mono" title="Code">Code</button>
                            <button type="button" @click="insertMd('[Link Text](', ')', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Link">🔗</button>
                            <button type="button" @click="insertMd('\n| Col 1 | Col 2 |\n|---|---|\n| Val 1 | Val 2 |\n', '', 'my')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Table">📊</button>
                        </div>
                    </div>
                    <textarea
                        id="content_my"
                        name="content_my"
                        rows="12"
                        placeholder="# ခေါင်းစဉ်\n\nဤနေရာတွင် Markdown syntax ဖြင့် ရေးသားနိုင်ပါသည်..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-3 text-xs font-mono text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden leading-relaxed"
                    >{{ old('content_my', $page->content_my) }}</textarea>
                </div>
            </div>

            {{-- Tab 2: English --}}
            <div x-show="activeTab === 'en'" class="space-y-3" x-cloak>
                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.page_title') }} ({{ __('messages.language_english') }}) <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="title_en"
                        x-model="titleEn"
                        @blur="autoSlug()"
                        value="{{ old('title_en', $page->title_en) }}"
                        required
                        placeholder="e.g. About Our Store"
                        class="h-8 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.summary_excerpt') }} ({{ __('messages.language_english') }})
                    </label>
                    <textarea
                        name="summary_en"
                        rows="2"
                        placeholder="Short summary / excerpt..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-2 text-xs font-medium text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    >{{ old('summary_en', $page->summary_en) }}</textarea>
                </div>

                {{-- Markdown Toolbar + Content Area (English) --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-[11px] font-black text-slate-700 dark:text-slate-300">
                            {{ __('messages.page_content_markdown') }} ({{ __('messages.language_english') }})
                        </label>
                        <div class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                            <button type="button" @click="insertMd('**', '**', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Bold">B</button>
                            <button type="button" @click="insertMd('*', '*', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs italic font-serif" title="Italic">I</button>
                            <button type="button" @click="insertMd('# ', '', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 1">H1</button>
                            <button type="button" @click="insertMd('## ', '', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 2">H2</button>
                            <button type="button" @click="insertMd('### ', '', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 3">H3</button>
                            <button type="button" @click="insertMd('- ', '', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Bullet List">• List</button>
                            <button type="button" @click="insertMd('> ', '', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-serif" title="Quote">“ Quote</button>
                            <button type="button" @click="insertMd('`', '`', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-mono" title="Code">Code</button>
                            <button type="button" @click="insertMd('[Link Text](', ')', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Link">🔗</button>
                            <button type="button" @click="insertMd('\n| Col 1 | Col 2 |\n|---|---|\n| Val 1 | Val 2 |\n', '', 'en')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Table">📊</button>
                        </div>
                    </div>
                    <textarea
                        id="content_en"
                        name="content_en"
                        rows="12"
                        placeholder="# Heading\n\nWrite content here in Markdown format..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-3 text-xs font-mono text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden leading-relaxed"
                    >{{ old('content_en', $page->content_en) }}</textarea>
                </div>
            </div>

            {{-- Tab 3: Chinese --}}
            <div x-show="activeTab === 'zh'" class="space-y-3" x-cloak>
                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.page_title') }} ({{ __('messages.language_chinese') }})
                    </label>
                    <input
                        type="text"
                        name="title_zh_cn"
                        value="{{ old('title_zh_cn', $page->title_zh_cn) }}"
                        placeholder="例如- 关于我们"
                        class="h-8 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.summary_excerpt') }} ({{ __('messages.language_chinese') }})
                    </label>
                    <textarea
                        name="summary_zh_cn"
                        rows="2"
                        placeholder="摘要简述..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-2 text-xs font-medium text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    >{{ old('summary_zh_cn', $page->summary_zh_cn) }}</textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-[11px] font-black text-slate-700 dark:text-slate-300">
                            {{ __('messages.page_content_markdown') }} ({{ __('messages.language_chinese') }})
                        </label>
                        <div class="flex items-center gap-1 text-slate-600 dark:text-slate-400">
                            <button type="button" @click="insertMd('**', '**', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Bold">B</button>
                            <button type="button" @click="insertMd('*', '*', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs italic font-serif" title="Italic">I</button>
                            <button type="button" @click="insertMd('# ', '', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 1">H1</button>
                            <button type="button" @click="insertMd('## ', '', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 2">H2</button>
                            <button type="button" @click="insertMd('### ', '', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold" title="Heading 3">H3</button>
                            <button type="button" @click="insertMd('- ', '', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Bullet List">• List</button>
                            <button type="button" @click="insertMd('> ', '', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-serif" title="Quote">“ Quote</button>
                            <button type="button" @click="insertMd('`', '`', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-mono" title="Code">Code</button>
                            <button type="button" @click="insertMd('[Link Text](', ')', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Link">🔗</button>
                            <button type="button" @click="insertMd('\n| Col 1 | Col 2 |\n|---|---|\n| Val 1 | Val 2 |\n', '', 'zh')" class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-xs" title="Table">📊</button>
                        </div>
                    </div>
                    <textarea
                        id="content_zh"
                        name="content_zh_cn"
                        rows="12"
                        placeholder="# 标题\n\n在此使用 Markdown 格式撰写页面内容..."
                        class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-3 text-xs font-mono text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden leading-relaxed"
                    >{{ old('content_zh_cn', $page->content_zh_cn) }}</textarea>
                </div>
            </div>
        </div>

        {{-- 2. Slug & Featured Image Section --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
            {{-- Slug Configuration --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-2">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-1 flex items-center gap-1.5">
                    <span>🔗</span>
                    <span>{{ __('messages.url_slug_settings') }}</span>
                </h2>

                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.page_slug') }} <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center">
                        <span class="inline-flex h-8 items-center px-2.5 rounded-l-lg border border-r-0 border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-[11px] font-mono text-slate-500">
                            /page/
                        </span>
                        <input
                            type="text"
                            name="slug"
                            x-model="slug"
                            required
                            placeholder="about-us"
                            class="h-8 w-full rounded-r-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-mono font-bold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                        />
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">
                        {{ __('messages.slug_format_hint') }}
                    </p>
                </div>
            </div>

            {{-- Featured Image Upload --}}
            <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-2">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-1 flex items-center gap-1.5">
                    <span>🖼️</span>
                    <span>{{ __('messages.featured_image') }}</span>
                </h2>

                <div class="flex items-start gap-3">
                    @if ($page->featured_image_path)
                        <div class="shrink-0 relative">
                            <img
                                src="{{ asset('storage/' . $page->featured_image_path) }}"
                                alt="Featured"
                                class="h-14 w-20 rounded-lg object-cover border border-slate-200 dark:border-slate-700"
                            />
                        </div>
                    @endif

                    <div class="flex-1 space-y-1.5">
                        <input
                            type="file"
                            name="featured_image"
                            accept="image/jpeg,image/png,image/webp,image/jpg"
                            class="block w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-extrabold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 dark:file:bg-sky-950/60 dark:file:text-sky-300"
                        />

                        @if ($page->featured_image_path)
                            <label class="flex items-center gap-1.5 cursor-pointer text-[11px] font-bold text-rose-600">
                                <input type="checkbox" name="remove_featured_image" value="1" class="rounded text-rose-600 focus:ring-rose-500">
                                <span>{{ __('messages.remove_current_image') }}</span>
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. SEO Meta Tags & Publishing Settings --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-3">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-1.5 flex items-center gap-1.5">
                <span>🚀</span>
                <span>{{ __('messages.publishing_and_seo') }}</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {{-- Status --}}
                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.publication_status') }} <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="status"
                        x-model="status"
                        class="h-8 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    >
                        <option value="draft">{{ __('messages.draft') }}</option>
                        <option value="published">{{ __('messages.published') }}</option>
                    </select>
                </div>

                {{-- Published At --}}
                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.published_at') }}
                    </label>
                    <input
                        type="datetime-local"
                        name="published_at"
                        value="{{ old('published_at', $page->published_at ? $page->published_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                        class="h-8 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    />
                </div>

                {{-- Is Enabled --}}
                <div class="flex items-center pt-5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_enabled" value="0">
                        <input
                            type="checkbox"
                            name="is_enabled"
                            value="1"
                            @checked(old('is_enabled', $page->is_enabled ?? true))
                            class="rounded text-emerald-600 focus:ring-emerald-500"
                        />
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ __('messages.is_enabled') }}</span>
                    </label>
                </div>
            </div>

            {{-- Optional SEO Meta Accordion / Fields --}}
            <details class="border-t border-slate-100 dark:border-slate-800 pt-2 text-xs">
                <summary class="cursor-pointer font-bold text-sky-600 dark:text-sky-400 select-none">
                    + {{ __('messages.advanced_seo_meta_tags') }}
                </summary>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                            {{ __('messages.meta_title') }} (EN)
                        </label>
                        <input
                            type="text"
                            name="meta_title_en"
                            value="{{ old('meta_title_en', $page->meta_title_en) }}"
                            class="h-7 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2 text-xs font-medium text-slate-900 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-400 mb-1">
                            {{ __('messages.meta_description') }} (EN)
                        </label>
                        <input
                            type="text"
                            name="meta_description_en"
                            value="{{ old('meta_description_en', $page->meta_description_en) }}"
                            class="h-7 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2 text-xs font-medium text-slate-900 dark:text-white"
                        />
                    </div>
                </div>
            </details>
        </div>

        {{-- Form Actions Bar --}}
        <div class="flex items-center justify-end gap-2 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2.5 shadow-2xs">
            <a
                href="{{ route('admin.pages.index', ['store_slug' => $store->slug]) }}"
                class="inline-flex h-8 items-center px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition"
            >
                {{ __('messages.cancel') }}
            </a>
            <button
                type="submit"
                class="inline-flex h-8 items-center gap-1.5 px-4 rounded-lg bg-sky-600 text-xs font-black text-white hover:bg-sky-700 transition shadow-2xs"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span>{{ __('messages.save') }}</span>
            </button>
        </div>
    </form>
</div>
@endsection
