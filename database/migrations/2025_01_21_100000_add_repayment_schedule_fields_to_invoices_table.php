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

        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('repayment_parts')->nullable()->after('due_date'); // 3, 6, etc.
            $table->decimal('extra_percentage', 5, 2)->default(0)->after('repayment_parts'); // Extra interest/percentage
            $table->decimal('funded_amount', 18, 2)->nullable()->after('extra_percentage'); // Amount actually funded
            $table->date('funded_date')->nullable()->after('funded_amount'); // Date when payment was made
            $table->unsignedBigInteger('funded_by')->nullable()->after('funded_date'); // Admin who recorded payment
        });
    }

    public function down(): void
    {
        // Check if invoices table exists first
        if (!Schema::hasTable('invoices')) {
            return; // Skip if table doesn't exist
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'repayment_parts',
                'extra_percentage',
                'funded_amount',
                'funded_date',
                'funded_by',
            ]);
        });
    }
};

