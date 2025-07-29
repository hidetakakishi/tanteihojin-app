<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PhoneCall;
use App\Models\PhoneCallResponse;
use Carbon\Carbon;

class PhoneCallController extends Controller
{
    // 一覧表示
    public function index(Request $request)
    {
        $query = PhoneCall::with('latestResponse');

        $query->orderBy('call_date', 'desc')
            ->orderBy('call_time', 'desc');

        if ($keyword = $request->input('keyword')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('customer_name', 'like', "%$keyword%")
                ->orWhere('customer_phone', 'like', "%$keyword%")
                ->orWhere('request_type', 'like', "%$keyword%")
                ->orWhere('request_detail', 'like', "%$keyword%")
                ->orWhere('staff_name', 'like', "%$keyword%")
                ->orWhere('region', 'like', "%$keyword%");
            });
        }

        if ($status = $request->input('status')) {
            $query->whereHas('latestResponse', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        // サイト名の絞り込み
        if ($site = $request->input('site')) {
            $query->where('site', $site);
        }

        // ★ 修正点：appends() を追加
        $calls = $query->orderByDesc('call_date')
                    ->paginate(50)
                    ->onEachSide(8)
                    ->appends($request->query());

        // サイト一覧（プルダウン用）
        $sites = PhoneCall::select('site')->distinct()->pluck('site');

        return view('phone_calls.index', compact('calls', 'sites'));
    }

    // 詳細表示
    public function show($id)
    {
        $call = PhoneCall::with('responses')->findOrFail($id);

        $callDateTime = \Carbon\Carbon::parse($call->call_date . ' ' . $call->call_time);

        $previous = PhoneCall::whereRaw("STR_TO_DATE(CONCAT(call_date, ' ', call_time), '%Y-%m-%d %H:%i:%s') < ?", [$callDateTime])
            ->orderByRaw("STR_TO_DATE(CONCAT(call_date, ' ', call_time), '%Y-%m-%d %H:%i:%s') DESC")
            ->first();

        $next = PhoneCall::whereRaw("STR_TO_DATE(CONCAT(call_date, ' ', call_time), '%Y-%m-%d %H:%i:%s') > ?", [$callDateTime])
            ->orderByRaw("STR_TO_DATE(CONCAT(call_date, ' ', call_time), '%Y-%m-%d %H:%i:%s') ASC")
            ->first();

        return view('phone_calls.show', compact('call', 'previous', 'next'));
    }

    // 対応状況更新
    public function updateResponse(Request $request, $id)
    {
        $response = PhoneCallResponse::findOrFail($id);
        $response->update($request->only(['handled_at', 'staff_name', 'status', 'method', 'memo']));

        return back()->with('success', '対応情報を更新しました');
    }

    // 対応状況削除
    public function destroyResponse($id)
    {
        PhoneCallResponse::findOrFail($id)->delete();
        return back()->with('success', '対応履歴を削除しました');
    }

    // 対応状況登録
    public function storeResponse(Request $request, PhoneCall $call)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'staff_name' => 'required|string',
            'handled_at' => 'nullable|date',
            'method' => 'nullable|string',
            'memo' => 'nullable|string',
        ]);

        $call->responses()->create($validated);

        return back()->with('success', '対応状況を登録しました');
    }

    // 電話履歴インポート処理
    public function import(Request $request)
    {
        $folder = storage_path('app/calls');
        $files = glob($folder . '/*.txt');
        $imported = 0;

        try {
            DB::beginTransaction();

            foreach ($files as $filePath) {
                $fileName = basename($filePath);
                preg_match('/\((.*?)\)/', $fileName, $match);
                $site = $match[1] ?? null;

                $lines = file($filePath, FILE_IGNORE_NEW_LINES);
                $currentYear = null;

                foreach ($lines as $i => $line) {
                    if (preg_match('/^(\d{4})年(\d{1,2})月(\d{1,2})日$/u', $line, $dateMatch)) {
                        $currentYear = $dateMatch[1];
                        continue;
                    }

                    if (preg_match('/^(\d{1,2}:\d{2})\s+(.+)$/u', $line, $match)) {
                        $time = $match[1];
                        $staff = $match[2];
                        $info = array_slice($lines, $i + 1, 10);

                        if (!isset($info[0], $info[1]) || !preg_match('/^1、/', $info[0]) || !preg_match('/^2、/', $info[1])) {
                            continue;
                        }

                        if (!preg_match('/1、(\d{1,2})\/(\d{1,2})/', $info[0], $md)) {
                            continue;
                        }

                        if (!$currentYear) continue;

                        $datetimeStr = sprintf('%04d-%02d-%02d %s', $currentYear, $md[1], $md[2], $time);
                        try {
                            $callDate = Carbon::parse($datetimeStr);
                        } catch (\Exception $e) {
                            continue;
                        }

                        $isNewFormat = $callDate->greaterThanOrEqualTo('2025-07-18');
                        $info = array_pad($info, 10, '');

                        $region         = trim(Str::after($info[1], '、'));
                        $customerName   = trim(Str::after($info[2], '、'));
                        $customerPhone  = trim(Str::after($info[3], '、'));
                        $gender         = trim(Str::after($info[4], '、'));

                        if ($isNewFormat) {
                            $requestType    = null;
                            $requestDetail  = trim(Str::after($info[5], '、'));
                            $staffResponse  = trim(Str::after($info[6], '、'));
                            $customerReply  = trim(Str::after($info[7], '、'));
                        } else {
                            $requestType    = trim(Str::after($info[5], '、'));
                            $requestDetail  = trim(Str::after($info[6], '、'));
                            $staffResponse  = trim(Str::after($info[7], '、'));
                            $customerReply  = trim(Str::after($info[8], '、'));
                        }

                        $method = (str_contains($customerReply, 'アポ') || str_contains($customerReply, 'AP')) ? 'アポ' : null;

                        $exists = PhoneCall::where('call_date', $callDate)
                            ->where('call_time', $callDate->format('H:i'))
                            ->where('staff_name', $staff)
                            ->where('region', $region)
                            ->where('customer_name', $customerName)
                            ->where('customer_phone', $customerPhone)
                            ->where('gender', $gender)
                            ->where('request_type', $requestType)
                            ->where('request_detail', $requestDetail)
                            ->where('staff_response', $staffResponse)
                            ->where('customer_reply', $customerReply)
                            ->where('site', $site)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        $call = PhoneCall::create([
                            'call_date' => $callDate,
                            'call_time' => $callDate->format('H:i'),
                            'staff_name' => $staff,
                            'region' => $region,
                            'customer_name' => $customerName,
                            'customer_phone' => $customerPhone,
                            'gender' => $gender,
                            'request_type' => $requestType,
                            'request_detail' => $requestDetail,
                            'staff_response' => $staffResponse,
                            'customer_reply' => $customerReply,
                            'site' => $site,
                        ]);

                        PhoneCallResponse::create([
                            'phone_call_id' => $call->id,
                            'staff_name' => $staff,
                            'status' => '対応済み',
                            'method' => $method,
                            'handled_at' => $callDate,
                            'memo' => '',
                        ]);

                        $imported++;
                    }
                }
            }

            DB::commit();
            return redirect()->route('calls.index')->with('success', "$imported 件の電話履歴を取り込みました");

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('calls.index')->with('error', 'エラーが発生したため取り込みを中止しました（困ったら岸まで）：' . $e->getMessage());
        }
    }
}
