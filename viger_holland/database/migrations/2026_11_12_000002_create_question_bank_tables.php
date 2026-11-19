<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thư viện câu hỏi (300 câu của Thầy): chủ đề, câu hỏi có trọng số rút đề, lịch sử đổi trọng số.
     */
    public function up(): void
    {
        Schema::create('question_facets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('R01…C10');
            $table->char('group', 1)->index();
            $table->string('name', 150);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('R001…C050');
            $table->char('group', 1)->index();
            $table->foreignId('question_facet_id')->nullable()->constrained()->nullOnDelete();
            $table->text('text');
            $table->char('priority_tier', 1)->default('B')->comment('A lõi, B chuẩn, C mở rộng');
            $table->decimal('selection_weight', 6, 2)->default(3)->comment('Chỉ dùng để rút đề, không dùng tính điểm');
            $table->boolean('is_anchor')->default(false)->comment('Câu neo: luôn có trong đề khi bật câu neo');
            $table->string('review_status', 20)->default('approved')->index()->comment('draft / approved / suspended');
            $table->string('validation_status', 30)->nullable();
            $table->string('language', 10)->default('vi-VN');
            $table->string('target_group', 50)->nullable();
            $table->string('item_version', 20)->default('1.0');
            $table->text('review_note')->nullable();
            $table->string('source_reference')->nullable();
            $table->date('last_reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('question_weight_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_weight', 6, 2);
            $table->decimal('new_weight', 6, 2);
            $table->string('reason', 500);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_weight_audits');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_facets');
    }
};
