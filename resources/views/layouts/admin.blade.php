{{-- Super admin: sidebar shell, platform-wide. --}}
@extends('layouts.dashboard')

@section('body')
    @include('dashboard.partials.console', ['context' => ['label' => 'Platform', 'title' => 'All branches']])
@endsection
