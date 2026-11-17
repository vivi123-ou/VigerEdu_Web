<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Danh mục Career Map: 6 nhóm, 15 cặp, 6 hướng chuyên sâu (ghép ra 120 mã), cụm ngành, môn học,
     * ngành học tại Đức, nghề nghiệp, môi trường làm việc. Admin sửa được tất cả.
     */
    public function up(): void
    {
        Schema::create('riasec_types', function (Blueprint $table) {
            $table->id();
            $table->char('code', 1)->unique();
            $table->string('name', 100);
            $table->string('label', 150)->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('hex_index');
            $table->timestamps();
        });

        Schema::create('riasec_pairs', function (Blueprint $table) {
            $table->id();
            $table->char('pair', 2)->unique()->comment('Cặp không thứ tự, viết theo thứ tự RIASEC, ví dụ RI');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('hex_distance');
            $table->string('relation', 50);
            $table->json('occupations')->nullable()->comment('Nghề tham khảo của cặp (chữ)');
            $table->text('foundation')->nullable()->comment('Môn nền / năng lực cần chuẩn bị');
            $table->timestamps();
        });

        Schema::create('third_letters', function (Blueprint $table) {
            $table->id();
            $table->char('code', 1)->unique();
            $table->string('specialty_de', 150)->nullable();
            $table->string('specialty_en', 150)->nullable();
            $table->text('skills')->nullable();
            $table->timestamps();
        });

        Schema::create('code_overrides', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique()->comment('Mã 3 chữ có thứ tự cần nội dung riêng');
            $table->text('interpretation')->nullable();
            $table->text('exploration')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('major_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->json('subject_weights')->nullable()->comment('{mã môn: tỉ trọng} cho chỉ số nền tảng học tập');
            $table->decimal('gpa_weight', 4, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('MAJ001…');
            $table->string('name_vi', 150);
            $table->string('name_de', 150)->nullable();
            $table->string('name_en', 150)->nullable();
            $table->foreignId('major_group_id')->nullable()->constrained()->nullOnDelete();
            $table->json('holland_codes')->nullable()->comment('1–3 mã 3 chữ, ví dụ ["RIE"]');
            $table->string('code_source')->nullable();
            $table->string('routes', 100)->nullable()->comment('Uni, HAW, Dual…');
            $table->string('validation_status', 100)->nullable();
            $table->string('source_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pair_major', function (Blueprint $table) {
            $table->id();
            $table->foreignId('riasec_pair_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unique(['riasec_pair_id', 'major_id']);
        });

        Schema::create('environments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('activities')->nullable();
            $table->string('interest_hint', 30)->nullable()->comment('Nhóm Holland gợi ý, ví dụ R;I;C');
            $table->timestamps();
        });

        Schema::create('occupations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('OCC001…');
            $table->string('name_vi', 150)->nullable();
            $table->string('name_de', 150)->nullable();
            $table->string('name_en', 150)->nullable();
            $table->string('sector', 30)->nullable();
            $table->text('tasks')->nullable();
            $table->text('skills')->nullable();
            $table->string('qualification', 100)->nullable();
            $table->string('regulation', 50)->nullable()->comment('Nghề được quản lý tại Đức');
            $table->string('riasec_hypothesis', 10)->nullable();
            $table->string('source')->nullable();
            $table->string('validation_status', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('environment_occupation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->unique(['environment_id', 'occupation_id']);
        });

        Schema::create('major_occupation', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('LNK001…');
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->string('link_type', 100)->nullable();
            $table->string('routes', 100)->nullable();
            $table->string('source')->nullable();
            $table->string('validation_status', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['major_occupation', 'environment_occupation', 'occupations', 'environments', 'pair_major', 'majors',
            'subjects', 'major_groups', 'code_overrides', 'third_letters', 'riasec_pairs', 'riasec_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
