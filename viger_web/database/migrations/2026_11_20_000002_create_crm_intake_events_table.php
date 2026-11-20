<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phía CRM: lưu event_id đã nhận để API idempotent — gửi lại cùng event_id trả lại kết quả cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_intake_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('channel', 30);
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->json('response');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_intake_events');
    }
};
