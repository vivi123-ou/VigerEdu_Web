<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bộ câu hỏi Phần B (ví dụ "THPT 54 câu", "Sinh viên"). Chọn bộ qua link ?bo={code}.
     */
    public function up(): void
    {
        Schema::create('question_sets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('answer_type', 20)->default('yes_no');
            $table->json('scale')->nullable();
            $table->unsignedSmallInteger('per_page')->default(9);
            $table->boolean('shuffle_groups')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_sets');
    }
};
