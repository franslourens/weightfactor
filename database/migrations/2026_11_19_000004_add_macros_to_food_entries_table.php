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
        Schema::table('food_entries', function (Blueprint $table) {
            $table->decimal('protein_g', 6, 1)->default(0)->after('calories');
            $table->decimal('carbs_g', 6, 1)->default(0)->after('protein_g');
            $table->decimal('fat_g', 6, 1)->default(0)->after('carbs_g');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_entries', function (Blueprint $table) {
            $table->dropColumn(['protein_g', 'carbs_g', 'fat_g']);
        });
    }
};
