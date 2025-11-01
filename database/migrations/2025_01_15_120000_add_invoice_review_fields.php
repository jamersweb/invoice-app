<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('assigned_to')->nullable()->after('status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('assigned_to');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
            $table->text('dispute_notes')->nullable()->after('review_notes');
            $table->timestamp('written_off_at')->nullable()->after('dispute_notes');
            $table->unsignedBigInteger('written_off_by')->nullable()->after('written_off_at');
            $table->string('write_off_reason')->nullable()->after('written_off_by');
            $table->unsignedTinyInteger('priority')->default(0)->after('write_off_reason');

            $table->index(['assigned_to', 'status']);
            $table->index('priority');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('written_off_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
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






