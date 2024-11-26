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
        Schema::create('category_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->cascadeOnDelete()->cascadeOnUpdate();
            $table->boolean('show_in_menu')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_vendor');
    }
};
