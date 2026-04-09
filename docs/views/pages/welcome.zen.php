@extends('layouts.main')

@section('title', 'Welcome')

@section('content')
<div class="text-center py-16">
    <h1 class="text-4xl font-bold mb-4">Welcome to Your Zen App</h1>
    <p class="text-xl text-gray-600 mb-8">Built with Zen Framework v2.0</p>
    <div class="flex gap-4 justify-center">
        <a href="/docs" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Documentation
        </a>
        <a href="/api/status" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
            API Status
        </a>
    </div>
</div>
@endsection