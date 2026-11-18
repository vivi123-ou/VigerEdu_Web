<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H5: hàng đợi gửi khách sang CRM (crm_outbox), hành vi khách (tracking_events), mã khách CRM trả về.
 * Chỉ thêm bảng / cột cho phép rỗng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_outbox', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique()->comment('Gửi lại vẫn giữ mã này: CRM nhận trùng thì trả kết quả cũ');
            $table->foreignId('test_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('schema', 20)->comment('lead/2 hoặc events/1');
            $table->longText('payload')->comment('Gói gửi, mã hóa');
            $table->string('status', 20)->default('pending')->index()->comment('pending / sent / failed / disabled');
            $table->unsignedTinyInteger('tries')->default(0);
            $table->timestamp('next_try_at')->nullable()->index();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->string('last_error', 500)->nullable()->comment('Không chứa dữ liệu cá nhân');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('sent_at')->nullable()->comment('Đã gửi sang CRM (trong gói lead hoặc events)');
        });

        Schema::table('respondents', function (Blueprint $table) {
            $table->string('crm_customer_code', 40)->nullable()->after('role')->index()->comment('Mã khách CRM trả về, gửi kèm ở các lần sau');
        });
    }

    public function down(): void
    {
        Schema::table('respondents', fn (Blueprint $table) => $table->dropColumn('crm_customer_code'));
        Schema::dropIfExists('tracking_events');
        Schema::dropIfExists('crm_outbox');
    }
};
