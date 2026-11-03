@extends('layouts.admin')

@section('title', $branch['short'])

@section('content')
@php
    $config = ['endpoint' => route('admin.branches.metrics', $branch['key']), 'payload' => $payload, 'poll' => config('dashboard.poll_seconds')];
@endphp

<div x-data="page(@js($config))">
    <x-dashboard.page-header :title="$branch['name']" :description="$branch['area'].' · '.$branch['phone'].' · manager '.($branch['manager'] ?? '—')"
        :breadcrumbs="['Branches' => route('admin.branches'), $branch['short'] => null]">
        <x-slot:actions>
            <x-dashboard.status :value="$branch['status']" class="mr-1" />
            <x-dashboard.date-range />
        </x-slot:actions>
    </x-dashboard.page-header>

    @include('dashboard.partials.live-status')

    @include('dashboard.partials.branch-analytics', ['showLive' => false])
</div>
@endsection
