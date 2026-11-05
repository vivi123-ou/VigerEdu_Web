<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lượt làm bài. Thông tin liên hệ (contact) được mã hóa, chỉ giữ để gửi CRM.
     */
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('result_token')->unique();
            $table->foreignId('question_set_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 30)->default('web');
            $table->string('engine', 50);
            $table->string('engine_version', 20);
            $table->char('holland_code', 3)->nullable()->index();
            $table->longText('contact')->nullable();
            $table->json('answers');
            $table->json('result');
            $table->json('utm')->nullable();
            $table->string('crm_customer_code', 30)->nullable();
            $table->string('crm_sync_status', 20)->default('pending')->index();
            $table->timestamp('appointment_at')->nullable();
            $table->timestamp('completed_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
