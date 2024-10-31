
<!-- resources/views/home.blade.php -->
@extends('layouts.layout')

@section('title', 'Home')

@section('content')
<div class="columns">
    <div class="column"></div>
    <div class="column is-two-thirds">
        <h1 id="name" class="title is-4 pb-3">Prompts</h1>

        <h2 class="title is-5">System prompt</h2>
        <div class="bubble p-4">
            <form action="{{ route('prompt.save') }}" method="post">
                @csrf  <!-- CSRF token for form security -->
                <textarea style="width:100%; padding:0em; border:none; outline:none;" id="system" name="prompt">{{ old('prompt', $system) }}</textarea>
                <input type="hidden" name="type" value="system">
                <button type="submit" class="p-0 button is-ghost is-inline">
                    Save
                </button>
            </form>
        </div>

        <h2 class="title is-5">User prompt</h2>
        <div class="bubble p-4">
            <form action="{{ route('prompt.save') }}" method="post">
                @csrf  <!-- CSRF token for form security -->
                <textarea style="width:100%; padding:0em; border:none; outline:none;" id="user" name="prompt">{{ old('prompt', $user) }}</textarea>
                <input type="hidden" name="type" value="user">
                <button type="submit" class="p-0 button is-ghost is-inline">
                    Save
                </button>
            </form>
        </div>
    </div>
    <div class="column"></div>
</div>
@endsection

@section('footer')
@endsection