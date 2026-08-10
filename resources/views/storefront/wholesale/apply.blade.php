@extends('layouts.storefront.app')

@section('content')
<div class="max-w-2xl mx-auto bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-6">
    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900 font-outfit">လက်ကားဝယ်ယူခွင့် လျှောက်ထားရန် (Wholesale Application)</h1>
        <p class="text-sm text-gray-600 font-myanmar mt-1">အလင်းသစ် စနစ်တွင် လက်ကား ဈေးနှုန်းဖြင့် ဝယ်ယူရန် လျှောက်လွှာ ဖြည့်စွက်ပါ</p>
    </div>

    @if (session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-md text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($application)
        <div class="p-4 bg-blue-50 border border-blue-200 rounded-md text-sm space-y-2">
            <div class="font-semibold text-blue-900">လက်ရှိ လျှောက်လွှာ အခြေအနေ (Current Application Status):</div>
            <div class="inline-block px-3 py-1 text-xs font-bold rounded uppercase
                {{ $application->status === 'approved' ? 'bg-green-600 text-white' : '' }}
                {{ $application->status === 'pending' ? 'bg-amber-500 text-white' : '' }}
                {{ $application->status === 'rejected' ? 'bg-red-600 text-white' : '' }}
                {{ $application->status === 'suspended' ? 'bg-gray-600 text-white' : '' }}">
                {{ $application->status }}
            </div>
            @if ($application->notes)
                <p class="text-xs text-blue-800">Admin Note: {{ $application->notes }}</p>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ url('/store/' . ($store?->slug ?? 'default') . '/wholesale/apply') }}" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700">လုပ်ငန်း / ဆိုင်အမည် (Business / Store Name)</label>
            <input type="text" name="business_name" value="{{ old('business_name', $application->business_name ?? '') }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border px-3 py-2" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ဆက်သွယ်ရန် ဖုန်းနံပါတ် (Contact Phone Number)</label>
            <input type="text" name="phone" value="{{ old('phone', $application->phone ?? auth()->user()->phone) }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border px-3 py-2" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ဆိုင်လိပ်စာ (Business Address)</label>
            <textarea name="address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border px-3 py-2">{{ old('address', $application->address ?? '') }}</textarea>
        </div>

        <div>
            <button type="submit" class="w-full py-2 px-4 bg-violet-600 text-white font-medium text-sm rounded-md hover:bg-violet-700">
                {{ $application ? 'လျှောက်လွှာ အချက်အလက် ပြင်ဆင်မည်' : 'လျှောက်လွှာ တင်သွင်းမည်' }}
            </button>
        </div>
    </form>
</div>
@endsection
