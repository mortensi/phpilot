
<!-- resources/views/home.blade.php -->
@extends('layouts.layout')

@section('title', 'Home')

@section('content')
<div class="columns">
    <div class="column"></div>
    <div class="column is-two-thirds">
        <h1 id="name" class="title is-4 pb-3">Logger</h1>
        <pre style="line-height: 1;">
            <ul>
                @foreach($logs as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </pre>
    </div>
    <div class="column"></div>
</div>

@endsection

@section('footer')
@endsection