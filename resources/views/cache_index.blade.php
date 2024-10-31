
<!-- resources/views/home.blade.php -->
@extends('layouts.layout')

@section('title', 'Home')

@section('content')
<div class="columns">
    <div class="column"></div>
    <div class="column is-two-thirds">
        <h1 id="name" class="title is-4 pb-3">Cache</h1>
        <h2 class="title is-5">Search the cache</h2>

        <form class="mt-4 mb-5" method="get" action="{{ url('/cache') }}">
            <div class="field has-addons">

                <div class="control is-expanded">
                    <input name="q" class="input" type="text" placeholder="Search in the cache" value="{{ request('q') }}">
                </div>
                <div class="control">
                    <div class="select">
                        <select name="s">
                            <option value="semantic" {{ request('s') == 'semantic' ? 'selected' : '' }}>semantic</option>
                            <option value="fulltext" {{ request('s') == 'fulltext' ? 'selected' : '' }}>full-text</option>
                        </select>
                    </div>
                </div>
                <div class="control has-icons-left">
                    <button id="chat" type="submit" class="button" autocomplete="off">
                        <svg class="overflow-visible" width="18" height="19" viewBox="0 0 18 19" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M14.25 7.72059C14.25 11.591 11.2076 14.6912 7.5 14.6912C3.79242 14.6912 0.75 11.591 0.75 7.72059C0.75 3.8502 3.79242 0.75 7.5 0.75C11.2076 0.75 14.25 3.8502 14.25 7.72059Z" stroke-width="1.5"/>
                          <path d="M12 12.3529L17 17.5" stroke-width="1.5"/>
                        </svg>
                    </button>
                </div>
            </div>
        </form>

        @if(isset($data) && count($data) > 0)
            <ul>
                @foreach($data as $entry)
                    <li>
                        <h3 class="title is-6 p-0 mb-2">{{ $entry['question'] }}</h3>

                        <div class="bubble">
                            <form action="{{ url('/cache/save') }}" method="post">
                                @csrf
                                <input type="hidden" name="id" value="{{ $entry['id'] }}">
                                <textarea name="content" style="width:100%; padding:0em; border:none; outline:none;" spellcheck="false">{{ $entry['answer'] }}</textarea>
                                <div class="mt-2">
                                    <button type="submit" class="p-0 m-0 button is-ghost">
                                        Save
                                    </button>
                                    <a class="p-0 m-0 button is-ghost ml-2 delete_idx_anchor" href="{{ route('cache.delete', ['id' => $entry['id']]) }}">
                                        Delete
                                    </a>
                                </div>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No items found in the cache</p>
        @endif
    </div>
    <div class="column"></div>
</div>

@endsection

@section('footer')
@endsection