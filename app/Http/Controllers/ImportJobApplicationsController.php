<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\JobApplication;
use App\Models\JobApplicationResponse;

class ImportJobApplicationsController extends Controller
{
    public function import(Request $request)
    {
        // フォルダ例：storage/app/recruits（emails と併用も可）
        $folder = storage_path('app/recruits');
        if (!is_dir($folder)) {
            $folder = storage_path('app/jobapps'); // 併用：メールと同じ置き場でもOK
        }
        $files = glob($folder.'/*.txt');

        $imported = 0;
        $isAll = $request->has('all');

        try {
            DB::beginTransaction();

            foreach ($files as $path) {
                $content = file_get_contents($path);
                $blocks = $this->splitBlocks($content);

                foreach ($blocks as $block) {
                    $cleanBody = $this->extractBodyContent($block);
                    $sentAt    = $this->extractSentAtFromFooter($block) ?? $this->extractSentAtFromAlternativeFormat($block);

                    if (!$isAll && (!$sentAt || $sentAt->lt(Carbon::create(2025, 7, 10, 18, 28, 0)))) {
                        continue; // 既存と同じスキップ条件
                    }

                    // --- 項目抽出（代表的なキーを網羅的に吸収） ---
                    $kv = $this->parseKeyValues($cleanBody);

                    $exists = JobApplication::where('email', $kv['email'] ?? null)
                        ->where('body', $cleanBody)
                        ->where('sent_at', $sentAt)
                        ->exists();

                    if (!$exists) {
                        $app = JobApplication::create([
                            'name'          => $kv['name']          ?? null,
                            'region'        => $kv['region']        ?? ($kv['address'] ?? ($kv['desired_area'] ?? null)),
                            'phone'         => $kv['phone']         ?? null,
                            'email'         => $kv['email']         ?? null,
                            'age'           => $kv['age']           ?? null,
                            'gender'        => $kv['gender']        ?? null,
                            'desired_type'  => $kv['desired_type']  ?? null,
                            'desired_area'  => $kv['desired_area']  ?? null,
                            'site'          => $kv['site']          ?? null,
                            'page_url'      => $kv['page_url']      ?? null,
                            'reason'        => $kv['reason']        ?? null,
                            'experience'    => $kv['experience']    ?? null,
                            'qualifications'=> $kv['qualifications']?? null,
                            'personality'   => $kv['personality']   ?? null,
                            'body'          => $cleanBody,
                            'sent_at'       => $sentAt,
                        ]);
                        $imported++;

                        // @担当者 が本文にあれば「対応中」で自動生成（メール取込の踏襲）
                        // if (preg_match('/@(.+?)\s/u', $block, $m)) {
                        //     $staffName = trim($m[1]);
                        //     JobApplicationResponse::create([
                        //         'job_application_id' => $app->id,
                        //         'staff_name' => $staffName,
                        //         'status' => '対応中',
                        //         'handled_at' => null,
                        //         'method' => null,
                        //         'memo' => null,
                        //     ]);
                        // }
                    }
                }

                // unlink($path); // 必要なら
            }

            DB::commit();
            return redirect()->route('jobapps.index')->with('success', "{$imported} 件の求人反響を取り込みました");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('jobapps.index')->with('error', "エラーのため取り込みを中止しました: ".$e->getMessage());
        }
    }

    // ====== 解析ユーティリティ（既存メール取込を踏襲） ======

    private function splitBlocks(string $text): array
    {
        // 改行・不可視文字の正規化
        $text = preg_replace('/\x{FEFF}/u', '', $text); // BOM除去
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // ケース1: フォーマット「12:28/嶋田陽一」で始まるブロック
        // 先読みで開始位置を保持しつつ分割
        $blocks = preg_split('/(?=^\d{1,2}:\d{2}\s*\/\s*嶋田陽一\s*$)/m', $text, -1, PREG_SPLIT_NO_EMPTY);

        // フォールバック（稀に時刻行が欠落しても「▼送信内容」が基準で切れるように）
        if (count($blocks) <= 1) {
            $blocks = preg_split('/(?=^.*▼送信内容\s*$)/m', $text, -1, PREG_SPLIT_NO_EMPTY);
        }

        // 先頭や日付見出しのみの断片を除去
        $out = [];
        foreach ($blocks as $b) {
            $trim = trim($b);
            // 単独の日付行だけの断片を除外
            if (preg_match('/^\d{4}年\d{1,2}月\d{1,2}日/u', $trim) && !str_contains($trim, '▼送信内容')) {
                continue;
            }
            // 実体のない断片を除外
            if ($trim === '') continue;

            // 連続する日付見出しをまたいだ不要前置きを削る（先頭～最初の「▼送信内容」までを捨てる）
            if (preg_match('/(▼送信内容[\s\S]*)/u', $trim, $m)) {
                $trim = $m[1];
            }
            $out[] = $trim;
        }
        return $out;
    }

