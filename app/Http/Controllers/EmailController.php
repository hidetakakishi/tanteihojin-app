<?php

namespace App\Http\Controllers;

use App\Models\Email;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function index(Request $request)
    {
        $query = Email::query(); // ← 論理削除されていないものだけ

        // 並び順（デフォルト: 降順）
        $sort = $request->input('sort', 'desc');
        if (in_array($sort, ['asc', 'desc'])) {
            $query->orderBy('sent_at', $sort);
        }

        // 検索キーワード
        $keyword = $request->input('keyword');
        if ($keyword) {
            $words = preg_split('/\s+/', mb_convert_kana($keyword, 's'), -1, PREG_SPLIT_NO_EMPTY);

            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->where(function ($subQ) use ($word) {
                        $subQ->where('subject', 'like', "%{$word}%")
                            ->orWhere('body', 'like', "%{$word}%")
                            ->orWhereHas('latestResponse', function ($hasQ) use ($word) {
                                $hasQ->where('staff_name', 'like', "%{$word}%");
                            });
                    });
                }
            });
        }

        // 対応状況（status）での絞り込み
        $status = $request->input('status');
        if ($status) {
            $query->whereHas('latestResponse', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        // サイト名での絞り込み
        $site = $request->input('site');
        if ($site) {
            $query->where('site', $site);
        }

        $deleted = $request->input('deleted');
        if ($deleted === 'only') {
            $query->where('deleted_flag', true);
        } elseif ($deleted === 'with') {
            // 何も追加しない（すべて含む）
        } else {
            $query->where('deleted_flag', false); // デフォルト: 削除メールは除外
        }

        // データ取得
        $emails = $query->with('latestResponse')->paginate(100)->onEachSide(8)->appends([
            'sort' => $sort,
            'keyword' => $keyword,
            'status' => $status,
            'site' => $site,
            'deleted' => $deleted,
        ]);

        return view('emails.index', compact('emails', 'sort', 'keyword', 'status', 'site'));
    }

    public function show($id)
    {
        $email = Email::with('responses')->findOrFail($id);

        // 前のメール（送信日時が小さい＝古い）
        $previous = Email::where('sent_at', '<', $email->sent_at)
            ->orderBy('sent_at', 'desc')
            ->first();

        // 次のメール（送信日時が大きい＝新しい）
        $next = Email::where('sent_at', '>', $email->sent_at)
            ->orderBy('sent_at', 'asc')
            ->first();

        // 本文から電話番号・メールアドレスを抽出
        $body = $email->body;

        // 電話番号（ハイフンあり・なし対応）
        preg_match_all('/\b0\d{1,4}[-]?\d{1,4}[-]?\d{3,4}\b/u', $body, $phoneMatches);
        $phoneNumbers = array_unique($phoneMatches[0]);
        $phoneCount = count($phoneNumbers);

        // メールアドレス
        preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $body, $emailMatches);
        $emailAddresses = array_unique($emailMatches[0]);
        $emailCount = count($emailAddresses);

        // 警告表示フラグ
        $showWarning = ($phoneCount > 1 || $emailCount > 1);

        return view('emails.show', compact(
            'email', 'previous', 'next',
            'showWarning', 'phoneCount', 'emailCount',
            'phoneNumbers', 'emailAddresses'
        ));
    }

    public function destroy(Email $email)
    {
        $email->deleted_flag = true;
        $email->save();

        return redirect()->route('emails.index')->with('success', 'メールを削除しました。');
    }
}