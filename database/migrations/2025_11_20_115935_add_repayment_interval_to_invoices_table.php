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
        // Check if invoices table exists first
        if (!Schema::hasTable('invoices')) {
            return; // Skip this migration if invoices table doesn't exist yet
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('repayment_interval_days')->nullable()->after('repayment_parts')->comment('Days between each repayment (e.g., 30, 60, 90)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if invoices table exists first
        if (!Schema::hasTable('invoices')) {
            return; // Skip if table doesn't exist
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('repayment_interval_days');
        });
    }
};
