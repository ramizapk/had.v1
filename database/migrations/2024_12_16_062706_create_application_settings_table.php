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
        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->time('open_time')->nullable(); // وقت الفتح
            $table->time('close_time')->nullable(); // وقت الغلق
            $table->string('whatsapp_number')->nullable(); // رقم الواتس آب
            $table->string('contact_number')->nullable(); // رقم الاتصال
            $table->string('facebook_page')->nullable(); // صفحة فيسبوك
            $table->string('instagram_page')->nullable(); // صفحة انستجرام
            $table->string('twitter_page')->nullable(); // صفحة تويتر
            $table->string('tiktok_page')->nullable(); // صفحة تيك توك
            $table->boolean('is_open')->default(true); // الحالة: مفتوح/مغلق
            $table->text('closure_reason')->nullable(); // سبب الإغلاق
            $table->decimal('delivery_price_first_3km', 8, 2)->nullable();
            $table->decimal('delivery_price_additional_per_km', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};
