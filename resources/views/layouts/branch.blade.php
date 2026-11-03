{{-- Branch admin: sidebar shell; the header carries the branch, its open/closed state and the time. --}}
@extends('layouts.dashboard')

@php
    $branch = app(\App\Services\Demo\DemoDataService::class)->branch(auth()->user()->branchKey());
@endphp

@section('body')
    @include('dashboard.partials.console', ['context' => ['label' => 'Branch', 'title' => $branch['name'] ?? 'Branch'], 'branch' => $branch])
@endsection
