<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('funding_batches', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('status')->default('created'); // created, approved, executed
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('executed_by')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('fundings', function (Blueprint $table) {
            if (!Schema::hasColumn('fundings', 'batch_id')) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('offer_id');
            }
            if (!Schema::hasColumn('fundings', 'status')) {
                $table->string('status')->default('queued')->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fundings', function (Blueprint $table) {
            if (Schema::hasColumn('fundings', 'batch_id')) {
                $table->dropColumn('batch_id');
            }
            if (Schema::hasColumn('fundings', 'status')) {
                $table->dropColumn('status');
            }
        });
        Schema::dropIfExists('funding_batches');
    }
};


