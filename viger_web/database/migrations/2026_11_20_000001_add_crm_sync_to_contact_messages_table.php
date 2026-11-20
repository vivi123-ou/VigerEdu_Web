<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Theo dõi việc gửi form liên hệ sang CRM (HOP_DONG_KET_NOI mục 4):
 * event_id cố định cho mỗi form => gửi lại nhiều lần CRM cũng không tạo trùng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->text('consent_text')->nullable();   // đúng câu đồng ý khách thấy lúc bấm gửi
            $table->uuid('crm_event_id')->nullable()->unique();
            $table->string('crm_lead_code', 50)->nullable();
            $table->timestamp('crm_synced_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropUnique(['crm_event_id']);
            $table->dropIndex(['crm_synced_at']);
            $table->dropColumn(['consent_text', 'crm_event_id', 'crm_lead_code', 'crm_synced_at']);
        });
    }
};
