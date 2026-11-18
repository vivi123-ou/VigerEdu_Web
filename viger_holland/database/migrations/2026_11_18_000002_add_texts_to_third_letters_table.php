<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đoạn "Chữ thứ ba" cho báo cáo khách và tên hướng chuyên sâu tiếng Việt (Vân soạn 18/11). Chỉ thêm cột cho phép rỗng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('third_letters', function (Blueprint $table) {
            $table->string('specialty_vi', 150)->nullable()->after('code')->comment('Tên hướng chuyên sâu tiếng Việt');
            $table->text('description')->nullable()->after('skills')->comment('Đoạn "Chữ thứ ba" khách đọc trong báo cáo');
        });
    }

    public function down(): void
    {
        Schema::table('third_letters', fn (Blueprint $table) => $table->dropColumn(['specialty_vi', 'description']));
    }
};
