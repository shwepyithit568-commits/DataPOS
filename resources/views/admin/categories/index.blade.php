@extends('layouts.admin.app')

@section('title', __('messages.categories') . ' · ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
    @include('admin.categories._content')
@endsection
