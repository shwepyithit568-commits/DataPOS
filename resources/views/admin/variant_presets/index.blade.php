@extends('layouts.admin.app')

@section('title', __('messages.variant_presets') . ' · ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
    @include('admin.variant_presets._content')
@endsection
