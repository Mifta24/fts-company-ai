<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Portfolio: real projects and solutions the AI Staff can show visitors.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('client_name')->nullable();
            $table->string('industry')->nullable(); // hospitality | restaurant | internal | ...
            $table->string('status')->default('live'); // live | pilot | demo | in_development
            $table->string('summary');
            $table->text('description');
            $table->json('translations')->nullable();
            $table->json('highlights')->nullable();
            $table->json('tech_stack')->nullable();
            $table->json('tags')->nullable(); // country, sector, features — for keyword search
            $table->string('live_url')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