    private function extractBodyContent(string $rawBody): string
    {
        $rawBody = str_replace("�", '', $rawBody);
        $lines = preg_split("/\r\n|\r|\n/", $rawBody);

        $start = null; $end = null;
        foreach ($lines as $i => $line) {
            if ($start === null && mb_strpos($line, '▼送信内容') !== false) $start = $i + 1;
            if ($start !== null && (mb_strpos($line, '送信日時') !== false)) { $end = $i - 1; break; }
        }
        if ($start !== null && $end !== null && $start <= $end) {
            $slice = array_slice($lines, $start, $end - $start + 1);
            return trim(implode("\n", array_map('trim', $slice)));
        }
        return trim($rawBody);
    }

    private function extractSentAtFromFooter(string $rawBody): ?Carbon
    {
        $lines = preg_split("/\r\n|\r|\n/", $rawBody);
        foreach ($lines as $line) {
            // 例: 2025/07/08(Tue) 20:59:01
            if (preg_match('/送信日時\s*[:：]\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\([^)]+\)\s+(\d{1,2}):(\d{1,2}):(\d{1,2})/', $line, $m)) {
                try { return Carbon::createFromFormat('Y/n/j G:i:s', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}"); } catch (\Exception $e) {}
            }
            // 例: 2025/07/08(Tue) 20:59
            if (preg_match('/送信日時\s*[:：]\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\([^)]+\)\s+(\d{1,2}):(\d{1,2})/', $line, $m)) {
                try { return Carbon::createFromFormat('Y/n/j G:i', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}"); } catch (\Exception $e) {}
            }
        }
        return null;
    }

    private function extractSentAtFromAlternativeFormat(string $rawBody): ?Carbon
    {
        if (preg_match('/【送信日時】\s*(\d{4})\/(\d{2})\/(\d{2})\([^)]+\)\s+(\d{2}):(\d{2}):(\d{2})/u', $rawBody, $m)) {
            try { return Carbon::createFromFormat('Y/m/d H:i:s', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}"); } catch (\Exception $e) {}
        }
        return null;
    }

    private function parseKeyValues(string $body): array
    {
        // キーの表記ブレを広くカバー
        $map = [
            'site'          => ['サイト'],
            'page_url'      => ['ページURL'],
            'name'          => ['お名前'],
            'address'       => ['住所'],
            'region'        => ['お住まい地域','勤務希望地域','勤務希望地','地域'],
            'phone'         => ['ご連絡先','電話番号'],
            'email'         => ['メールアドレス'],
            'age'           => ['年齢'],
            'gender'        => ['性別'],
            'desired_type'  => ['希望種別','希望項目','希望業種'],
            'desired_area'  => ['勤務希望地域','勤務希望地'],
            'experience'    => ['職歴','経験'],
            'qualifications'=> ['有資格・免許','資格・経験'],
            'personality'   => ['自分の性格'],
            'reason'        => ['探偵業を選ぶ理由','志望動機','応募理由'],
        ];

        $out = [];
        foreach (explode("\n", $body) as $line) {
            $line = trim($line);
            foreach ($map as $key => $labels) {
                foreach ($labels as $label) {
                    // 「ラベル = 値」形式
                    if (preg_match('/^'.preg_quote($label, '/').'\s*=\s*(.*)$/u', $line, $m)) {
                        $val = trim($m[1]);
                        if (!isset($out[$key])) $out[$key] = $val;
                    }
                }
            }
        }
        return $out;
    }
}