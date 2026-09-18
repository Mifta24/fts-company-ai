<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prospects the AI Staff qualified: consultation, demo, or quotation
     * requests. This is the company-website equivalent of a hotel booking.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // consultation | demo | quotation
            $table->string('name');
            $table->string('organization')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('business_type')->nullable();
            $table->string('country')->nullable();
            $table->string('preferred_contact')->nullable(); // whatsapp | email | phone | meeting
            $table->string('preferred_time')->nullable();
            $table->string('budget_range')->nullable();
            $table->text('needs_summary');
            $table->string('status')->default('new'); // new | contacted | qualified | won | lost
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
