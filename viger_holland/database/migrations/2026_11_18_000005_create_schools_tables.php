<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H6: danh mục Quốc gia → Vùng (bang) → Trường, thiết kế mở cho nhiều nước: loại hình, hình thức là chữ (dữ liệu),
 * cột không có ô riêng lưu ở extra. Chỉ thêm bảng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('code', 2)->unique()->comment('Mã ISO, ví dụ DE');
            $table->string('name', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10)->nullable();
            $table->string('name', 150);
            $table->string('name_alt', 150)->nullable()->comment('Tên khác, ví dụ tên tiếng Anh');
            $table->string('capital', 150)->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->decimal('area_km2', 12, 2)->nullable();
            $table->unsignedBigInteger('population')->nullable();
            $table->decimal('density', 10, 2)->nullable();
            $table->decimal('hdi', 5, 3)->nullable();
            $table->unsignedInteger('gdp_per_capita')->nullable();
            $table->text('highlights')->nullable();
            $table->text('description')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->unique(['country_id', 'name']);
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_code', 50)->nullable()->comment('Mã trường trong file nguồn, dùng để cập nhật khi nhập lại');
            $table->string('short_name', 100)->nullable();
            $table->string('name', 255);
            $table->string('display_name', 255)->nullable();
            $table->string('type', 100)->nullable()->index()->comment('Loại hình, ví dụ ĐH Ứng dụng');
            $table->string('ownership', 100)->nullable()->comment('Hình thức, ví dụ Công lập');
            $table->unsignedInteger('students')->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->boolean('phd_rights')->nullable();
            $table->string('city', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website', 255)->nullable();
            $table->boolean('is_hidden')->default(false)->comment('Ẩn khỏi danh sách tư vấn (mặc định: ĐH Hành chính, ĐH Đặc thù)');
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->unique(['country_id', 'external_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('countries');
    }
};
