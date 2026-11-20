<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store weekly loss precisely enough that a 500 kcal deficit round-trips.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->decimal('weekly_loss_kg', 5, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->decimal('weekly_loss_kg', 3, 2)->change();
        });
    }
};
