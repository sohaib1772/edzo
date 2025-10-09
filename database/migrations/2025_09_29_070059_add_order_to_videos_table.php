<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::table('videos', function (Blueprint $table) {
        $table->integer('order')->default(0)->after('is_paid');
    });

    // ملء الترتيب الحالي بنفس ترتيب الـ id
    $videos = DB::table('videos')->orderBy('id')->get();
    $i = 1;
    foreach ($videos as $video) {
        DB::table('videos')
            ->where('id', $video->id)
            ->update(['order' => $i++]);
    }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('videos', function (Blueprint $table) {
        $table->dropColumn('order');
    });
    }
};
