@extends('layouts.admin.app')

@section('title', __('messages.warehouses_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
    @include('admin.warehouses._content')
@endsection
