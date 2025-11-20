<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if invoices table exists first
        if (!Schema::hasTable('invoices')) {
            return; // Skip this migration if invoices table doesn't exist yet
        }

        // Check if columns exist before adding them
        // These columns may already exist from 2025_01_15_120000_add_invoice_review_fields migration
        
        if (!Schema::hasColumn('invoices', 'assigned_to')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('invoices', 'priority')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedTinyInteger('priority')->nullable()->after('assigned_to');
            });
        }
    }

    public function down(): void
    {
        // Check if invoices table exists first
        if (!Schema::hasTable('invoices')) {
            return; // Skip if table doesn't exist
        }

        // Only drop columns if they exist
        // Note: This migration may not have added these columns if they already existed
        if (Schema::hasColumn('invoices', 'priority')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }
        // Don't drop assigned_to in down() as it may be used by other migrations
    }
};


