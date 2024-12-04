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
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('status', [
                'pending',
                'order_assigned',
                'on_the_way',
                'picked_up',
                'delivered',
                'completed',
                'failed',
                'refunded',
                'cancelled'
            ]);
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete(); // المستخدم الذي قام بالتغيير
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
    }
};
