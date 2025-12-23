<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->foreignId('investor_id')->nullable()->after('id')->constrained('users')->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->after('investor_id')->constrained('transactions')->onDelete('set null');
            $table->string('status')->default('Pending')->after('date'); // Pending, Confirmed, Active, Completed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            $table->dropForeign(['investor_id']);
            $table->dropForeign(['transaction_id']);
            $table->dropColumn(['investor_id', 'transaction_id', 'status']);
        });
    }
};
