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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->foreignId('address_id')->nullable()->constrained('addresses');
            $table->foreignId('delivery_agent_id')->nullable()->constrained('delivery_agents');
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
            ])->default('pending');
            $table->decimal('total_price', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->boolean('is_coupon')->default(false);
            $table->enum('used_coupon', [
                25,
                50,
                75,
                100
            ])->nullable();
            $table->enum('payment_method', [
                'cash_on_delivery',
                'card_payment',
                'wallet',
                'e_wallet',
                'bank'
            ]);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->decimal('final_price', 10, 2);
            $table->text('notes')->nullable();
            $table->boolean('is_returnable')->default(false);
            $table->decimal('distance', 10, 2)->nullable();
            // $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
