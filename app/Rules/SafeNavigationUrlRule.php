<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeNavigationUrlRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('messages.invalid_navigation_url');
            return;
        }

        $raw = trim($value);

        if ($raw === '') {
            return;
        }

        // 1. Reject raw controls, null bytes and CRLF
        if (preg_match('/[\x00-\x1f\x7f]/', $raw)) {
            $fail('messages.invalid_navigation_url');
            return;
        }

        // 2. Reject backslashes in raw form
        if (str_contains($raw, '\\')) {
            $fail('messages.invalid_navigation_url');
            return;
        }

        // 3. Reject protocol-relative forms (e.g. //evil.com, /\\evil.com)
        if (str_starts_with($raw, '//') || str_starts_with($raw, '/\\')) {
            $fail('messages.invalid_navigation_url');
            return;
        }

        // 4. Bounded percent-decode loop (max 5 iterations to catch nested encoding like %252f)
        $decoded = $raw;
        $iterations = 0;
        $maxIterations = 5;

        while ($iterations < $maxIterations) {
            $prev = $decoded;
            $decoded = rawurldecode($decoded);
            $iterations++;

            // Reject controls or null bytes revealed after decode
            if (preg_match('/[\x00-\x1f\x7f]/', $decoded)) {
                $fail('messages.invalid_navigation_url');
                return;
            }

            // Reject backslashes revealed after decode
            if (str_contains($decoded, '\\')) {
                $fail('messages.invalid_navigation_url');
                return;
            }

            // Reject protocol-relative forms revealed after decode
            if (str_starts_with($decoded, '//') || str_starts_with($decoded, '/\\')) {
                $fail('messages.invalid_navigation_url');
                return;
            }

            if ($decoded === $prev) {
                break;
            }
        }

        // 5. Check URL structure
        // Safe root-relative path (must start with '/' and NOT followed by another '/' or '\')
        if (str_starts_with($raw, '/')) {
            if (str_starts_with($raw, '//') || str_starts_with($raw, '/\\')) {
                $fail('messages.invalid_navigation_url');
                return;
            }
            return;
        }

        // Absolute URL validation
        $parsed = parse_url($raw);
        if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            $fail('messages.invalid_navigation_url');
            return;
        }

        $scheme = strtolower($parsed['scheme']);

        // Only allow https (and http for local/dev if needed)
        if (!in_array($scheme, ['https', 'http'], true)) {
            $fail('messages.invalid_navigation_url');
            return;
        }

        // Dangerous schemes check on decoded string
        $loweredDecoded = strtolower($decoded);
        $dangerousSchemes = ['javascript:', 'data:', 'vbscript:', 'file:', 'blob:', 'about:'];
        foreach ($dangerousSchemes as $danger) {
            if (str_contains(str_replace([' ', "\t", "\n", "\r"], '', $loweredDecoded), $danger)) {
                $fail('messages.invalid_navigation_url');
                return;
            }
        }

        // Reject embedded credentials (user:pass@host)
        if (isset($parsed['user']) || isset($parsed['pass'])) {
            $fail('messages.invalid_navigation_url');
            return;
        }
    }
}
