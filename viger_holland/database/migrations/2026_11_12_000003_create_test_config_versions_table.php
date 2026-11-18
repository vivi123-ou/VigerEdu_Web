<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cài đặt bài test có phiên bản: mỗi lần lưu thêm một dòng, dòng mới nhất là bản đang dùng.
     * Lượt làm bài ghi lại phiên bản đã dùng → đổi cài đặt không làm đổi kết quả cũ.
     */
    public function up(): void
    {
        Schema::create('test_config_versions', function (Blueprint $table) {
            $table->id();
            $table->json('settings');
            $table->string('note', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_config_versions');
    }
};
