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
            $table->unsignedTinyInteger('priority')->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['assigned_to','priority']);
        });
    }
};


