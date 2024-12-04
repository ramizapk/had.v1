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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id(); // المفتاح الأساسي
            $table->string('method_name'); // اسم طريقة الدفع
            $table->string('account_name')->nullable(); // اسم الحساب (يمكن أن يكون فارغًا)
            $table->string('account_number')->nullable(); // رقم الحساب (يمكن أن يكون فارغًا)
            $table->string('company_logo')->nullable(); // لوجو الشركة (يمكن أن يكون فارغًا)
            $table->timestamps(); // لحفظ تاريخ الإنشاء والتحديث
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
