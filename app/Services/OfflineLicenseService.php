<?php

namespace App\Services;

class OfflineLicenseService
{
    /**
     * Generate a signed base64 offline license key.
     *
     * @param array{
     *     store_name: string,
     *     store_slug: string,
     *     edition: string,
     *     tier: string,
     *     max_branches: int,
     *     expires_at: string,
     *     machine_fingerprint?: string|null
     * } $payload
     * @param string $secretKey
     * @return string
     */
    public function generateSignedLicense(array $payload, string $secretKey): string
    {
        $payload['issued_at'] = now()->toIso8601String();

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $json, $secretKey);

        $envelope = [
            'data'      => base64_encode($json),
            'signature' => $signature,
        ];

        return base64_encode(json_encode($envelope));
    }

    /**
     * Verify an offline license key against cryptographic signature and constraints.
     *
     * @param string $licenseKey
     * @param string $secretKey
     * @param string|null $expectedStoreSlug
     * @return array{
     *     is_valid: bool,
     *     status: string,
     *     message: string,
     *     payload: array<string, mixed>|null
     * }
     */
    public function verifyLicense(string $licenseKey, string $secretKey, ?string $expectedStoreSlug = null): array
    {
        $decodedEnvelope = base64_decode(trim($licenseKey), true);
        if (! $decodedEnvelope) {
            return [
                'is_valid' => false,
                'status'   => 'invalid_format',
                'message'  => 'License key format is invalid.',
                'payload'  => null,
            ];
        }

        $envelope = json_decode($decodedEnvelope, true);
        if (! is_array($envelope) || empty($envelope['data']) || empty($envelope['signature'])) {
            return [
                'is_valid' => false,
                'status'   => 'invalid_envelope',
                'message'  => 'License key payload or signature is missing.',
                'payload'  => null,
            ];
        }

        $rawJson = base64_decode($envelope['data'], true);
        if (! $rawJson) {
            return [
                'is_valid' => false,
                'status'   => 'invalid_data',
                'message'  => 'License key corrupted data.',
                'payload'  => null,
            ];
        }

        // Verify HMAC-SHA256 signature
        $expectedSignature = hash_hmac('sha256', $rawJson, $secretKey);
        if (! hash_equals($expectedSignature, $envelope['signature'])) {
            return [
                'is_valid' => false,
                'status'   => 'signature_mismatch',
                'message'  => 'License key cryptographic signature is invalid or tampered.',
                'payload'  => null,
            ];
        }

        $payload = json_decode($rawJson, true);
        if (! is_array($payload)) {
            return [
                'is_valid' => false,
                'status'   => 'invalid_payload',
                'message'  => 'License content is not a valid JSON structure.',
                'payload'  => null,
            ];
        }

        // Check store slug match
        if ($expectedStoreSlug !== null && ! empty($payload['store_slug'])) {
            if ($payload['store_slug'] !== $expectedStoreSlug) {
                return [
                    'is_valid' => false,
                    'status'   => 'store_mismatch',
                    'message'  => "License is issued for store [{$payload['store_slug']}], but attempted to use with [{$expectedStoreSlug}].",
                    'payload'  => $payload,
                ];
            }
        }

        // Check expiration
        if (! empty($payload['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($payload['expires_at'])->endOfDay();
            if ($expiresAt->isPast()) {
                return [
                    'is_valid' => false,
                    'status'   => 'expired',
                    'message'  => "License expired on {$expiresAt->toDateString()}.",
                    'payload'  => $payload,
                ];
            }
        }

        return [
            'is_valid' => true,
            'status'   => 'active',
            'message'  => 'License is verified and valid.',
            'payload'  => $payload,
        ];
    }

    /**
     * Generate machine hardware fingerprint identifier for standalone PC binding.
     */
    public function getMachineFingerprint(): string
    {
        $components = [
            php_uname('n'), // Hostname
            php_uname('s'), // OS Name
            php_uname('m'), // Machine architecture
            get_current_user(),
        ];

        return hash('sha256', implode('::', $components));
    }
}
