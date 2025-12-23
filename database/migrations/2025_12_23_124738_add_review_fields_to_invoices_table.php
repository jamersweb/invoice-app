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
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('priority');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
            $table->integer('repayment_parts')->nullable()->after('review_notes');
            $table->decimal('extra_percentage', 5, 2)->default(0)->after('repayment_parts');
            $table->timestamp('written_off_at')->nullable()->after('extra_percentage');
            $table->unsignedBigInteger('written_off_by')->nullable()->after('written_off_at');
            $table->text('write_off_reason')->nullable()->after('written_off_by');
            $table->text('dispute_notes')->nullable()->after('write_off_reason');

            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('written_off_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['written_off_by']);
            $table->dropColumn([
                'reviewed_by',
                'reviewed_at',
                'review_notes',
                'repayment_parts',
                'extra_percentage',
                'written_off_at',
                'written_off_by',
                'write_off_reason',
                'dispute_notes',
            ]);
        });
    }
};
