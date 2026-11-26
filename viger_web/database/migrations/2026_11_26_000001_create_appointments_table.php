<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch hẹn của CRM (tạm trong viger_web). Một lead có nhiều lịch hẹn.
 */
return new class extends Migration
{
    public function up(): void
    {
        // DB thật có users.id là BIGINT có dấu => khoá ngoại phải cùng kiểu (xem create_leads_table)
        $usersIdUnsigned = str_contains(
            strtolower(collect(Schema::getColumns('users'))->firstWhere('name', 'id')['type'] ?? ''),
            'unsigned'
        );

        Schema::create('appointments', function (Blueprint $table) use ($usersIdUnsigned) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();

            $table->bigInteger('user_id', unsigned: $usersIdUnsigned)->nullable();      // tư vấn viên phụ trách
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->bigInteger('created_by', unsigned: $usersIdUnsigned)->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->dateTime('scheduled_at')->index();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('type', 20)->default('call');           // Appointment::TYPES
            $table->string('location')->nullable();                 // địa chỉ / link họp
            $table->text('note')->nullable();
            $table->string('status', 20)->default('scheduled')->index();   // Appointment::STATUSES
            $table->text('result')->nullable();                     // kết quả sau buổi hẹn
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
