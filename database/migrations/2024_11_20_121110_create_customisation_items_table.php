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
        Schema::create('customisation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customisation_id')->constrained();
            $table->foreignId('product_id')->constrained('product_items');
            $table->json('items')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customisation_items');
    }
};
