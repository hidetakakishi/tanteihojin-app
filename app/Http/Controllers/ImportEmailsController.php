<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Email;
use Carbon\Carbon;
use App\Models\EmailResponse;

class ImportEmailsController extends Controller
{
 
    public function import(Request $request)
    {
        $folder = storage_path('app/emails');
        $files = glob($folder . '/*.txt');
        $imported = 0;

        $isAll = $request->has('all'); // オプション指定

        try {
            DB::beginTransaction();

            foreach ($files as $filePath) {
                $content = file_get_contents($filePath);
                $blocks = $this->splitEmailBlocks($content);
                $filename = basename($filePath);

                // ファイル名から site を推定
                $siteFromFilename = match (true) {
                    Str::contains($filename, '調査士会') => '探偵法人',
                    Str::contains($filename, '社団法人') => '社団法人',
                    Str::contains($filename, 'PRC')       => 'PRC',
                    default                               => null,
                };

                foreach ($blocks as $block) {
                    $cleanBody = $this->extractBodyContent($block);
                    $sentAt = $this->extractSentAtFromFooter($block)
                        ?? $this->extractSentAtFromAlternativeFormat($block);

                    // オプション all がない場合は条件付きスキップ
                    if (!$isAll && (!$sentAt || $sentAt->lt(Carbon::create(2025, 7, 10, 18, 28, 0)))) {
                        continue;
                    }

                    // preg_match('/お名前\s*=\s*(.+)/u', $cleanBody, $fromMatch);
                    preg_match('/メールアドレス\s*=\s*(.+)/u', $cleanBody, $fromMatch);

                    $from = $fromMatch[1] ?? '（差出人不明）';
                    $to = '';

                    $site = $siteFromFilename;

                    $exists = Email::where('from', $from)
                        ->where('body', $cleanBody)
                        ->where('sent_at', $sentAt)
                        ->exists();

                    if (!$exists) {
                        $email = Email::create([
                            'from' => $from,
                            'to' => '',
                            'subject' => '',
                            'body' => $cleanBody,
                            'sent_at' => $sentAt, // allの場合null対策
                            'site' => $site,
                        ]);
                        $imported++;

                        // ★ @〇〇〇 から staff_name を抽出して phone_call_responses に登録
                        if (preg_match('/@(.+?)\s/u', $block, $staffMatch)) {
                            $staffName = trim($staffMatch[1]);

                            EmailResponse::create([
                                'email_id' => $email->id,
                                'staff_name' => $staffName,
                                'status' => '対応中',
                                'handled_at' => null,
                                'method' => null,
                                'memo' => null,
                            ]);
                        }
                    }
                }

                // unlink($filePath); // 処理済みファイル削除する場合は有効化
            }

            DB::commit();
            return redirect()->route('emails.index')->with('success', "{$imported} 件のメールを取り込みました");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('emails.index')->with('error', "エラーが発生したため取り込みを中止しました（困ったら岸まで）: " . $e->getMessage());
        }
    }

    function extractBodyContent(string $rawBody): string
    {
        // 文字化け「�」を削除
        $rawBody = str_replace("�", '', $rawBody);
        
        // 改行統一＆配列化
        $lines = preg_split("/\r\n|\r|\n/", $rawBody);

        $startIndex = null;
        $endIndex = null;

        foreach ($lines as $index => $line) {
            if ($startIndex === null && Str::contains($line, '送信内容')) {
                $startIndex = $index + 1;
            }

            $endIndex = $index - 1;
            if ($startIndex !== null && Str::contains($line, '送信日時')) {
                $endIndex = $index - 1;
                break;
            }
        }

        if (!is_null($startIndex) && !is_null($endIndex) && $startIndex <= $endIndex) {
            $bodyLines = array_slice($lines, $startIndex, $endIndex - $startIndex + 1);
            return implode("\n", array_map('trim', $bodyLines));
        }

        return '';
    }

