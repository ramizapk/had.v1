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
        Schema::create('return_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('returns_id')->constrained('returns')->cascadeOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'picked_up', 'returned_to_store', 'refunded', 'accepted', 'returned_product'])->default('pending');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete(); // المستخدم الذي قام بالتغيير
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_status_logs');
    }
};
