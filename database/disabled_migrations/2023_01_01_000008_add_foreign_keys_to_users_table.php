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
        Schema::table('users', function (Blueprint $table) {
            // Now that locations table exists, we can add the foreign key constraint
            $table->foreign('location_id')->references('id')->on('locations');
            
            // Now that users table exists, we can add the self-referencing foreign key
            $table->foreign('supervisor_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['supervisor_id']);
        });
    }
};

// database/disabled_migrations/2023_01_01_000008_add_foreign_keys_to_users_table.php