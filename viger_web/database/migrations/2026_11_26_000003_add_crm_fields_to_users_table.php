<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng users dùng chung với web chính / Filament. Chỉ THÊM cột cho CRM, không đổi cột cũ:
 * - crm_role: vai trò trong CRM (admin | consultant | staff). Null => suy từ role cũ (ADMIN => admin, còn lại consultant).
 * - must_change_password: buộc đổi mật khẩu ở lần đăng nhập CRM kế tiếp (mật khẩu tạm).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('crm_role', 20)->nullable()->index();
            $table->boolean('must_change_password')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['crm_role']);
            $table->dropColumn(['crm_role', 'must_change_password']);
        });
    }
};
