@extends('layouts.app')

@section('title', 'URL登録・管理')

@section('content')
<h1 class="mb-4">URL 登録画面</h1>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('scraping.store') }}" class="row g-3 mb-4">
    @csrf
    <div class="col-md-4">
        <input type="text" name="name" class="form-control" required placeholder="サイト名">
    </div>
    <div class="col-md-6">
        <input type="url" name="url" class="form-control" required placeholder="登録したいURL">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">登録</button>
    </div>
</form>

<ul class="list-group">
@foreach($sources as $source)
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
            <strong>{{ $source->name }}</strong><br>
            <small class="text-muted">{{ $source->url }}</small><br>

            @php
                $latest = $source->articles->first(); // withで並べてあるので先頭が最新
            @endphp

            <small class="text-muted">
                最終スクレイピング時間:
                {{ $latest ? $latest->created_at->format('Y-m-d H:i') : 'なし' }}
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('scraping.run', $source->id) }}" class="btn btn-sm btn-success">
                スクレイピング実行
            </a>
            <form method="POST" action="{{ route('scraping.destroy', $source->id) }}" onsubmit="return confirm('削除しますか？');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-danger">削除</button>
            </form>
        </div>
    </li>
@endforeach
</ul>

<a href="{{ route('scraping.index') }}" class="btn btn-link mt-3">記事一覧に戻る</a>
@endsection