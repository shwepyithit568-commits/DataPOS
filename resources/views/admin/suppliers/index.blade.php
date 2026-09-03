@extends('layouts.admin.app')

@section('title', __('messages.sidebar_suppliers') . ' - ' . $store->name)
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
    @include('admin.suppliers._content')
@endsection

