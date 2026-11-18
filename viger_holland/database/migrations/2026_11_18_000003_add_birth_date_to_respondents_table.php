<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ngày sinh đầy đủ của học sinh (Vân chốt 18/11: chọn trên lịch). Lưu mã hóa; CRM vẫn nhận năm sinh (birth_year).
 * Chỉ thêm cột cho phép rỗng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('respondents', function (Blueprint $table) {
            $table->text('birth_date')->nullable()->after('parent_phone_hash')->comment('Ngày sinh học sinh, mã hóa');
        });
    }

    public function down(): void
    {
        Schema::table('respondents', fn (Blueprint $table) => $table->dropColumn('birth_date'));
    }
};
