<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H4: người làm bài, mã xác nhận qua email, lời đồng ý (nếu admin bật câu đồng ý).
 * Chỉ thêm bảng / cột cho phép rỗng (theo quy tắc migration của dự án).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respondents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->text('full_name')->comment('Mã hóa');
            $table->text('email')->comment('Mã hóa');
            $table->char('email_hash', 64)->unique()->comment('Băm email đã chuẩn hóa, để tìm và gộp');
            $table->text('phone')->nullable()->comment('SĐT người điền, mã hóa');
            $table->char('phone_hash', 64)->nullable()->index();
            $table->text('parent_phone')->nullable()->comment('SĐT phụ huynh, mã hóa');
            $table->char('parent_phone_hash', 64)->nullable()->index();
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->string('role', 10)->nullable()->comment('HS / PH / KHAC');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->char('email_hash', 64)->index();
            $table->char('code_hash', 64)->comment('HMAC của mã, không lưu mã gốc');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('respondent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('version', 50)->nullable();
            $table->text('text_snapshot')->comment('Đúng câu khách thấy lúc bấm');
            $table->timestamp('agreed_at');
            $table->timestamps();
        });

        Schema::table('test_sessions', function (Blueprint $table) {
            $table->foreignId('respondent_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->comment('Khách nhập đúng mã email');
            $table->timestamp('report_viewed_at')->nullable()->comment('Báo cáo đầy đủ đã hiện (chỉ hiện một lần)');
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->json('conditions')->nullable()->after('options_source')
                ->comment('Ẩn / đổi chữ theo ô khác, ví dụ {"hide_if":{"doi_tuong":["PH"]},"label_if":{"doi_tuong":{"PH":"Email của học sinh"}}}');
        });
    }

    public function down(): void
    {
        Schema::table('form_fields', fn (Blueprint $table) => $table->dropColumn('conditions'));
        Schema::table('test_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('respondent_id');
            $table->dropColumn(['verified_at', 'report_viewed_at']);
        });
        Schema::dropIfExists('consents');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('respondents');
    }
};
