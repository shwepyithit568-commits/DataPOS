<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(4);
if (!$user) { echo "NO_USER_4\n"; exit(1); }

$guard = app('auth')->guard('web');
$ref = new ReflectionClass($guard);
$method = $ref->getMethod('getName');
$method->setAccessible(true);
$authKey = $method->invoke($guard);

// Exactly 40 alnum chars.
$sid = Illuminate\Support\Str::random(40);
$payload = [
    '_token' => Illuminate\Support\Str::random(40),
    $authKey => $user->getAuthIdentifier(),
    '_flash' => new Illuminate\Support\Collection(),
];
$encoded = base64_encode(serialize($payload));

DB::table('sessions')->updateOrInsert(
    ['id' => $sid],
    [
        'user_id' => 4,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'lighthouse',
        'payload' => $encoded,
        'last_activity' => time(),
    ]
);

// EncryptCookies middleware prefixes the value: CookieValuePrefix::create + value.
$cookieName = 'datapos-commerce-session';
$key = $app['encrypter']->getKey();
$prefixed = Illuminate\Cookie\CookieValuePrefix::create($cookieName, $key) . $sid;

echo "SID: {$sid}\n";
echo "PREFIX: " . substr($prefixed, 0, 45) . "...\n";
echo "COOKIE_VALUE: " . encrypt($prefixed, false) . "\n";
