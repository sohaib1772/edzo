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
        Schema::table('playlists', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('teacher_id');
        });

        // نملأ الترتيب الحالي
        $playlists = DB::table('playlists')->orderBy('id')->get();
        $i = 1;
        foreach ($playlists as $playlist) {
            DB::table('playlists')
                ->where('id', $playlist->id)
                ->update(['order' => $i++]);
        }
    }

    public function down(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
