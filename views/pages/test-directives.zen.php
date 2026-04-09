@extends('layouts.default')

@section('title', 'Test Directives')

@section('content')
    <h1>Testing Template Directives</h1>
    
    @if($showMessage)
        <p>Message is shown!</p>
    @else
        <p>Message is hidden.</p>
    @endif

    @foreach($items as $item)
        <div class="item">{{ $item }}</div>
    @endforeach

    @for($i = 0; $i < 3; $i++)
        <p>Counter: {{ $i }}</p>
    @endfor

    @csrf
    @method('PUT')
    @json($data)

    @auth
        <p>Welcome, authenticated user!</p>
    @endauth

    @guest
        <p>Please log in.</p>
    @endguest
@endsection
