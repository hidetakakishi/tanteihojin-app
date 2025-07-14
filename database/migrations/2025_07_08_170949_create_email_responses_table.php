<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_id');  // 外部キー制約なし
            $table->string('staff_name');            // 担当者
            $table->date('handled_at')->nullable();  // 対応日
            $table->string('status')->default('未対応'); // 対応状況
            $table->string('method')->nullable();    // 対応方法
            $table->text('memo')->nullable();        // 担当者メモ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_responses');
    }
};
