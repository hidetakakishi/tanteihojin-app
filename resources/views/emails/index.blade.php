@extends('layouts.app')

@section('title', '受信メール一覧')

@section('content')
    <h2>📩 メール一覧</h2>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('emails.import') }}" method="POST" class="mb-3">
        @csrf
        <button type="submit" class="btn btn-primary">メール取り込み</button>
        {{-- <button type="submit" class="btn btn-primary" style="pointer-events: none; opacity: 0.6;">メール取り込み</button> --}}
    </form>

    <form method="GET" action="{{ route('emails.index') }}" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="keyword" class="form-control" placeholder="件名・本文で検索" value="{{ request('keyword') }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">対応状況を選択</option>
                @foreach (['未対応', '対応中', '対応済み', '対応保留'] as $option)
                    <option value="{{ $option }}" {{ request('status') === $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="site" class="form-select">
                <option value="">サイトを選択</option>
                @foreach ($emails->pluck('site')->unique()->sort() as $option)
                    <option value="{{ $option }}" {{ request('site') === $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="sort" class="form-select">
                <option value="desc" {{ request('sort') === 'desc' ? 'selected' : '' }}>送信日時（新しい順）</option>
                <option value="asc" {{ request('sort') === 'asc' ? 'selected' : '' }}>送信日時（古い順）</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="deleted" class="form-select">
                <option value="">削除メール除外</option>
                <option value="with" {{ request('deleted') === 'with' ? 'selected' : '' }}>削除メールを含める</option>
                <option value="only" {{ request('deleted') === 'only' ? 'selected' : '' }}>削除メールのみ</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">検索</button>
        </div>
    </form>
    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>送信日時</th>
                <th>件名</th>
                <th>サイト名</th>
                <th>対応日</th>
                <th>担当者</th>
                <th>対応状況</th>
                <th>対応方法</th>
                <th>詳細</th>
                {{-- <th>削除</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach($emails as $email)
                <tr>
                    <td>{{ $email->sent_at }}</td>
                    <td>
                        <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ Str::limit($email->body, 1500) }}">
                            {{ $email->subject }}
                        </span>
                    </td>
                    <td>{{ $email->site }}</td>
                    <td>{{ optional($email->latestResponse)->handled_at ?? '-' }}</td>
                    <td>
                        {{ optional($email->latestResponse)->staff_name ?? '-' }}
                    </td>
                    <td>
                        @php
                            $status = optional($email->latestResponse)->status ?? '-';
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
                    <td>{{ optional($email->latestResponse)->method ?? '-' }}</td>
                    <td><a href="{{ route('emails.show', $email->id) }}" class="btn btn-sm btn-primary">表示</a></td>
                    {{-- <td>
                        <form method="POST" action="{{ route('emails.destroy', $email->id) }}" onsubmit="return confirm('本当にこのメールを削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                        </form>
                    </td> --}}
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $emails->links() }}

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
    @endpush
@endsection