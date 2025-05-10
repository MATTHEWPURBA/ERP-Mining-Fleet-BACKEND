<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, let's check what columns actually exist in the users table
        $columnsExist = [];
        
        // Check for each column we need
        $columnsExist['role'] = Schema::hasColumn('users', 'role');
        $columnsExist['department'] = Schema::hasColumn('users', 'department');
        $columnsExist['location_id'] = Schema::hasColumn('users', 'location_id');
        $columnsExist['supervisor_id'] = Schema::hasColumn('users', 'supervisor_id');
        
        // Print the current columns in the users table for debugging
        $query = "SELECT column_name FROM information_schema.columns WHERE table_name = 'users'";
        $columns = DB::select($query);
        $columnNames = array_column($columns, 'column_name');
        
        // Log so we can debug
        echo "Current columns in users table: " . implode(', ', $columnNames) . "\n";
        
        // Add missing columns one by one to avoid errors
        if (!$columnsExist['role']) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('User')->after('password');
                echo "Added 'role' column\n";
            });
        }
        
        if (!$columnsExist['department']) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('department')->nullable()->after('role');
                echo "Added 'department' column\n";
            });
        }
        
        if (!$columnsExist['location_id']) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('location_id')->nullable()->after('department');
                echo "Added 'location_id' column\n";
            });
        }
        
        if (!$columnsExist['supervisor_id']) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('supervisor_id')->nullable()->after('location_id');
                echo "Added 'supervisor_id' column\n";
            });
        }
        
        // Add foreign key constraints only if the columns exist
        if (Schema::hasColumn('users', 'location_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    // Check if the constraint already exists
                    $constraints = DB::select("SELECT constraint_name FROM information_schema.table_constraints 
                                              WHERE table_name = 'users' AND constraint_name = 'users_location_id_foreign'");
                    
                    if (empty($constraints)) {
                        $table->foreign('location_id')->references('id')->on('locations');
                        echo "Added foreign key constraint for location_id\n";
                    } else {
                        echo "Foreign key constraint for location_id already exists\n";
                    }
                });
            } catch (\Exception $e) {
                echo "Error adding location_id foreign key: " . $e->getMessage() . "\n";
            }
        }
        
        if (Schema::hasColumn('users', 'supervisor_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    // Check if the constraint already exists
                    $constraints = DB::select("SELECT constraint_name FROM information_schema.table_constraints 
                                              WHERE table_name = 'users' AND constraint_name = 'users_supervisor_id_foreign'");
                    
                    if (empty($constraints)) {
                        $table->foreign('supervisor_id')->references('id')->on('users');
                        echo "Added foreign key constraint for supervisor_id\n";
                    } else {
                        echo "Foreign key constraint for supervisor_id already exists\n";
                    }
                });
            } catch (\Exception $e) {
                echo "Error adding supervisor_id foreign key: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only try to drop constraints if the columns exist
        if (Schema::hasColumn('users', 'location_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['location_id']);
                });
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        }
        
        if (Schema::hasColumn('users', 'supervisor_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['supervisor_id']);
                });
            } catch (\Exception $e) {
                // Constraint might not exist
            }
        }
        
        // Drop columns in reverse order they were added
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'supervisor_id')) {
                $table->dropColumn('supervisor_id');
            }
            
            if (Schema::hasColumn('users', 'location_id')) {
                $table->dropColumn('location_id');
            }
            
            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
            
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};


// // database/migrations/2025_05_10_091944_verify_and_fix_users_table_structure.php