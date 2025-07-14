<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('email_responses', function (Blueprint $table) {
            $table->dateTime('handled_at')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('email_responses', function (Blueprint $table) {
            $table->date('handled_at')->nullable()->change(); // 元に戻す場合
        });
    }
};
