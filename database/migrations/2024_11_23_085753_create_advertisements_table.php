<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('image');
            $table->enum('type', ['internal', 'external']);


            $table->foreignId('vendor_id')->nullable()->constrained()->onDelete('set null');


            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');

            $table->string('target_link')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // سعر الإعلان
            $table->enum('status', ['pending', 'active', 'expired'])->default('pending');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('placement', ['main_page', 'specific_section']); // مكان الإعلان


            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');


            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
