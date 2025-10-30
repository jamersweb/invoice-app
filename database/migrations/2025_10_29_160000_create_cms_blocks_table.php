<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cms_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('locale', 10)->default('en')->index();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('cta_href')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_blocks');
    }
};


