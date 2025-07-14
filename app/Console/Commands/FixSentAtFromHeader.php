<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Email;
use Carbon\Carbon;

class FixSentAtFromHeader extends Command
{
    protected $signature = 'emails:fix-sent-at';
    protected $description = 'メール本文のヘッダー送信日時から sent_at を更新する';

    public function handle()
    {
        $emails = Email::all();
        $updatedCount = 0;

        foreach ($emails as $email) {
            $newSentAt = $this->extractSentAtFromHeader($email->body);

            if ($newSentAt && $email->sent_at != $newSentAt) {
                $email->sent_at = $newSentAt;
                $email->save();
                $this->info("Updated email ID {$email->id} with new sent_at: {$newSentAt}");
                $updatedCount++;
            }
        }

        $this->info("✔ {$updatedCount} 件の sent_at を更新しました。");
        return 0;
    }

    private function extractSentAtFromHeader(string $body): ?Carbon
    {
        $lines = preg_split("/\r\n|\r|\n/", $body);

        foreach ($lines as $line) {
            // 正規表現で「送信日時: YYYY/MM/DD HH:MM」の形式にマッチ
            if (preg_match('/送信日時\s*:\s*(\d{4})\/(\d{2})\/(\d{2})\s+(\d{2}):(\d{2})/', $line, $matches)) {
                try {
                    return Carbon::createFromFormat('Y/m/d H:i', "{$matches[1]}/{$matches[2]}/{$matches[3]} {$matches[4]}:{$matches[5]}");
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        return null;
    }
}