    function extractSentAtFromFooter(string $rawBody): ?Carbon
    {
        $lines = preg_split("/\r\n|\r|\n/", $rawBody);

        foreach ($lines as $line) {
            // パターン1: 秒あり（例: 2025/07/08(Tue) 20:59:01）
            if (preg_match('/送信日時\s*[:：]\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\([^)]+\)\s+(\d{1,2}):(\d{1,2}):(\d{1,2})/', $line, $m)) {
                try {
                    return Carbon::createFromFormat('Y/n/j G:i:s', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}");
                } catch (\Exception $e) {
                    continue;
                }
            }

            // ✅ パターン2: 秒なし（例: 2025/07/08(Tue) 20:59）
            if (preg_match('/送信日時\s*[:：]\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\([^)]+\)\s+(\d{1,2}):(\d{1,2})/', $line, $m)) {
                try {
                    return Carbon::createFromFormat('Y/n/j G:i', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}");
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }


    // function extractSentAtFromHeader(string $rawBody): ?Carbon
    // {
    //     $lines = preg_split("/\r\n|\r|\n/", $rawBody);

    //     foreach ($lines as $line) {
    //         // ヘッダー形式（例: 2025/06/25 7:34 または 07:34）
    //         if (preg_match('/送信日時[:：]?\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\s+(\d{1,2}):(\d{1,2})/', $line, $m)) {
    //             try {
    //                 return Carbon::createFromFormat('Y/n/j G:i', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}");
    //             } catch (\Exception $e) {
    //                 continue; // 該当形式ではない場合スキップ
    //             }
    //         }

    //         // フッター形式（例: 2025/06/25(Wed) 11:47:36）
    //         if (preg_match('/送信日時\s*[:：]\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\([^)]+\)\s+(\d{1,2}):(\d{1,2}):(\d{1,2})/', $line, $m)) {
    //             try {
    //                 return Carbon::createFromFormat('Y/n/j G:i:s', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}");
    //             } catch (\Exception $e) {
    //                 continue;
    //             }
    //         }
    //     }

    //     return null;
    // }

    private function extractSentAtFromAlternativeFormat(string $rawBody): ?Carbon
    {
        // 【送信日時】2025/07/08(Tue) 12:24:51 の形式に対応
        if (preg_match('/【送信日時】\s*(\d{4})\/(\d{2})\/(\d{2})\([^)]+\)\s+(\d{2}):(\d{2}):(\d{2})/u', $rawBody, $m)) {
            try {
                return Carbon::createFromFormat('Y/m/d H:i:s', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}");
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    // private function extractSiteName(string $to, string $body): string
    // {
    //     // 宛先から判定
    //     if (trim($to) === 'soudan1@tanteik.jp') {
    //         return '探偵興信所一般社団法人';
    //     }

    //     if (trim($to) === 'form@privateriskconsulting.jp') {
    //         return 'PRC';
    //     }

    //     if (preg_match('/サイト\s*=\s*(.+)/u', $body, $matches)) {
    //         $site = trim($matches[1]);
    //         // return $site !== '' ? $site : '未分類';
    //         if($site === '探偵事務所' || $site === '調査士会')
    //         {
    //             $site = '探偵法人調査士会';
    //         }
    //         return $site !== '' ? $site : '探偵法人調査士会';
    //     }

    //     if (trim($to) === 'soudan1@tanteihojin.jp') {
    //         return '探偵法人調査士会';
    //     }

    //     return '未分類';
    // }

    private function splitEmailBlocks(string $text): array
    {
        // 改行統一
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 「時間 + 嶋田陽一」が行頭にある部分で分割
        $pattern = '/(?=^\d{1,2}:\d{2}\s+嶋田陽一)/m';
        $rawParts = preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY);

        $blocks = [];

        foreach ($rawParts as $part) {
            $trimmed = trim($part);

            // 「完全に日付だけ」の行（例: 2025年7月6日）だけならスキップ
            if (preg_match('/^\d{4}年\d{1,2}月\d{1,2}日$/u', $trimmed)) {
                continue;
            }

            $blocks[] = $trimmed;
        }

        return $blocks;
    }
}