<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Email;
use Carbon\Carbon;

class UpdateSentAtFromFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-sent-at-from-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folder = storage_path('app/emails');
        $files = File::files($folder);
        $updated = 0;

        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            $body = $this->extractBodyContent($content);
            $sentAt = $this->extractSentAtFromHeader($content);

            if ($body && $sentAt) {
                $email = Email::where('body', $body)->first();
                if ($email) {
                    $email->sent_at = $sentAt;
                    $email->save();
                    $updated++;
                    $this->info("Updated ID: {$email->id}, SentAt: {$sentAt}");
                }
            }
        }

        $this->info("✅ 更新完了: {$updated} 件");
        return Command::SUCCESS;
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

    private function extractSentAtFromHeader(string $rawBody): ?Carbon
    {
        $lines = preg_split("/\r\n|\r|\n/", $rawBody);

        foreach ($lines as $line) {
            if (preg_match('/送信日時[:：]?\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\s+(\d{1,2}):(\d{1,2})/', $line, $m)) {
                try {
                    return Carbon::createFromFormat('Y/n/j G:i', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}");
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (preg_match('/送信日時\s*[:：]\s*(\d{4})\/(\d{1,2})\/(\d{1,2})\([^)]+\)\s+(\d{1,2}):(\d{1,2}):(\d{1,2})/', $line, $m)) {
                try {
                    return Carbon::createFromFormat('Y/n/j G:i:s', "{$m[1]}/{$m[2]}/{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}");
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }
}
