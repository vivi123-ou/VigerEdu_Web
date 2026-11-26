<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lưu nguyên kết quả Holland gửi sang (holland-result/3 — HOP_DONG_KET_NOI mục 3.2):
 * điểm trung bình 1–5, mã 3 chữ, độ tin cậy, ngành gợi ý. Chỉ thêm cột, không đổi cột cũ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->json('holland_result')->nullable();
            $table->string('holland_code', 3)->nullable()->index();   // code_full do Holland tính
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['holland_code']);
            $table->dropColumn(['holland_result', 'holland_code']);
        });
    }
};
