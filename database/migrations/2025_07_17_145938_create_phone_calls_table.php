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
        Schema::create('phone_calls', function (Blueprint $table) {
            $table->id();
            $table->date('call_date');
            $table->time('call_time');
            $table->string('staff_name');
            $table->string('region')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('gender')->nullable();
            $table->string('request_type')->nullable();
            $table->text('request_detail')->nullable();
            $table->text('staff_response')->nullable();
            $table->text('customer_reply')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_calls');
    }
};
