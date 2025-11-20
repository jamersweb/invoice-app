<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if invoices table exists first
        if (!Schema::hasTable('invoices')) {
            return; // Skip this migration if invoices table doesn't exist yet
        }

        // Add columns only if they don't exist
        if (!Schema::hasColumn('invoices', 'assigned_to')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
            });
        }
        
        if (!Schema::hasColumn('invoices', 'reviewed_by')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('assigned_to');
            });
        }
        
        if (!Schema::hasColumn('invoices', 'reviewed_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            });
        }
        
        if (!Schema::hasColumn('invoices', 'review_notes')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->text('review_notes')->nullable()->after('reviewed_at');
            });
        }
        
        if (!Schema::hasColumn('invoices', 'dispute_notes')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->text('dispute_notes')->nullable()->after('review_notes');
            });
        }
        
        if (!Schema::hasColumn('invoices', 'written_off_at')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->timestamp('written_off_at')->nullable()->after('dispute_notes');
            });
        }
        
        if (!Schema::hasColumn('invoices', 'written_off_by')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('written_off_by')->nullable()->after('written_off_at');
            });
        }
        
        if (!Schema::hasColumn('invoices', 'write_off_reason')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('write_off_reason')->nullable()->after('written_off_by');
            });
        }
        
        if (!Schema::hasColumn('invoices', 'priority')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedTinyInteger('priority')->default(0)->after('write_off_reason');
            });
        }

        // Add indexes only if they don't exist
        if (Schema::hasColumn('invoices', 'assigned_to') && Schema::hasColumn('invoices', 'status')) {
            $indexes = DB::select("SHOW INDEXES FROM invoices WHERE Key_name = 'invoices_assigned_to_status_index'");
            if (empty($indexes)) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->index(['assigned_to', 'status']);
                });
            }
        }
        
        if (Schema::hasColumn('invoices', 'priority')) {
            $indexes = DB::select("SHOW INDEXES FROM invoices WHERE Key_name = 'invoices_priority_index'");
            if (empty($indexes)) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->index('priority');
                });
            }
        }

        // Add foreign keys only if they don't exist
        if (Schema::hasColumn('invoices', 'assigned_to')) {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'assigned_to' AND CONSTRAINT_NAME != 'PRIMARY'");
            if (empty($foreignKeys)) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
                });
            }
        }
        
        if (Schema::hasColumn('invoices', 'reviewed_by')) {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'reviewed_by' AND CONSTRAINT_NAME != 'PRIMARY'");
            if (empty($foreignKeys)) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
                });
            }
        }
        
        if (Schema::hasColumn('invoices', 'written_off_by')) {
            $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invoices' AND COLUMN_NAME = 'written_off_by' AND CONSTRAINT_NAME != 'PRIMARY'");
            if (empty($foreignKeys)) {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->foreign('written_off_by')->references('id')->on('users')->onDelete('set null');
                });
            }
        }
    }

    public function down(): void
    {
        // Check if invoices table exists first
        if (!Schema::hasTable('invoices')) {
            return; // Skip if table doesn't exist
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['written_off_by']);
            $table->dropColumn([
                'assigned_to', 'reviewed_by', 'reviewed_at', 'review_notes',
                'dispute_notes', 'written_off_at', 'written_off_by', 'write_off_reason', 'priority'
            ]);
        });
    }
};










