<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lượt làm bài: đề đã phát (36 câu, phiên bản, thứ tự), đáp án, thông tin khách điền, kết quả.
     */
    public function up(): void
    {
        Schema::create('test_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('result_token')->unique()->comment('Link xem báo cáo khách');
            $table->foreignId('test_config_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('in_progress')->index()->comment('in_progress / answered / completed');
            $table->string('device_hash', 64)->nullable()->index()->comment('Băm cookie thiết bị, để hạn chế lặp câu khi làm lại');
            $table->string('generation_seed', 64)->nullable();
            $table->json('generation_warnings')->nullable();
            $table->string('source', 50)->default('holland');
            $table->json('utm')->nullable();
            $table->json('scores')->nullable()->comment('Điểm trung bình 6 nhóm');
            $table->json('result')->nullable()->comment('Kết quả đầy đủ (bản nội bộ)');
            $table->char('code2', 2)->nullable()->index();
            $table->char('code3', 3)->nullable()->index();
            $table->string('profile_case', 1)->nullable()->comment('A ba nhóm / B hai nhóm / C một nhóm / D chưa nhóm nào nổi bật');
            $table->boolean('needs_counseling')->default(false)->index();
            $table->string('reliability', 50)->nullable();
            $table->string('crm_status', 20)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('session_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained();
            $table->char('group', 1);
            $table->string('item_version', 20);
            $table->text('text_snapshot')->comment('Nội dung câu lúc phát đề');
            $table->unsignedSmallInteger('display_order');
            $table->unique(['test_session_id', 'question_id']);
        });

        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained();
            $table->unsignedTinyInteger('value')->comment('Likert 1–5');
            $table->unsignedInteger('ms')->nullable()->comment('Thời gian trả lời (mili giây)');
            $table->timestamps();
            $table->unique(['test_session_id', 'question_id']);
        });

        Schema::create('field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_field_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key', 60)->comment('Khóa lúc điền, giữ lại kể cả khi trường bị xóa');
            $table->text('value')->nullable()->comment('Chữ, hoặc JSON với bảng điểm môn');
            $table->timestamps();
            $table->unique(['test_session_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_values');
        Schema::dropIfExists('responses');
        Schema::dropIfExists('session_questions');
        Schema::dropIfExists('test_sessions');
    }
};
