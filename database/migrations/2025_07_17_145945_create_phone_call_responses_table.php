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
        Schema::create('phone_call_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phone_call_id');
            $table->string('staff_name');
            $table->string('status')->default('対応中');
            $table->dateTime('handled_at')->nullable();
            $table->string('method')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_call_responses');
    }
};
