@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold">Staff Home</h1>
    <p class="mt-2">Hello, {{ auth()->user()->first_name }}!</p>
    <div class="mt-6 flex flex-wrap gap-4">
        <a href="/staff/applications" class="btn btn-primary">My applications</a>
        <a href="/staff/encode" class="btn btn-ghost">Encode applicant</a>
    </div>
</div>
@endsection
