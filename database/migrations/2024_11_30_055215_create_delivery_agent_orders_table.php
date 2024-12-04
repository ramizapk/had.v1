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
        Schema::create('delivery_agent_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('delivery_agent_id')->constrained('delivery_agents')->cascadeOnDelete();
            $table->boolean('is_accepted')->default(false); // هل قبل عامل التوصيل الطلب
            $table->boolean('is_rejected')->default(false); // هل تم رفض الطلب من قبل عامل التوصيل
            $table->timestamp('accepted_at')->nullable(); // وقت قبول الطلب
            $table->timestamp('rejected_at')->nullable(); // وقت رفض الطلب
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_agent_orders');
    }
};
