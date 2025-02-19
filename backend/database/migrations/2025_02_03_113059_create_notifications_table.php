<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true); // معرف الإشعار
            $table->unsignedBigInteger('user_id')->nullable(); // معرف المستخدم الذي يتلقى الإشعار
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null'); // مفتاح خارجي مع علاقة بـ users
            $table->text('message'); // نص الإشعار (بدون عنوان)
            $table->string('type'); // نوع الإشعار (مثل 'order', 'promotion', إلخ)
            $table->enum('read_status', ['unread', 'read'])->default('unread'); // حالة الإشعار (مقروء / غير مقروء)
            $table->timestamp('read_at')->nullable(); // تاريخ ووقت قراءة الإشعار
            $table->boolean('is_active')->default(true); // حالة الإشعار
            $table->timestamps(); // تواريخ الإنشاء والتحديث
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
