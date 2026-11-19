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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('sex');
            $table->unsignedSmallInteger('age');
            $table->decimal('height_cm', 5, 1);
            $table->decimal('body_fat_percent', 4, 1);
            $table->decimal('target_body_fat_percent', 4, 1);
            $table->string('activity');
            $table->decimal('weekly_loss_kg', 3, 2);
            $table->timestamps();
        });

        DB::table('profiles')->insert([
            'sex' => 'male',
            'age' => 40,
            'height_cm' => 187,
            'body_fat_percent' => 23.8,
            'target_body_fat_percent' => 20,
            'activity' => 'light',
            'weekly_loss_kg' => 0.50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
