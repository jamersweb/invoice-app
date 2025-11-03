<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_grades', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // A, B, C, D, A+, A-, etc.
            $table->string('name'); // "Excellent", "Good", "Average", etc.
            $table->text('description')->nullable();
            $table->decimal('default_rate_adjustment', 5, 4)->default(0); // Adjustment to base rate
            $table->decimal('max_funding_limit', 18, 2)->nullable();
            $table->integer('max_tenor_days')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_grades');
    }
};







