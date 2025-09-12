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
        Schema::table('teachers_info', function (Blueprint $table) {
            $table->string('telegram_url')->nullable(); 
            // بعد عمود 'role'، وnullable حتى لا تتسبب مشاكل مع البيانات القديمة
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teachers_info', function (Blueprint $table) {
            $table->dropColumn('telegram_url');
        });
    }
};
