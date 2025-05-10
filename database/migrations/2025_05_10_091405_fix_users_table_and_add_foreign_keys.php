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
        // First check if the columns exist, if not add them
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('User'); // Administrator, Approver, User
            });
        }
        
        if (!Schema::hasColumn('users', 'department')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('department')->nullable();
            });
        }
        
        if (!Schema::hasColumn('users', 'location_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('location_id')->nullable();
            });
        }
        
        if (!Schema::hasColumn('users', 'supervisor_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('supervisor_id')->nullable();
            });
        }
        
        // Now add the foreign key constraints
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
            
            // Optionally drop the columns too
            $table->dropColumn(['role', 'department', 'location_id', 'supervisor_id']);
        });
    }
};



// // database/migrations/2023_01_01_000008_add_foreign_keys_to_users_table.php