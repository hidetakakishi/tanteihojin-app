<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Firebase\JWT\JWT; // JWTライブラリ
use Carbon\Carbon; // 日時操作

class LineWorksBotController extends Controller
{
    /**
     * LINE WORKS BotからのWebhookを処理します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleWebhook(Request $request)
    {
        // ログに出力してリクエストの内容を確認
        Log::info('LINE WORKS Webhook received:', $request->all());

        // LINE WORKSからのリクエストであることを検証（署名検証）
        // 本番環境では必須のセキュリティ対策です。
        // リクエストヘッダーの 'X-WorksMobile-Signature' を使用して、
        // あなたの秘密鍵とリクエストボディからHMAC-SHA256ハッシュを計算し、
        // ヘッダーの値と一致するかどうかを検証する必要があります。
        // 詳細はLINE WORKS Developersのドキュメントを参照してください。
        // 参考: https://developers.worksmobile.com/jp/docs/bot-callback-signature

        $event = $request->json()->all();

        // イベントタイプが 'message' であることを確認
        if (isset($event['type']) && $event['type'] === 'message') {
            $sourceType = data_get($event, 'source.type');
            $groupId = data_get($event, 'source.groupId');   // グループID
            $userId = data_get($event, 'source.userId');     // 送信ユーザーID

            $contentType = data_get($event, 'content.type');
            $messageText = data_get($event, 'content.text'); // テキストメッセージの場合

            if ($sourceType === 'group') {
                Log::info("Message from group '{$groupId}' by user '{$userId}':");
                Log::info("  Type: {$contentType}, Text: {$messageText}");

                // ここにメッセージに対する処理を記述します。
                // 例: テキストメッセージの場合
                if ($contentType === 'text') {
                    if (str_contains($messageText, 'こんにちは')) {
                        Log::info("'こんにちは' を検知しました。");
                        // 例: 応答メッセージを送信する場合
                        // $this->sendMessage($groupId, "こんにちは！グループメッセージありがとうございます！");
                    } elseif (str_contains($messageText, '今日の天気')) {
                        Log::info("'今日の天気' を検知しました。");
                        // 例: 外部APIを呼び出して天気情報を取得し、応答メッセージを送信
                        // $this->sendMessage($groupId, "今日の東京の天気は晴れです！");
                    } else {
                        Log::info("受信メッセージ: {$messageText}");
                    }
                } elseif ($contentType === 'image') {
                    $fileId = data_get($event, 'content.fileId');
                    Log::info("画像メッセージを受信しました。ファイルID: {$fileId}");
                    // 画像コンテンツの取得APIを呼び出すなどの処理
                }
                // 他のメッセージタイプ (sticker, file, location, video, audioなど) に応じた処理を追加
            } else {
                Log::warning("Unsupported source type: {$sourceType}");
            }
        } else {
            Log::warning("Unsupported event type: " . data_get($event, 'type'));
        }

        // LINE WORKS Bot APIは、Webhookの応答に特定の形式を要求しないため、
        // 200 OK を返すだけで問題ありません。
        return Response::make('OK', 200);
    }

    /**
     * Botからメッセージを送信するためのヘルパー関数
     * 実際にはLINE WORKS Bot APIの呼び出しが必要です。
     *
     * @param string $targetId 送信先ID（ユーザーIDまたはグループID）
     * @param string $messageContent 送信するメッセージの内容
     * @return bool
     */
    private function sendMessage(string $targetId, string $messageContent): bool
    {
        $consumerKey = env('LINEWORKS_CONSUMER_KEY');
        $privateKeyPath = env('LINEWORKS_PRIVATE_KEY_PATH');
        $botId = env('LINEWORKS_BOT_ID');

        if (!$consumerKey || !file_exists($privateKeyPath) || !$botId) {
            Log::error('LINE WORKS API Credentials are not set correctly.');
            return false;
        }

        try {
            $privateKey = file_get_contents($privateKeyPath);

            // JWTトークンの生成
            $now = Carbon::now();
            $payload = [
                'iss' => $consumerKey,
                'iat' => $now->timestamp,
                'exp' => $now->addMinutes(10)->timestamp, // 10分間有効
            ];
            $jwt = JWT::encode($payload, $privateKey, 'RS256');

            // Bot APIの呼び出し
            $apiUrl = "https://www.worksapis.com/v1.0/bot/message/send";
            $headers = [
                'Authorization' => "Bearer {$jwt}",
                'Content-Type'  => 'application/json',
            ];
            $body = [
                "botId"     => $botId,
                "accountId" => $targetId, // グループIDまたはユーザーID
                "content"   => [
                    "type" => "text",
                    "text" => $messageContent,
                ],
            ];

            // GuzzleなどのHTTPクライアントライブラリを使用します
            // composer require guzzlehttp/guzzle
            $client = new \GuzzleHttp\Client();
            $response = $client->post($apiUrl, [
                'headers' => $headers,
                'json'    => $body,
            ]);

            if ($response->getStatusCode() === 200) {
                Log::info("Message sent successfully to {$targetId}.");
                return true;
            } else {
                Log::error("Failed to send message: " . $response->getBody()->getContents());
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Error sending message: " . $e->getMessage());
            return false;
        }
    }
}