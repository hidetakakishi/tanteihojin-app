<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('job_application_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_application_id');     // 外部キー制約は任意
            $table->string('staff_name');                         // 担当者
            $table->dateTime('handled_at')->nullable();           // 対応日（datetime）
            $table->string('status')->default('未対応');          // 対応状況
            $table->string('method')->nullable();                 // 対応方法
            $table->text('memo')->nullable();                     // メモ
            $table->timestamps();

            $table->index('job_application_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('job_application_responses');
    }
};