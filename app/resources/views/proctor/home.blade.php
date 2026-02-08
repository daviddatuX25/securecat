@extends('layouts.app')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold">Proctor Home</h1>
    <p class="mt-2">Hello, {{ auth()->user()->first_name }}!</p>
</div>
@endsection
