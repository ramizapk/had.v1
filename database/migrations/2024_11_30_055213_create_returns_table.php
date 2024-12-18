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
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_agent_id')->nullable()->constrained('delivery_agents');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('distance', 10, 2)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'picked_up', 'returned_to_store', 'refunded', 'accepted', 'returned_product'])->default('pending');
            $table->text('reason')->nullable();
            $table->longText('customer_location')->nullable();
            $table->double('customer_latitude');
            $table->double('customer_longitude');
            $table->decimal('return_price', 10, 2);
            $table->enum('payment_method', [
                'cash_on_delivery',
                'card_payment',
                'wallet',
                'e_wallet',
                'bank'
            ])->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
