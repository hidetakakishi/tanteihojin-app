<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            // 基本属性
            $table->string('name')->nullable();                 // お名前
            $table->string('region')->nullable();               // お住まい地域/住所/勤務希望地 など代表列
            $table->string('phone')->nullable();                // ご連絡先
            $table->string('email')->nullable();                // メールアドレス
            $table->string('age')->nullable();                  // 年齢（表記ブレ吸収のためstring）
            $table->string('gender')->nullable();               // 性別（文字列）
            $table->string('desired_type')->nullable();         // 希望種別/希望項目
            $table->string('desired_area')->nullable();         // 勤務希望地域/地
            // 応募内容
            $table->string('site')->nullable();                 // サイト
            $table->string('page_url')->nullable();             // ページURL
            $table->text('reason')->nullable();                 // 探偵業を選ぶ理由/応募理由
            $table->text('experience')->nullable();             // 職歴/経験
            $table->text('qualifications')->nullable();         // 有資格・免許/資格・経験
            $table->text('personality')->nullable();            // 自分の性格
            $table->longText('body')->nullable();               // 送信内容の生テキスト（全文）
            // メタ
            $table->dateTime('sent_at')->nullable()->index();   // 送信日時（datetime）
            $table->boolean('deleted_flag')->default(false);    // 論理削除フラグ（一覧既定は除外）
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('job_applications');
    }
};