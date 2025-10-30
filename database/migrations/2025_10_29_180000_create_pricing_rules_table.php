<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tenor_min');
            $table->unsignedInteger('tenor_max');
            $table->decimal('amount_min', 18, 2)->default(0);
            $table->decimal('amount_max', 18, 2)->default(999999999);
            $table->decimal('base_rate', 6, 3); // percent
            $table->decimal('vip_adjust', 6, 3)->default(-0.500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenor_min','tenor_max']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};


