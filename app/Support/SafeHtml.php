<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;

/**
 * Project-approved allow-list HTML sanitizer for rich-text product descriptions.
 *
 * The project has no HTMLPurifier dependency, so rich text is sanitized with a
 * conservative DOM-based allow-list: only formatting tags survive, every other
 * element is unwrapped (its text is kept), attributes are stripped except a
 * tiny safe set, and only http(s)/mailto/tel/relative URLs are allowed.
 * Plain text (no markup at all) is escaped rather than trusted, so the output
 * of sanitize() is always safe to render with {!! !!}.
 *
 * Used by the storefront product page AND the admin product form preview so
 * both sides render descriptions identically.
 */
class SafeHtml
{
    /** Dangerous tags dropped entirely (content included) — never shown. */
    private const DROPPED = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'link', 'meta', 'base', 'svg', 'math', 'video',
        'audio', 'source', 'track', 'applet', 'template', 'noscript',
    ];

    /** Formatting tags that are kept as-is (everything else is unwrapped). */
    private const ALLOWED = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'blockquote', 'span', 'div',
        'hr', 'pre', 'code', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th',
        'td', 'figure', 'figcaption', 'img', 'sup', 'sub', 'small', 'mark',
        'del', 'ins',
    ];

    private const SAFE_ATTRS = [
        'href', 'title', 'alt', 'src', 'width', 'height', 'colspan', 'rowspan',
        'start', 'type', 'rel', 'target',
    ];

    private const SAFE_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // No markup at all — escape it, never trust raw input.
        if (! preg_match('/<[a-z][\s\S]*>/i', $html)) {
            return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        // The encoding prefix keeps UTF-8 (Burmese) multibyte content intact.
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);

        foreach (iterator_to_array($xpath->query('//*')) as $node) {
            $tag = strtolower($node->nodeName);

            if (! in_array($tag, self::ALLOWED, true)) {
                if (in_array($tag, self::DROPPED, true)) {
                    // Dangerous: remove the node and its content entirely.
                    $node->parentNode?->removeChild($node);
                } else {
                    // Unknown formatting tag: unwrap, keeping the text content.
                    $node->parentNode?->replaceChild($dom->createTextNode($node->textContent), $node);
                }
                continue;
            }

            foreach (iterator_to_array($node->attributes ?? []) as $attr) {
                $name = strtolower($attr->nodeName);

                if (! in_array($name, self::SAFE_ATTRS, true)) {
                    $node->removeAttribute($attr->nodeName);
                    continue;
                }

                if (($name === 'href' || $name === 'src') && trim((string) $attr->nodeValue) !== '') {
                    if (! self::isSafeUrl((string) $attr->nodeValue)) {
                        $node->removeAttribute($attr->nodeName);
                    }
                }

                if ($name === 'target' && $attr->nodeValue !== '_blank') {
                    $node->removeAttribute($attr->nodeName);
                }
            }
        }

        $out = $dom->saveHTML();
        // Drop the encoding hint prefix DOMDocument echoes back.
        $out = preg_replace('/^<\?xml encoding="utf-8" \?>/i', '', (string) $out);

        return trim((string) $out);
    }

    private static function isSafeUrl(string $url): bool
    {
        // Relative URLs (/foo, ./foo, ../foo) are fine.
        if (preg_match('#^(/|\.\.?/)#', $url)) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme !== '' && in_array($scheme, self::SAFE_SCHEMES, true);
    }
}
