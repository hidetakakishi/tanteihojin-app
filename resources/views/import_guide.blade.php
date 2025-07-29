@extends('layouts.app')

@section('title', '📥 メール・電話取り込み手順')

@section('content')
    <h2 class="mb-4">📥 メール・電話取り込み手順</h2>

    <div class="mb-4">
        <h4>✅ 1. LINE WORKS チャット履歴のダウンロード</h4>
        <ol>
            <li>LINE WORKS アプリ版（PC）を開く</li>
            <li>ダウンロードしたいチャットルームを開く</li>
            <li>右上の「︙（点々）」メニューをクリック</li>
            <li>「トークを保存」を選択</li>
            <li>ファイルが保存されます（※画面に表示されている分のみ）</li>
        </ol>
    </div>

    <div class="mb-4">
        <h4>📁 ファイルの設置先</h4>

        <h5>📧 メール反響ファイル</h5>
        <ul>
            <li>設置場所：<code>C:\Users\i7-7700k\tanteihojin-app\storage\app\emails</code></li>
            <li>形式：ダウンロードしたファイルそのままでおｋ</li>
            <li>備考：前に取り込んだファイルがある場合は<code>backup</code>フォルダへ移動</li>
        </ul>

        <h5>📞 電話反響ファイル</h5>
        <ul>
            <li>設置場所：<code>C:\Users\i7-7700k\tanteihojin-app\storage\app\calls</code></li>
            <li>形式：ダウンロードしたファイルそのままでおｋ</li>
            <li>備考：前に取り込んだファイルがある場合は<code>backup</code>フォルダへ移動</li>
        </ul>
    </div>

    <div class="mb-4">
        <h4>🧩 2. 取り込み手順</h4>
        <ol>
            <li>メニューから「📧 メール反響一覧」または「📞 電話反響一覧」を開く</li>
            <li>画面上部の「メール取り込み」または「電話取り込み」をクリック</li>
            <li>新しいデータが一覧に反映されます</li>
        </ol>
    </div>

    <div class="mb-4">
        <h4>🚨 注意書き</h4>
        <ul>
            <li>上記ファイル設置は<strong>岸のパソコン</strong>で行う（ダウンロードもできます）</li>
            <li>同一内容は自動スキップされるので過去のデータが読み込まれても大丈夫です（なので最新データの日付を確認して以降のデータを大雑把にダウンロードすればおｋ）</li>
            <li>エラー発生時は取り込みが<strong>中断</strong>されます（困ったら岸まで）</li>
        </ul>
    </div>

    <div class="mb-4">
        <small>メモ：LINE WORKSから自動取り込みしたかったけどセキュリティ的な問題で一旦手動取り込みにしたよ～</small>
    </div>

    <a href="{{ route('calls.index') }}" class="btn btn-secondary">📞 電話一覧へ戻る</a>
    <a href="{{ route('emails.index') }}" class="btn btn-secondary">📧 メール一覧へ戻る</a>
@endsection