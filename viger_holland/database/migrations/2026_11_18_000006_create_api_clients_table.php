<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H7: ứng dụng được gọi API của Holland (chatbot, CRM): token lưu dạng băm, có phạm vi quyền, thu hồi được. Chỉ thêm bảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Ví dụ: Chatbot, CRM');
            $table->char('token_hash', 64)->unique()->comment('SHA-256 của token, không lưu token gốc');
            $table->string('token_last4', 4);
            $table->json('scopes')->comment('Ví dụ ["results:read"]');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
