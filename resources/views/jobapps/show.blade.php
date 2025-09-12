@extends('layouts.app')

@section('title','求人反響詳細')

@section('content')
<h2>👔 求人反響詳細</h2>

@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if (session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

<div class="d-flex justify-content-between mb-3">
  <div>
    @if($previous)<a href="{{ route('jobapps.show',$previous->id) }}" class="btn btn-outline-secondary btn-sm">← 前</a>@endif
    @if($next)<a href="{{ route('jobapps.show',$next->id) }}" class="btn btn-outline-secondary btn-sm ms-2">次 →</a>@endif
  </div>
  <form method="POST" action="{{ route('jobapps.destroy', $app) }}" onsubmit="return confirm('削除フラグを立てます。よろしいですか？')">
    @csrf @method('DELETE')
    <button class="btn btn-danger btn-sm">削除</button>
  </form>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">応募者情報</div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-4">送信日時</dt><dd class="col-8">{{ optional($app->sent_at)->format('Y-m-d H:i') ?? '-' }}</dd>
          <dt class="col-4">氏名</dt><dd class="col-8">{{ $app->name ?? '-' }}</dd>
          <dt class="col-4">地域/住所</dt><dd class="col-8">{{ $app->region ?? '-' }}</dd>
          <dt class="col-4">連絡先</dt><dd class="col-8">{{ $app->phone ?? '-' }}</dd>
          <dt class="col-4">メール</dt><dd class="col-8">{{ $app->email ?? '-' }}</dd>
          <dt class="col-4">年齢/性別</dt><dd class="col-8">{{ $app->age ?? '-' }} / {{ $app->gender ?? '-' }}</dd>
          <dt class="col-4">希望種別</dt><dd class="col-8">{{ $app->desired_type ?? '-' }}</dd>
          <dt class="col-4">勤務希望</dt><dd class="col-8">{{ $app->desired_area ?? '-' }}</dd>
        </dl>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header">参照元</div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-4">サイト</dt><dd class="col-8">{{ $app->site ?? '-' }}</dd>
          <dt class="col-4">ページURL</dt><dd class="col-8">
            @if($app->page_url)
              <a href="{{ $app->page_url }}" target="_blank" rel="noopener">{{ $app->page_url }}</a>
            @else - @endif
          </dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card">
      <div class="card-header">応募内容</div>
      <div class="card-body">
        <dl class="row">
          <dt class="col-4">応募理由</dt><dd class="col-8">{{ $app->reason ?? '-' }}</dd>
          <dt class="col-4">職歴/経験</dt><dd class="col-8">{{ $app->experience ?? '-' }}</dd>
          <dt class="col-4">資格</dt><dd class="col-8">{{ $app->qualifications ?? '-' }}</dd>
          <dt class="col-4">性格</dt><dd class="col-8">{{ $app->personality ?? '-' }}</dd>
        </dl>
        <hr>
        <label class="form-label"><strong>原文（送信内容）</strong></label>
        <pre class="small" style="white-space: pre-wrap;">{{ $app->body }}</pre>
      </div>
    </div>
  </div>
</div>

<hr class="my-4">

<h4>対応履歴</h4>

@if($app->responses->count())
  @foreach($app->responses as $r)
    <form id="update-form-{{ $r->id }}" action="{{ route('jobapps.response.update', $r->id) }}" method="POST" class="card mb-2">
      @csrf @method('PUT')
      <div class="card-body">
        <div class="row g-2">
          <div class="col-md-2">
            <label class="form-label"><strong>対応状況</strong></label>
            <select name="status" class="form-select">
              @foreach (['未対応','対応中','対応済み','対応保留'] as $opt)
                <option value="{{ $opt }}" {{ $r->status===$opt?'selected':'' }}>{{ $opt }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label"><strong>担当者</strong></label>
            <select name="staff_name" class="form-select">
              @foreach (['北野','岩下','松元','小泉','柴田','名古屋','平松','岸','簑和田','岡田','小林','長尾'] as $opt)
                <option value="{{ $opt }}" {{ $r->staff_name===$opt?'selected':'' }}>{{ $opt }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label"><strong>対応日</strong></label>
            <input type="datetime-local" name="handled_at" class="form-control"
              value="{{ optional($r->handled_at)->format('Y-m-d\TH:i') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label"><strong>方法</strong></label>
            <select name="method" class="form-select">
              @foreach (['アポ','メール','電話','追い電話','追いメール','LINE','その他'] as $opt)
                <option value="{{ $opt }}" {{ $r->method===$opt?'selected':'' }}>{{ $opt }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-12 mt-2">
            <label class="form-label"><strong>メモ</strong></label>
            <textarea name="memo" rows="{{ empty($r->memo)?1:3 }}" class="form-control">{{ $r->memo }}</textarea>
          </div>
        </div>
        <p class="mt-2">
          <a href="#" onclick="event.preventDefault(); document.getElementById('update-form-{{ $r->id }}').submit();" class="text-primary text-decoration-underline">更新</a>
          　
          <a href="#" onclick="if(confirm('本当にこの対応状況を削除しますか？')) { document.getElementById('delete-form-{{ $r->id }}').submit(); } return false;" class="text-danger text-decoration-underline">削除</a>
        </p>
      </div>
    </form>
    <form id="delete-form-{{ $r->id }}" action="{{ route('jobapps.response.destroy', $r->id) }}" method="POST" style="display:none;">
      @csrf @method('DELETE')
    </form>
  @endforeach
@else
  <p class="text-muted">対応情報はまだ登録されていません。</p>
@endif

<hr>

<h4>対応状況を登録</h4>
<form method="POST" action="{{ route('jobapps.response.store', $app->id) }}">
  @csrf
  <div class="mb-2">
    <label for="status" class="form-label">対応状況</label>
    <select name="status" id="status" class="form-select">
      <option value="">選択してください</option>
      @foreach (['未対応','対応中','対応済み','対応保留'] as $opt)
        <option value="{{ $opt }}">{{ $opt }}</option>
      @endforeach
    </select>
  </div>
  <div class="mb-2">
    <label for="staff_name" class="form-label">担当者</label>
    <select name="staff_name" id="staff_name" class="form-select">
      <option value="">選択してください</option>
      @foreach (['北野','岩下','松元','小泉','柴田','名古屋','平松','岸','簑和田','岡田','小林','長尾'] as $opt)
        <option value="{{ $opt }}">{{ $opt }}</option>
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
      @foreach (['アポ','メール','電話','追い電話','追いメール','LINE','その他'] as $opt)
        <option value="{{ $opt }}">{{ $opt }}</option>
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