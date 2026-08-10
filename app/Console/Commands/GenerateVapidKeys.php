<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Minishlink\WebPush\VAPID;

/**
 * Generate VAPID keys for Web Push and store them in .env.
 *
 * Run with: php artisan vapid:generate
 * Options:
 *   --subject=mailto:admin@example.com   VAPID subject (must be a URL or mailto:)
 *   --force                              Overwrite existing keys
 *   --show                               Print keys without writing them
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'vapid:generate
                            {--subject= : VAPID subject (https URL or mailto:) }
                            {--force : Overwrite existing VAPID keys }
                            {--show : Print keys instead of writing them }';

    protected $description = 'Generate VAPID keys for Web Push and store them in .env';

    public function handle(): int
    {
        $keys = $this->createVapidKeys();

        $subject = $this->option('subject');
        if (! $subject) {
            // Default to a mailto: derived from the app URL host, which the
            // push services accept as a VAPID subject.
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
            $subject = 'mailto:admin@' . $host;
        }

        if ($this->option('show')) {
            $this->line("VAPID_SUBJECT={$subject}");
            $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
            $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");
            $this->newLine();
            $this->warn('--show prints the keys without writing them.');

            return self::SUCCESS;
        }

        $envPath = $this->laravel->environmentFilePath();
        $contents = file_get_contents($envPath);

        $existing = $this->existingVapidKeys($contents);
        if ($existing && ! $this->option('force') && ! $this->confirm('VAPID keys already exist. Overwrite them?')) {
            $this->info('Keeping existing VAPID keys.');

            return self::SUCCESS;
        }

        $updated = $this->writeKey($contents, 'VAPID_SUBJECT', $subject, $envPath);
        $updated = $this->writeKey($updated, 'VAPID_PUBLIC_KEY', $keys['publicKey'], $envPath);
        $this->writeKey($updated, 'VAPID_PRIVATE_KEY', $keys['privateKey'], $envPath);

        $this->info('VAPID keys generated and stored in .env');
        $this->line("VAPID_SUBJECT={$subject}");

        return self::SUCCESS;
    }

    /**
     * Generate a fresh VAPID key pair.
     *
     * Prefers minishlink's VAPID::createVapidKeys(). If OpenSSL cannot create
     * the P-256 key (seen on some Windows/XAMPP builds where the default
     * openssl.cnf is not on the OpenSSL search path), falls back to a direct
     * openssl_pkey_new() call with an explicitly discovered config file.
     *
     * @return array{publicKey: string, privateKey: string}
     */
    protected function createVapidKeys(): array
    {
        try {
            return VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            $this->warn('minishlink key generation failed (' . $e->getMessage() . ') — trying direct OpenSSL.');
        }

        $config = $this->findOpenSslConfig();
        $options = [
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ];
        if ($config) {
            $options['config'] = $config;
        }

        $key = openssl_pkey_new($options);
        if ($key === false) {
            throw new \RuntimeException('Unable to create the key: ' . (openssl_error_string() ?: 'unknown error'));
        }

        $details = openssl_pkey_get_details($key);
        if (! isset($details['ec'])) {
            throw new \RuntimeException('Generated key is not an EC key.');
        }

        // PHP returns the EC point coordinates and private scalar as raw
        // binary strings; the uncompressed public key is 0x04 || X || Y.
        $x = $details['ec']['x'] ?? '';
        $y = $details['ec']['y'] ?? '';
        $d = $details['ec']['d'] ?? '';

        if (strlen($x) !== 32 || strlen($y) !== 32 || strlen($d) !== 32) {
            throw new \RuntimeException('Unexpected EC key geometry (expected P-256).');
        }

        $publicKey = rtrim(strtr(base64_encode(chr(4) . $x . $y), '+/', '-_'), '=');
        $privateKey = rtrim(strtr(base64_encode(str_pad($d, 32, "\0", STR_PAD_LEFT)), '+/', '-_'), '=');

        return ['publicKey' => $publicKey, 'privateKey' => $privateKey];
    }

    /**
     * Locate an openssl.cnf when the default one is not on the search path
     * (common on Windows/XAMPP). Returns null when nothing is found.
     */
    protected function findOpenSslConfig(): ?string
    {
        $envConfig = getenv('OPENSSL_CONF');
        if ($envConfig && file_exists($envConfig)) {
            return $envConfig;
        }

        // Common relative-to-PHP locations for bundled configs.
        $phpDir = dirname(PHP_BINARY);
        $candidates = [
            $phpDir . '/extras/openssl/openssl.cnf',
            $phpDir . '/openssl.cnf',
            $phpDir . '/../common/ssl/openssl.cnf',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Whether the .env already contains any VAPID_* keys.
     */
    protected function existingVapidKeys(string $contents): bool
    {
        return Str::contains($contents, ['VAPID_PUBLIC_KEY', 'VAPID_PRIVATE_KEY', 'VAPID_SUBJECT']);
    }

    /**
     * Set (or append) a single KEY=value line in the .env content and persist.
     */
    protected function writeKey(string $contents, string $key, string $value, string $envPath): string
    {
        $line = $key . '=' . $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $contents)) {
            $contents = preg_replace(
                '/^' . preg_quote($key, '/') . '=.*$/m',
                $line,
                $contents
            );
        } else {
            $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
        }

        file_put_contents($envPath, $contents);

        return $contents;
    }
}
