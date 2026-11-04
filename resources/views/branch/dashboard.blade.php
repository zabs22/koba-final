@extends('layouts.branch')

@section('title', 'Overview')

@section('content')
@php
    $config = ['endpoint' => route('branch.dashboard.metrics'), 'payload' => $payload, 'poll' => config('dashboard.poll_seconds')];
    $hour = (int) now(config('dashboard.timezone'))->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

<div x-data="page(@js($config))">
    <x-dashboard.page-header :eyebrow="$greeting.', '.strtok(auth()->user()->name, ' ')" title="Today at the counter">
        <x-slot:description><span x-text="payload.range.label"></span> · pickups, sales and the kitchen at a glance.</x-slot:description>
        <x-slot:actions>
            <a href="{{ route('branch.live-orders') }}" class="btn-primary"><x-dashboard.icon name="flame" /> Live orders</a>
            <x-dashboard.date-range />
        </x-slot:actions>
    </x-dashboard.page-header>

    @include('dashboard.partials.live-status')

    @include('dashboard.partials.branch-analytics')
</div>
@endsection
