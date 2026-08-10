<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased flex items-center justify-center min-h-screen p-6">
    <div class="text-center max-w-md space-y-6">
        <div class="text-7xl font-bold text-red-500 font-outfit">500</div>
        <h1 class="text-2xl font-bold text-gray-900 font-outfit">ဆာဗာ အမှားအယွင်း ဖြစ်နေပါသည်</h1>
        <p class="text-gray-600">ကျွန်ုပ်တို့၏ စနစ်တွင် ယာယီပြဿနာ ရှိနေပါသည်။ ကျေးဇူးပြု၍ ခဏစောင့်ဆိုင်းပြီးမှ ပြန်လည်ကြိုးစားပါ။</p>
        <p class="text-sm text-gray-500">We are experiencing a temporary issue. Please try again later.</p>
        <a href="{{ url('/') }}" class="inline-block px-6 py-3 bg-violet-600 text-white font-medium rounded-md hover:bg-violet-700 transition">
            ပင်မစာမျက်နှာသို့ ပြန်သွားရန် (Back to Home)
        </a>
    </div>
</body>
</html>
