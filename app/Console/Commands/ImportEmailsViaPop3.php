<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laminas\Mail\Storage\Pop3;
use App\Models\Email;
use Carbon\Carbon;

class ImportEmailsViaPop3 extends Command
{
    protected $signature = 'emails:import-via-pop3';
    protected $description = 'POP3でメールを受信し保存';

    public function handle()
    {
        $this->info('📡 POP3メール取り込み開始...');

        $accounts = [
            [
                'host'     => 'pop3.tanteihojin.jp',
                'user'     => 'soudan1@tanteihojin.jp',
                'password' => 'S25eLBXEiKWy',
            ],
            [
                'host'     => 'pop3.privateriskconsulting.jp',
                'user'     => 'form@privateriskconsulting.jp',
                'password' => 'CEex79d3sqpF',
            ],
            [
                'host'     => 'mail.tantei-tjc.jp',
                'user'     => 'form@tantei-tjc.jp',
                'password' => '43431',
            ],
            [
                'host'     => 'pop3.tanteik.jp',
                'user'     => 'soudan1@tanteik.jp',
                'password' => '434343',
            ],
        ];

        $totalInsertCount = 0;

        foreach ($accounts as $account) {
            $this->info("📡 接続中: {$account['user']}");

            try {
                $mail = new Pop3([
                    'host'     => $account['host'],
                    'user'     => $account['user'],
                    'password' => $account['password'],
                    'port'     => 110,
                    'ssl'      => false,
                ]);

                $messageCount = $mail->countMessages();
                $this->info("📥 受信メール件数: {$messageCount}");

                for ($i = 1; $i <= $messageCount; $i++) {
                    try {
                        $message = $mail->getMessage($i);

                        $from    = $this->decodeHeader($message->getHeader('from')->getFieldValue() ?? '');
                        $to      = $this->decodeHeader($message->getHeader('to')->getFieldValue() ?? '');
                        $subject = $this->decodeHeader($message->getHeader('subject')->getFieldValue() ?? '');
                        $dateHeader = $message->getHeader('date');
                        $date = $dateHeader ? $dateHeader->getFieldValue() : now()->toRfc2822String();
                        $carbonDate = Carbon::parse($date);

                        $bodyRaw = $message->getContent();
                        $decoded = quoted_printable_decode($bodyRaw);
                        $converted = mb_convert_encoding($decoded, 'UTF-8', 'UTF-8, ISO-8859-1, JIS, SJIS-win');
                        $converted = htmlspecialchars_decode($converted);

                        $convInfo = '通常変換';
                        if (
                            preg_match('/\$[A-Z]|\%[A-Z]/u', $converted) ||
                            preg_match('/=\?[Ii][Ss][Oo]-2022-J[Pp]\?/', $bodyRaw) ||
                            !preg_match('//u', $converted) ||
                            mb_strlen($converted) < 100
                        ) {
                            $converted = @iconv('ISO-2022-JP', 'UTF-8//IGNORE', $decoded);
                            $convInfo = 'ICONV変換';
                        }

                        $body = $this->extractBodyContent($converted);

                        if (!$this->isTargetEmail($from, $subject, $body)) {
                            $this->line("⏩ 対象外のメール: {$subject}");
                            continue;
                        }

                        $site = $this->extractSiteName($to, $body);

                        // 実行画面出力
                        $this->line($convInfo);
                        $this->line($subject);
                        $this->line($body);

                        $exists = Email::where('from', $from)
                            ->where('to', $to)
                            ->where('sent_at', $carbonDate)
                            ->exists();

                        if (!$exists) {
                            $deleted = (str_contains($subject, '求人') || str_contains($body, '求人')) ? 1 : 0;
                            // Email::create([
                            //     'from'     => $from,
                            //     'to'       => $to,
                            //     'subject'  => $subject,
                            //     'body'     => strip_tags($body),
                            //     'sent_at'  => $carbonDate,
                            //     'site'     => $site,
                            //     'deleted_flag' => $deleted,
                            // ]);
                            $this->line("✔ 登録: {$subject}");
                            $totalInsertCount++;
                        } else {
                            $this->line("⚠ 重複スキップ: {$subject}");
                        }
                    } catch (\Throwable $ex) {
                        $this->warn("⚠ スキップ（ヘッダー/本文エラー）: #{$i} - " . $ex->getMessage());
                    }
                }
            } catch (\Throwable $ex) {
                $this->error("❌ エラー（{$account['user']}）: " . $ex->getMessage());
            }
        }

        $this->info("✅ 全アカウントの取り込み完了。新規登録件数: {$totalInsertCount} 件");
    }

