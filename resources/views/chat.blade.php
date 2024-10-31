
<!-- resources/views/home.blade.php -->
@extends('layouts.layout')

@section('title', 'Home')

@section('content')
<div style="flex: 1; display: flex; flex-direction: column; margin-bottom: 110px;">
    <div class="columns">
        <div class="column is-hidden-mobile"></div>
        <div class="column is-two-thirds is-full-mobile">
                <div class="bubble bubble-initial text-lg tracking-tight">
                    <p>
                        Hello! I am the Phpilot, how can I help you?
                    </p>
                </div>
            <span id="conversation">
            </span>
        </div>
        <div class="column is-hidden-mobile"></div>
    </div>
</div>
@endsection

@section('footer')
    @include('includes.footer')
@endsection