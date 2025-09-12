<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobApplication;
use App\Models\JobApplicationResponse;

class JobApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = JobApplication::query();

        // 並び順（送信日時 desc/asc）
        $sort = $request->input('sort', 'desc');
        if (in_array($sort, ['asc','desc'])) {
            $query->orderBy('sent_at', $sort);
        }

        // キーワード（名前/地域/本文/担当者 等を横断）
        $keyword = $request->input('keyword');
        if ($keyword) {
            $words = preg_split('/\s+/', mb_convert_kana($keyword, 's'), -1, PREG_SPLIT_NO_EMPTY);
            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->where(function ($sub) use ($word) {
                        $sub->where('name', 'like', "%{$word}%")
                            ->orWhere('region', 'like', "%{$word}%")
                            ->orWhere('email', 'like', "%{$word}%")
                            ->orWhere('phone', 'like', "%{$word}%")
                            ->orWhere('desired_type', 'like', "%{$word}%")
                            ->orWhere('desired_area', 'like', "%{$word}%")
                            ->orWhere('site', 'like', "%{$word}%")
                            ->orWhere('body', 'like', "%{$word}%")
                            ->orWhereHas('latestResponse', function ($r) use ($word) {
                                $r->where('staff_name', 'like', "%{$word}%")
                                  ->orWhere('status', 'like', "%{$word}%")
                                  ->orWhere('method', 'like', "%{$word}%")
                                  ->orWhere('memo', 'like', "%{$word}%");
                            });
                    });
                }
            });
        }

        // 対応状況
        $status = $request->input('status');
        if ($status) {
            $query->whereHas('latestResponse', fn($r) => $r->where('status', $status));
        }

        // サイト名
        $site = $request->input('site');
        if ($site) {
            $query->where('site', $site);
        }

        // 削除フラグ（デフォルト除外）
        $deleted = $request->input('deleted');
        if ($deleted === 'only') {
            $query->where('deleted_flag', true);
        } elseif ($deleted === 'with') {
            // そのまま
        } else {
            $query->where('deleted_flag', false);
        }

        $applications = $query
            ->with('latestResponse')
            ->paginate(100)->onEachSide(8)
            ->appends(compact('sort','keyword','status','site','deleted'));

        return view('jobapps.index', compact('applications','sort','keyword','status','site'));
    }

    public function show($id)
    {
        $app = JobApplication::with('responses')->findOrFail($id);

        $previous = JobApplication::where('sent_at', '<', $app->sent_at)->orderBy('sent_at', 'desc')->first();
        $next     = JobApplication::where('sent_at', '>', $app->sent_at)->orderBy('sent_at', 'asc')->first();

        // 参考：メール詳細では本文から電話/メール抽出→警告表示の仕組みあり
        // 今回は値をそのまま見せる（必要なら同様の抽出＋警告実装も可）

        return view('jobapps.show', compact('app','previous','next'));
    }

    // --- 対応登録 ---
    public function storeResponse(Request $request, $id)
    {
        $app = JobApplication::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|string',
            'staff_name' => 'required|string',
            'handled_at' => 'nullable|date',
            'method' => 'nullable|string',
            'memo' => 'nullable|string',
        ]);

        $data['job_application_id'] = $app->id;
        JobApplicationResponse::create($data);

        return redirect()->route('jobapps.show', $app->id)->with('success', '対応状況を登録しました。');
    }

    public function updateResponse(Request $request, $responseId)
    {
        $response = JobApplicationResponse::findOrFail($responseId);

        $data = $request->validate([
            'status' => 'required|string',
            'staff_name' => 'required|string',
            'handled_at' => 'nullable|date',
            'method' => 'nullable|string',
            'memo' => 'nullable|string',
        ]);

        $response->update($data);
        return back()->with('success', '対応状況を更新しました。');
    }

    public function destroyResponse($responseId)
    {
        $response = JobApplicationResponse::findOrFail($responseId);
        $appId = $response->job_application_id;
        $response->delete();

        return redirect()->route('jobapps.show', $appId)->with('success', '対応状況を削除しました。');
    }

    // 論理削除
    public function destroy(JobApplication $jobapp)
    {
        $jobapp->deleted_flag = true;
        $jobapp->save();
        return redirect()->route('jobapps.index')->with('success', '求人反響を削除しました。');
    }
}
