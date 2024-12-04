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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_item_id')->constrained('product_items');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->boolean('is_discount')->default(false);
            $table->decimal('unit_discount_price', 10, 2)->nullable();
            $table->decimal('unit_price_after_discount', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
