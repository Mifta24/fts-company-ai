<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the company sells. Pricing lives here and only here — the AI Staff
     * may quote a price only when it comes back from a tool reading this table.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('category'); // ai | saas | development | automation
            $table->string('name');
            $table->string('summary');
            $table->text('description');
            $table->json('translations')->nullable();
            $table->json('features')->nullable();
            $table->json('ideal_for')->nullable();
            $table->string('pricing_model')->default('quotation'); // subscription | one_time | quotation
            $table->decimal('starting_price', 14, 2)->nullable();
            $table->string('price_unit')->nullable(); // e.g. "per month"
            $table->text('price_note')->nullable();
            $table->json('pricing_tiers')->nullable();
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
        Schema::dropIfExists('services');
    }
};
