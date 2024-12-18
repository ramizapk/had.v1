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
        Schema::create('direct_orders', function (Blueprint $table) {
            $table->id();
            $table->decimal('customer_lat', 10, 7);
            $table->decimal('customer_long', 10, 7);
            $table->decimal('vendor_lat', 10, 7)->nullable();
            $table->decimal('vendor_long', 10, 7)->nullable();
            $table->float('distance')->nullable();
            $table->decimal('delivery_fee', 10, 2);
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
            $table->enum('payment_method', [
                'cash_on_delivery',
                'card_payment',
                'wallet',
                'e_wallet',
                'bank'
            ]);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('delivery_agent_id')->nullable()->constrained('delivery_agents');
            $table->boolean('is_vendor')->default(false);
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->string('vendor_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_orders');
    }
};
