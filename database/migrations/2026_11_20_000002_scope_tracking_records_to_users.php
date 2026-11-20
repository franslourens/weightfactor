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
        Schema::table('weight_entries', function (Blueprint $table) {
            $table->dropUnique(['recorded_on']);
        });

        foreach (['profiles', 'weight_entries', 'food_entries', 'body_fat_entries'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            });
        }

        $ownerId = DB::table('users')->orderBy('id')->value('id');

        if ($ownerId) {
            foreach (['profiles', 'weight_entries', 'food_entries', 'body_fat_entries'] as $tableName) {
                DB::table($tableName)->whereNull('user_id')->update(['user_id' => $ownerId]);
            }
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->unique('user_id');
        });

        Schema::table('weight_entries', function (Blueprint $table) {
            $table->unique(['user_id', 'recorded_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weight_entries', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'recorded_on']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        foreach (['body_fat_entries', 'food_entries', 'weight_entries', 'profiles'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        Schema::table('weight_entries', function (Blueprint $table) {
            $table->unique('recorded_on');
        });
    }
};