    protected function decodeHeader($value)
    {
        if (is_object($value) && method_exists($value, 'getFieldValue')) {
            $value = $value->getFieldValue();
        }

        if (!is_string($value)) {
            return '';
        }

        try {
            $elements = imap_mime_header_decode($value);
        } catch (\Throwable $e) {
            return ''; // エラーが出たら空文字
        }

        $decoded = '';
        foreach ($elements as $element) {
            $charset = strtoupper($element->charset);
            $text = $element->text;

            if ($charset !== 'DEFAULT' && $charset !== 'UTF-8') {
                $text = @mb_convert_encoding($text, 'UTF-8', $charset);
            }

            $decoded .= $text;
        }

        return $decoded;
    }

    /**
     * このメールが保存対象かどうかを判定する
     */
    protected function isTargetEmail(string $from, string $subject, string $body): bool
    {
        // 送信元が指定アドレスなら確定対象
        if ($from === 'soudan1@tanteihojin.jp') {
            return true;
        }

        // 件名に含まれるキーワード一覧（部分一致）
        $subjectKeywords = [
            '【トラブル探偵】',
            '探偵法人調査士会メールフォーム',
            'お問い合わせありがとうございます',
            // 追加したい件名キーワードがあればここに追加
        ];

        foreach ($subjectKeywords as $keyword) {
            if (str_contains($subject, $keyword)) {
                return true;
            }
        }

        // 本文に特定のキーワードを含む場合
        // 「〇〇 =」の "〇〇" に該当するキーワード一覧
        $fields = [
            'サイト',
            'ページURL',
            'お名前',
            'region',
            'お電話番号',
            'メールアドレス',
            '現在のトラブル状況',
            '情報・証拠が必要な方',
            'ご要望、専門家希望',
            '解決にかける予算',
            'ご都合の良い時間帯',
            'お住まい地域',
            'ご連絡先',
            'お調べになりたい事柄',
            '現時点での情報',
            '依頼目的、希望・要望、その他',
            '希望予算',
        ];

        // フィールドごとに「〇〇 =」の正規表現で本文をチェック
        foreach ($fields as $field) {
            if (preg_match('/' . preg_quote($field, '/') . '\s*=/u', $body)) {
                return true;
            }
        }

        // 上記に一致しなければ対象外
        return false;
    }

    private function extractBodyContent(string $rawBody): string
    {
        $rawBody = str_replace("�", '', $rawBody);
        $lines = preg_split("/\r\n|\r|\n/", $rawBody);

        $startIndex = null;
        $endIndex = null;

        foreach ($lines as $index => $line) {
            if ($startIndex === null && Str::contains($line, '送信内容')) {
                $startIndex = $index + 1;
            }

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

    private function extractSiteName(string $to, string $body): string
    {
        $to = trim($to);

        // 宛先メールアドレスに応じて判定
        if ($to === 'soudan1@tanteik.jp') {
            return '探偵興信所一般社団法人';
        }

        if ($to === 'form@privateriskconsulting.jp') {
            return 'PRC';
        }

        if ($to === 'soudan1@tanteihojin.jp') {
            return '探偵法人調査士会';
        }

        // 本文から「サイト = ○○」を抽出して判定
        if (preg_match('/サイト\s*=\s*(.+)/u', $body, $matches)) {
            $site = trim($matches[1]);

            if (in_array($site, ['探偵事務所', '調査士会'])) {
                return '探偵法人調査士会';
            }

            return $site !== '' ? $site : '探偵法人調査士会';
        }

        return '未分類';
    }
}