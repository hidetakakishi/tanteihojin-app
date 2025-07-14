@extends('layouts.app')

@section('title', '記事一覧')

@section('content')
<h1 class="mb-4">📰 記事一覧</h1>


<div class="d-flex justify-content-between align-items-center mb-3">
    <!-- 左側：更新 & URL登録 -->
    <div class="d-flex gap-2">
        <!-- 更新ボタン -->
        <form method="POST" action="{{ route('scraping.updateAll') }}">
            @csrf
            <button type="submit" class="btn btn-success">
                更新
            </button>
        </form>
        <a href="{{ route('scraping.sources') }}" class="btn btn-secondary">URL登録画面へ</a>
    </div>

    <!-- 右側：並び順 & URLフィルター -->
    <form method="GET" action="{{ route('scraping.index') }}" class="d-flex align-items-center gap-2">
        <!-- 並び順 -->
        <label for="sort" class="mb-0" style="white-space: nowrap;">並び順:</label>
        <select id="sort" name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="desc" {{ $sort === 'desc' ? 'selected' : '' }}>新しい順</option>
            <option value="asc" {{ $sort === 'asc' ? 'selected' : '' }}>古い順</option>
        </select>

        <!-- URLフィルター -->
        <label for="source" class="mb-0 ms-3" style="white-space: nowrap;">サイト名指定:</label>
        <select id="source" name="source" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px; max-width: 400px;">
            <option value="">すべて表示</option>
            @foreach($sources as $source)
                <option value="{{ $source->id }}" {{ $sourceId == $source->id ? 'selected' : '' }}>
                    {{ $source->name }}
                </option>
            @endforeach
        </select>
    </form>
</div>

<ul class="list-group mb-3">
    @foreach($articles as $article)
        <li class="list-group-item">
            <a href="{{ $article->url }}" target="_blank">{{ $article->title ?? '(タイトルなし)' }}</a>

            @if ($article->source)
                <div class="text-muted small">
                    サイト名：{{ $article->source->name }}
                </div>
            @endif

            <div class="text-muted small">
                取得日時：{{ $article->created_at->format('Y-m-d H:i') }}
            </div>
        </li>
    @endforeach
</ul>

<!-- ページネーションリンク -->
<div>
    {{ $articles->links() }}
</div>
@endsection