<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('path');
            $table->string('referrer')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('ip', 64)->nullable();
            $table->string('ua', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};


