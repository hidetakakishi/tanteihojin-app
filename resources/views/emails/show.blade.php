@extends('layouts.app')

@section('title', 'メール詳細')

@section('content')
    <h2>📨 メール詳細</h2>
    <div class="d-flex justify-content-between mb-3">
        @if($previous)
            <a href="{{ route('emails.show', $previous->id) }}" class="btn btn-outline-secondary">&laquo; 前のメール</a>
        @else
            <div></div> {{-- 左スペース保持 --}}
        @endif

        @if($next)
            <a href="{{ route('emails.show', $next->id) }}" class="btn btn-outline-secondary">次のメール &raquo;</a>
        @endif
    </div>
    <div class="card mb-4">
        <div class="card-header">件名：{{ $email->subject }}　　サイト名：{{ $email->site }}</div>
        <div class="card-body">
            <p><strong>送信日時：</strong> {{ $email->sent_at }}</p>
            <p><strong>差出人：</strong> {{ $email->from }}</p>
            <p><strong>宛先：</strong> {{ $email->to }}</p>
            <hr>
            <div style="white-space: pre-wrap;">{!! nl2br(e($email->body)) !!}</div>
            <hr>
            <form method="POST" action="{{ route('emails.destroy', $email->id) }}" onsubmit="return confirm('本当にこのメールを削除しますか？');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">🗑 メールを削除する</button>
            </form>
        </div>
    </div>
    <h4>🛠 対応情報</h4>
    @if($email->responses->isNotEmpty())
        @foreach($email->responses as $response)
            <form id="update-form-{{ $response->id }}" 
                action="{{ route('emails.response.update', $response->id) }}" 
                method="POST">
                @csrf
                @method('PATCH')

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label"><strong>対応日</strong></label>
                                <input type="datetime-local" name="handled_at" class="form-control"
                                    value="{{ $response->handled_at ? \Carbon\Carbon::parse($response->handled_at)->format('Y-m-d\TH:i') : '' }}">
                            </div>
                           <div class="col-md-3">
                                <label class="form-label"><strong>担当者</strong></label>
                                <select name="staff_name" class="form-select">
                                    @foreach (['北野', '岩下', '松元', '小泉','柴田','名古屋','平松','岸','簑和田','岡田','小林','長尾'] as $option)
                                        <option value="{{ $option }}" {{ $response->staff_name === $option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>ステータス</strong></label>
                                <select name="status" class="form-select">
                                    @foreach (['未対応', '対応中', '対応済み', '対応保留'] as $option)
                                        <option value="{{ $option }}" {{ $response->status === $option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><strong>対応方法</strong></label>
                                <select name="method" class="form-select">
                                    <option value="" {{ empty($response->method) ? 'selected' : '' }}>選択してください</option>
                                    @foreach (['アポ', 'メール', '電話', '追い電話', '追いメール', 'LINE', 'その他'] as $option)
                                        <option value="{{ $option }}" {{ $response->method === $option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label"><strong>メモ</strong></label>
                            <textarea name="memo" rows="{{ empty($response->memo) ? 1 : 3 }}" class="form-control">{{ $response->memo }}</textarea>
                        </div>
                        <p class="mt-2">
                            <a href="#" 
                            onclick="event.preventDefault(); document.getElementById('update-form-{{ $response->id }}').submit();" 
                            class="text-primary text-decoration-underline">
                                更新
                            </a>
                            　
                            <a href="#" 
                            onclick="if(confirm('本当にこの対応状況を削除しますか？')) { document.getElementById('delete-form-{{ $response->id }}').submit(); } return false;" 
                            class="text-danger text-decoration-underline">
                                削除
                            </a>
                        </p>
                    </div>
                </div>
            </form>

            <form id="delete-form-{{ $response->id }}" 
                action="{{ route('emails.response.destroy', $response->id) }}" 
                method="POST" 
                style="display: none;">
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    @else
        <p class="text-muted">対応情報はまだ登録されていません。</p>
    @endif

    <hr>
    <h4>対応状況を登録</h4>
    <form method="POST" action="{{ route('emails.response.store', $email->id) }}">
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
            <select name="method"class="form-select">
                <option value="">選択してください</option>
                @foreach (['アポ', 'メール', '電話', '追い電話', '追いメール', 'LINE', 'その他'] as $option)
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