<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trường thông tin khách (form sau khi làm bài). key là mã nội bộ (ho_ten…); crm_key là tên trường bên CRM
     * theo từ điển dữ liệu chung (student_name…). crm_key rỗng thì gửi CRM trong extra_fields.{key}.
     */
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique()->comment('Mã nội bộ, ví dụ ho_ten, so_dien_thoai');
            $table->string('crm_key', 60)->nullable()->comment('Tên trường bên CRM (từ điển), ví dụ student_name; rỗng = extra_fields');
            $table->string('label', 255);
            $table->string('type', 30)->comment('text, textarea, phone, email, year, number, select, radio');
            $table->json('options')->nullable()->comment('Lựa chọn [{value,label}] cho kiểu select');
            $table->string('options_source', 60)->nullable()->comment('Danh sách chuẩn trong lua_chon_chuan.json');
            $table->string('group', 60)->nullable();
            $table->string('help', 500)->nullable();
            $table->string('placeholder', 150)->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('to_crm')->default(true);
            $table->boolean('on_report')->default(false)->comment('Hiện trên báo cáo khách');
            $table->boolean('is_visible')->default(true)->comment('Hiện trên form (ẩn = tự ghi, ví dụ nguồn)');
            $table->boolean('is_system')->default(false)->comment('Trường lõi: không xóa được');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
