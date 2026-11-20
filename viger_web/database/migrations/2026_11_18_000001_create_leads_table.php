<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DB thật có users.id là BIGINT có dấu (khác migration mặc định là UNSIGNED)
        // => tạo cột khoá ngoại cùng kiểu để MySQL chấp nhận ràng buộc.
        $usersIdUnsigned = str_contains(
            strtolower(collect(Schema::getColumns('users'))->firstWhere('name', 'id')['type'] ?? ''),
            'unsigned'
        );

        Schema::create('leads', function (Blueprint $table) use ($usersIdUnsigned) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable();
            $table->string('source', 30)->default('website')->index();   // Lead::SOURCES
            $table->string('status', 30)->default('new')->index();       // Lead::STATUSES
            $table->bigInteger('assigned_to', unsigned: $usersIdUnsigned)->nullable();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
