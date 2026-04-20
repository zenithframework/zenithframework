@extends('layouts.main')

@section('title', 'Test Page')

@section('content')
    <h2>Welcome to the Test Page</h2>
    
    @if($showMessage)
        <p class="success">Message is shown!</p>
    @else
        <p class="info">Message is hidden.</p>
    @endif

    <h3>Items List:</h3>
    <ul>
    @foreach($items as $item)
        <li>{{ $item }}</li>
    @endforeach
    </ul>

    <h3>Counter Loop:</h3>
    @for($i = 0; $i < 3; $i++)
        <p>Counter: {{ $i }}</p>
    @endfor

    <h3>Form Elements:</h3>
    @csrf
    @method('PUT')
    
    <h3>Auth Check:</h3>
    @auth
        <p>Welcome, authenticated user!</p>
    @endauth

    @guest
        <p>Please log in to continue.</p>
    @endguest

    <h3>JSON Data:</h3>
    <pre>@json($data)</pre>
@endsection
