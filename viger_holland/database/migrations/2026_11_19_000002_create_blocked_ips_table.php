<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chống tấn công (ý Thầy 18/11): địa chỉ IP bị chặn tạm vì truy cập dồn dập hoặc dò đường hacker hay dò (/wp-admin, /.env…),
 * hoặc do quản trị viên chặn tay. Chỉ thêm bảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->string('reason', 255);
            $table->unsignedInteger('hits')->default(0)->comment('Số lần vi phạm trước khi bị chặn');
            $table->string('last_path', 255)->nullable()->comment('Đường dẫn gần nhất (không có tham số)');
            $table->timestamp('blocked_until')->nullable()->comment('Rỗng = chặn đến khi bỏ chặn tay');
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete()->comment('Rỗng = hệ thống tự chặn');
            $table->timestamps();
            $table->index('blocked_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
