<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hàng đợi gửi sang CRM: gói lead chờ gửi / gửi lỗi để thử lại (giai đoạn 4 mới gửi thật).
     */
    public function up(): void
    {
        Schema::create('outbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->default('lead');
            $table->longText('payload');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('tries')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox');
    }
};
