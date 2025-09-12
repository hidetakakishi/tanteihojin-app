@extends('layouts.app')

@section('title','求人反響一覧')

@section('content')
<h2>👔 求人反響一覧</h2>

@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if (session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

<form action="{{ route('jobapps.import') }}" method="POST" class="mb-3">
  @csrf
  <button type="submit" class="btn btn-primary">求人データ取り込み</button>
  {{-- <button type="submit" name="all" value="1" class="btn btn-outline-secondary ms-2">すべて取り込み</button> --}}
</form>

<form method="GET" action="{{ route('jobapps.index') }}" class="row g-3 mb-4">
  <div class="col-md-4">
    <input type="text" name="keyword" class="form-control" placeholder="氏名・地域・メール・本文 など" value="{{ request('keyword') }}">
  </div>
  <div class="col-md-2">
    <select name="status" class="form-select">
      <option value="">対応状況を選択</option>
      @foreach (['未対応','対応中','対応済み','対応保留'] as $opt)
        <option value="{{ $opt }}" {{ request('status')===$opt?'selected':'' }}>{{ $opt }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-2">
    <select name="site" class="form-select">
      <option value="">サイトを選択</option>
      @foreach ($applications->pluck('site')->unique()->sort() as $opt)
        <option value="{{ $opt }}" {{ request('site')===$opt?'selected':'' }}>{{ $opt }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-2">
    <select name="sort" class="form-select">
      <option value="desc" {{ request('sort')==='desc'?'selected':'' }}>送信日時（新しい順）</option>
      <option value="asc"  {{ request('sort')==='asc'?'selected':''  }}>送信日時（古い順）</option>
    </select>
  </div>
  <div class="col-md-2">
    <select name="deleted" class="form-select">
      <option value="">削除除外</option>
      <option value="with" {{ request('deleted')==='with'?'selected':'' }}>削除含む</option>
      <option value="only" {{ request('deleted')==='only'?'selected':'' }}>削除のみ</option>
    </select>
  </div>
  <div class="col-md-3">
    <button type="submit" class="btn btn-primary">検索</button>
  </div>
</form>

<table class="table table-sm table-striped align-middle">
    <thead>
        <tr>
            <th>送信日時</th>
            <th>氏名/地域</th>
            <th>年齢/性別</th> {{-- ← 追加 --}}
            <th>連絡先</th>
            <th>希望</th>
            {{-- <th>サイト</th> --}}
            <th>対応日</th>
            <th>担当者</th>
            <th>対応状況</th>
            <th>対応方法</th>
            <th>詳細</th>
        </tr>
    </thead>
<tbody>
    @foreach($applications as $a)
    <tr>
        <td>{{ optional($a->sent_at)->format('Y-m-d H:i') }}</td>

        {{-- 氏名/地域（氏名に原文ツールチップ） --}}
        <td>
        <div class="fw-bold">
            <span
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="{{ \Illuminate\Support\Str::limit($a->body ?? '原文なし', 1500) }}"
            >
            {{ $a->name ?? '-' }}
            </span>
        </div>
        <div class="text-muted small">{{ $a->region ?? '-' }}</div>
        </td>

        {{-- 年齢/性別（追加） --}}
        <td>
        <div>{{ $a->age ?? '-' }}</div>
        <div class="text-muted small">{{ $a->gender ?? '-' }}</div>
        </td>

        {{-- 連絡先 --}}
        <td>
        <div>{{ $a->phone ?? '-' }}</div>
        <div class="text-muted small">{{ $a->email ?? '-' }}</div>
        </td>

        {{-- 希望 --}}
        <td>
        <div>{{ $a->desired_type ?? '-' }}</div>
        <div class="text-muted small">{{ $a->desired_area ?? '-' }}</div>
        </td>

        {{-- <td>{{ $a->site ?? '-' }}</td> --}}
        <td>{{ optional($a->latestResponse)->handled_at?->format('Y-m-d H:i') ?? '-' }}</td>
        <td>{{ optional($a->latestResponse)->staff_name ?? '-' }}</td>

        {{-- 対応状況バッジ（既存のまま） --}}
        <td>
        @php
            $status = optional($a->latestResponse)->status ?? '-';
            $color = match($status) {
            '未対応' => 'bg-danger',
            '対応中' => 'bg-primary',
            '対応済み' => 'bg-dark',
            '対応保留' => 'bg-secondary',
            default => ''
            };
        @endphp
        <span class="badge {{ $color }}">{{ $status }}</span>
        </td>

        <td>{{ optional($a->latestResponse)->method ?? '-' }}</td>
        <td><a href="{{ route('jobapps.show', $a->id) }}" class="btn btn-sm btn-primary">表示</a></td>
    </tr>
    @endforeach
</tbody>
</table>

{{ $applications->links() }}

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
  });
});
</script>
@endpush

@endsection