<?php

namespace App\Services;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class StorefrontMarkdownRenderer
{
    /**
     * Render markdown text into safe HTML for storefront display.
     *
     * @param string|null $markdown
     * @return HtmlString
     */
    public static function render(?string $markdown): HtmlString
    {
        if (empty($markdown)) {
            return new HtmlString('');
        }

        // Configure commonmark to strip raw HTML and reject unsafe links
        $html = Str::markdown($markdown, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level'  => 10,
        ]);

        // Extra sanitization layer against malicious vectors
        $sanitized = self::sanitize($html);

        return new HtmlString($sanitized);
    }

    /**
     * Sanitize output HTML string.
     */
    protected static function sanitize(string $html): string
    {
        // 1. Remove any script tags if somehow bypassed
        $html = preg_replace('#<script\b[^>]*>(.*?)</script>#is', '', $html);

        // 2. Remove iframe, object, embed, form, applet, base, link tags
        $html = preg_replace('#<(iframe|object|embed|form|applet|base|link)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(iframe|object|embed|form|applet|base|link)\b[^>]*>#is', '', $html);

        // 3. Remove inline event handlers (onclick, onerror, onload, onmouseover, etc.)
        $html = preg_replace('#\s+on[a-z]+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)#i', '', $html);

        // 4. Remove javascript:, data:, vbscript: links
        $html = preg_replace('#href\s*=\s*(["\'])\s*(javascript|data|vbscript):[^"\']*\1#i', 'href="#"', $html);
        $html = preg_replace('#src\s*=\s*(["\'])\s*(javascript|data|vbscript):[^"\']*\1#i', 'src=""', $html);

        return $html;
    }
}
