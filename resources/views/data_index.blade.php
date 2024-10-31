
<!-- resources/views/home.blade.php -->
@extends('layouts.layout')

@section('title', 'Home')

@section('content')
<div class="columns">
    <div class="column"></div>
    <div class="column is-two-thirds">
        <h1 id="name" class="title is-4 pb-3">Data</h1>
        <div class="bubble">
        <h2 class="title is-5">Files</h2>
        <form id="upload" class="mb-4" method="post" action="{{ route('data.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="field has-addons">
                <div class="control is-expanded">
                    <div id="file-upload" class="file has-name is-fullwidth mb-3">
                        <label class="file-label">
                            <input class="file-input" type="file" name="asset">
                            <span class="file-cta">
                                <span class="file-label">
                                    Upload a CSV file
                                </span>
                            </span>
                            <span class="file-name">
                            </span>
                        </label>
                    </div>
                </div>
                <div class="control">
                    <button type="submit" class="button redis-yellow">Submit</button>
                </div>
            </div>
        </form>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
            <div>
                <table class="table is-fullwidth is-hoverable">
                    <colgroup>
                        <col style="width: 150px;">
                        <col>
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Index</th>
                        <th>File</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($data as $id => $filename)
                        <tr>
                            <td style="word-wrap:break-word; word-break:break-all; white-space:normal; width:70%;">
                            <span>{{ $filename }}</span>
                            </td>
                            <td>
                                <a class="create_idx_anchor" style="display:block;" href="{{ route('data.create', ['id' => $id]) }}">
                                    create
                                </a>
                            </td>
                            <td>
                                <a style="display:block;" href="{{ route('data.remove', ['id' => $id]) }}">
                                    delete
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if(empty($data))
                <p>There is no stored asset</p>
            @endif
        </div>

        <div class="bubble">
            <h2 class="title is-5">Semantic indexes</h2>
            <table class="table is-fullwidth is-hoverable">
                <thead>
                <tr>
                    <th>Index</th>
                    <th>Current</th>
                    <th>Docs</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($idx_overview as $idx)
                    <tr>
                        <td style="word-wrap: break-word; word-break: break-all; white-space: normal; width:70%">
                            <span>{{ $idx['name'] }}</span>
                        </td>
                        <td>
                            @if($idx['is_current'])
                                &#10004;
                            @endif
                        </td>
                        <td>
                            <span>{{ $idx['docs'] }}</span>
                        </td>
                        <td>
                            @if(!$idx['is_current'])
                                <div>
                                    <a class="mr-5" href="{{ route('data.current', ['name' => $idx['name']]) }}">
                                        Make current
                                    </a>
                                </div>
                            @endif
                            <a class="delete_idx_anchor" href="{{ route('data.drop', ['name' => $idx['name']]) }}">
                                Delete
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if(empty($idx_overview))
            <p>
                You have no semantic index, upload a CSV data source and create the index
            </p>
            @endif
        </div>

    </div>
    <div class="column"></div>
</div>
<script>
    const fileInput = document.querySelector("#file-upload input[type=file]");
    fileInput.onchange = () => {
        if (fileInput.files.length > 0) {
            const fileName = document.querySelector("#file-upload .file-name");
            fileName.textContent = fileInput.files[0].name;
        }
    };
</script>
@endsection

@section('footer')
@endsection