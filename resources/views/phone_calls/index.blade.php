@extends('layouts.app')

@section('title', '📞 電話反響一覧')

@section('content')

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl, {
                    html: true
                });
            });
        });
    </script>
    @endpush
    <h2>📞 電話反響一覧</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('calls.import') }}" method="POST" class="mb-3">
        @csrf
        <button type="submit" class="btn btn-primary">電話取り込み</button>
    </form>

    <form method="GET" action="{{ route('calls.index') }}" class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" name="keyword" class="form-control" placeholder="名前・電話番号・内容で検索" value="{{ request('keyword') }}">
        </div>
        <div class="col-md-3">
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
                <option value="">サイト名を選択</option>
                @foreach ($sites as $s)
                    <option value="{{ $s }}" {{ request('site') === $s ? 'selected' : '' }}>
                        {{ $s }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">検索</button>
        </div>
    </form>

    <table class="table table-bordered bg-white">
        <thead>
            <tr>
                <th>日時</th>
                <th>サイト名</th>
                <th>地域</th>
                <th>電話番号</th>
                <th>性別</th>
                <th>依頼内容</th>
                <th>担当者</th>
                <th>対応状況</th>
                <th>対応方法</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($calls as $call)
                <tr>
                    <td>{{ $call->call_date }} {{ substr($call->call_time, 0, 5) }}</td>
                    <td>{{ $call->site }}</td>
                    <td>{{ $call->region }}</td>
                    <td>{{ $call->customer_phone }}</td>
                    <td>
                        <span
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-html="true"
                            title="{{$call->gender}}"
                        >
                        {{ Str::limit($call->gender, 4, '...') }}
                    </td>
                    <td>
                        @php
                            $cutoffDate = \Carbon\Carbon::create(2025, 7, 18);
                            $isAfterCutoff = \Carbon\Carbon::parse($call->call_date)->greaterThanOrEqualTo($cutoffDate);
                        @endphp

                        <span
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            data-bs-html="true"
                            title="{{
                                $isAfterCutoff
                                    ? "{$call->request_detail} ---> {$call->staff_response} ---> {$call->customer_reply}"
                                    : "{$call->request_type} ---> {$call->request_detail} ---> {$call->staff_response} ---> {$call->customer_reply}"
                            }}"
                        >
                            {{
                                $isAfterCutoff
                                    ? Str::limit($call->request_detail, 20, '...')
                                    : Str::limit($call->request_type, 20, '...')
                            }}
                        </span>
                    </td>
                    <td>{{ optional($call->latestResponse)->staff_name ?? '-' }}</td>
                    <td>
                        @php
                            $status = optional($call->latestResponse)->status ?? '-';
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
                    <td>{{ optional($call->latestResponse)->method ?? '-' }}</td>
                    <td><a href="{{ route('calls.show', $call->id) }}" class="btn btn-sm btn-primary">表示</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $calls->appends(request()->query())->links() }}

@endsection
