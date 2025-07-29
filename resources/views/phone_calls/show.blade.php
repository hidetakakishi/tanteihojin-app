@extends('layouts.app')

@section('title', '📞 電話反響詳細')

@section('content')
    <h2>📞 電話反響詳細</h2>
    <div class="d-flex justify-content-between mb-3">
        @if($previous)
            <a href="{{ route('calls.show', $previous->id) }}" class="btn btn-outline-secondary">&laquo; 前の電話</a>
        @else
            <div></div> {{-- 左スペース保持 --}}
        @endif

        @if($next)
            <a href="{{ route('calls.show', $next->id) }}" class="btn btn-outline-secondary">次の電話 &raquo;</a>
        @endif
    </div>
    <div class="mb-4">
        <a href="{{ route('calls.index') }}" class="btn btn-secondary">一覧に戻る</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <tr><th>サイト</th><td>{{ $call->site }}</td></tr>
        <tr><th>日時</th><td>{{ $call->call_date }} {{ substr($call->call_time, 0, 5) }}</td></tr>
        <tr><th>地域</th><td>{{ $call->region }}</td></tr>
        <tr><th>氏名</th><td>{{ $call->customer_name }}</td></tr>
        <tr><th>電話番号</th><td>{{ $call->customer_phone }}</td></tr>
        <tr><th>性別</th><td>{{ $call->gender }}</td></tr>
        @php
            $cutoffDate = \Carbon\Carbon::create(2025, 7, 18);
        @endphp

        @if (\Carbon\Carbon::parse($call->call_date)->lt($cutoffDate))
            <tr><th>相談内容</th><td>{{ $call->request_type }}</td></tr>
        @endif
        <tr><th>相談内容</th><td>{{ $call->request_detail }}</td></tr>
        <tr><th>調査の提案</th><td>{{ $call->staff_response }}</td></tr>
        <tr><th>今後の予定</th><td>{{ $call->customer_reply }}</td></tr>
    </table>

    <h4 class="mt-5">📋 対応履歴</h4>

    @foreach($call->responses as $response)
        <form method="POST" action="{{ route('calls.response.update', $response->id) }}" class="border p-3 mb-4 bg-light">
            @csrf
            @method('PUT')
            <div class="row mb-2">
                <div class="col-md-3">
                    <label class="form-label">対応日</label>
                    <input type="datetime-local" name="handled_at" class="form-control" value="{{ optional($response->handled_at)->format('Y-m-d\TH:i') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">担当者</label>
                    <input type="text" name="staff_name" class="form-control" value="{{ $response->staff_name }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">対応状況</label>
                    <select name="status" class="form-select">
                        @foreach (['未対応', '対応中', '対応済み', '対応保留'] as $option)
                            <option value="{{ $option }}" {{ $response->status === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">対応方法</label>
                    <select name="method" class="form-select">
                        <option value="">選択してください</option>
                        @foreach (['電話', 'SMS', 'メール', '訪問', 'その他'] as $option)
                            <option value="{{ $option }}" {{ $response->method === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">メモ</label>
                <textarea name="memo" class="form-control" rows="3">{{ $response->memo }}</textarea>
            </div>
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-success">更新</button>
                <form method="POST" action="{{ route('calls.response.destroy', $response->id) }}" onsubmit="return confirm('この対応履歴を削除しますか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">🗑 削除</button>
                </form>
            </div>
        </form>
    @endforeach

<hr>
<h4>対応状況を登録</h4>
<form method="POST" action="{{ route('calls.response.store', $call->id) }}">
    @csrf
    <div class="mb-2">
        <label for="status" class="form-label">対応状況</label>
        <select name="status" id="status" class="form-select">
            <option value="">選択してください</option>
            @foreach (['未対応', '対応中', '対応済み', '対応保留'] as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-2">
        <label for="staff_name" class="form-label">担当者</label>
        <select name="staff_name" id="staff_name" class="form-select">
            <option value="">選択してください</option>
            @foreach (['北野', '岩下', '松元', '小泉','柴田','名古屋','平松','岸','簑和田','岡田','小林','長尾'] as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-2">
        <label for="handled_at" class="form-label">対応日</label>
        <input type="datetime-local" name="handled_at" id="handled_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
    </div>
    <div class="mb-2">
        <label for="method" class="form-label">対応方法</label>
        <select name="method" class="form-select">
            <option value="">選択してください</option>
            @foreach (['アポ', '電話', '追い電話', 'SMS'] as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="memo" class="form-label">メモ</label>
        <textarea name="memo" id="memo" rows="3" class="form-control"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">登録する</button>
</form>
@endsection