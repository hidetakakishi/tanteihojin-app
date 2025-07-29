<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\PhoneCall;
use Carbon\Carbon;

class ImportLineworksChats extends Command
{
    protected $signature = 'lineworks:import-chats';
    protected $description = 'LINE WORKSチャットから電話履歴をインポート';

    public function handle()
    {
        $token = $this->getLineworksAccessToken();

        $response = Http::withToken($token)->get('https://www.worksapis.com/v1.0/bots/{botId}/messages');

        if ($response->failed()) {
            $this->error('❌ API取得に失敗しました');
            return;
        }

        $messages = $response->json()['messages'];

        // foreach ($messages as $message) {
        //     // 例: メッセージ本文を解析して各フィールドを抽出（パース処理）
        //     $text = $message['text'];

        //     // データパース（仮の例：カンマ区切りや改行で分割）
        //     // 実際は構造に応じて正規表現などを用いてください
        //     $lines = explode("\n", $text);
        //     $data = [
        //         'call_date'       => Carbon::parse($message['createdTime'])->toDateString(),
        //         'call_time'       => Carbon::parse($message['createdTime'])->toTimeString(),
        //         'staff_name'      => $this->parseLine($lines, '担当者'),
        //         'region'          => $this->parseLine($lines, '地域'),
        //         'customer_name'   => $this->parseLine($lines, '名前'),
        //         'customer_phone'  => $this->parseLine($lines, '電話'),
        //         'gender'          => $this->parseLine($lines, '性別'),
        //         'request_type'    => $this->parseLine($lines, '依頼種別'),
        //         'request_detail'  => $this->parseLine($lines, '依頼内容'),
        //         'staff_response'  => $this->parseLine($lines, '対応'),
        //         'customer_reply'  => $this->parseLine($lines, '返答'),
        //     ];

        //     PhoneCall::create($data);
        // }

        $this->info('✅ インポート完了');
    }

    private function parseLine(array $lines, string $keyword): ?string
    {
        foreach ($lines as $line) {
            if (str_contains($line, $keyword)) {
                return trim(str_replace($keyword . '：', '', $line));
            }
        }
        return null;
    }

    function getLineworksAccessToken(): ?string
    {
        $response = Http::asForm()->post('https://auth.worksmobile.com/oauth2/v2.0/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.lineworks.client_id'),
            'client_secret' => config('services.lineworks.client_secret'),
            'scope' => 'bot', // 必要に応じて変更
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        logger()->error('LINE WORKS token取得失敗', ['response' => $response->body()]);
        return null;
    }
}