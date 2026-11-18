<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trường ↔ ngành (Thầy / Vân 18/11): mỗi dòng là một chương trình học của một trường thuộc một ngành trong danh mục.
 * Dùng cho "Trường có ngành này" (chỉ quản trị viên và tư vấn viên thấy). Chỉ thêm bảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->string('degree', 50)->default('')->comment('Bachelor, Master, Staatsexamen…; rỗng = chưa rõ');
            $table->string('program_name', 255)->nullable()->comment('Tên chương trình tại trường, ví dụ Informatik (B.Sc.)');
            $table->string('language', 50)->nullable()->comment('Ngôn ngữ dạy: Deutsch, Englisch…');
            $table->string('study_form', 50)->nullable()->comment('Vollzeit, Dual, Teilzeit…');
            $table->string('url', 255)->nullable()->comment('Link trang chương trình');
            $table->boolean('is_verified')->default(false)->comment('Đã kiểm tra trên trang trường / Hochschulkompass');
            $table->text('note')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'major_id', 'degree']);
            $table->index('major_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_programs');
    }
};
