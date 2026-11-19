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
        Schema::create('body_fat_entries', function (Blueprint $table) {
            $table->id();
            $table->timestamp('recorded_at');
            $table->decimal('body_fat_percent', 4, 1);
            $table->timestamps();
        });

        $profile = DB::table('profiles')->first();

        if ($profile) {
            $now = now();

            DB::table('body_fat_entries')->insert([
                'recorded_at' => $now,
                'body_fat_percent' => $profile->body_fat_percent,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('body_fat_entries');
    }
};
