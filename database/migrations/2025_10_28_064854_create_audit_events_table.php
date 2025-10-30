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
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('action');
            $table->json('diff_json')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('ua')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['actor_type', 'actor_id']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
