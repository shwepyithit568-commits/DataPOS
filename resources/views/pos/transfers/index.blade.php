@extends('layouts.admin.app')

@section('title', __('messages.transfers_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
    @include('pos.transfers._content')
@endsection
