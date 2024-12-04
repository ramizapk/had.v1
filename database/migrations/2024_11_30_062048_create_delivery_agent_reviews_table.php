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
        Schema::create('delivery_agent_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete(); // ربط التقييم بالطلب
            $table->foreignId('delivery_agent_id')->constrained('delivery_agents')->cascadeOnDelete(); // ربط التقييم بعامل التوصيل
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete(); // ربط التقييم بالعميل
            $table->integer('rating')->default(5)->comment('التقييم من 1 إلى 5');
            $table->text('review')->nullable()->comment('مراجعة النصية للتقييم');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_agent_reviews');
    }
};
