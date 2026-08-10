<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased flex items-center justify-center min-h-screen p-6">
    <div class="text-center max-w-md space-y-6">
        <div class="text-7xl font-bold text-violet-600 font-outfit">404</div>
        <h1 class="text-2xl font-bold text-gray-900 font-outfit">စာမျက်နှာ မတွေ့ပါ</h1>
        <p class="text-gray-600">သင်ရှာဖွေနေသော စာမျက်နှာကို ရှာမတွေ့ပါ။ ကျေးဇူးပြု၍ ပြန်လည်စစ်ဆေးပါ။</p>
        <p class="text-sm text-gray-500">The page you are looking for could not be found.</p>
        <a href="{{ url('/') }}" class="inline-block px-6 py-3 bg-violet-600 text-white font-medium rounded-md hover:bg-violet-700 transition">
            ပင်မစာမျက်နှာသို့ ပြန်သွားရန် (Back to Home)
        </a>
    </div>
</body>
</html>
