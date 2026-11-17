<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phân quyền bằng dữ liệu: vai trò, quyền, quyền của từng vai trò.
     * Quản trị viên thêm vai trò mới (ví dụ Kế toán) và tick quyền trên giao diện, không sửa code.
     * users.role lưu mã vai trò (ADMIN, COUNSELOR…) trỏ tới roles.code.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false)->comment('Vai trò lõi: không xóa được');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique()->comment('Ví dụ reports.lookup, data.export');
            $table->string('name', 150)->comment('Câu "Được…" hiện trên trang Phân quyền');
            $table->string('group', 60)->nullable();
            $table->string('description', 255)->nullable()->comment('Giải thích thêm dưới câu "Được…"');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
