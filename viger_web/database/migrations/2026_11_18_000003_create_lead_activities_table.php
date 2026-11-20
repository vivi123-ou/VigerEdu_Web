<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DB thật có users.id là BIGINT có dấu => khoá ngoại phải cùng kiểu (xem migration create_leads_table)
        $usersIdUnsigned = str_contains(
            strtolower(collect(Schema::getColumns('users'))->firstWhere('name', 'id')['type'] ?? ''),
            'unsigned'
        );

        Schema::create('lead_activities', function (Blueprint $table) use ($usersIdUnsigned) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->bigInteger('user_id', unsigned: $usersIdUnsigned)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('type', 20)->default('note')->index();   // LeadActivity::TYPES
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
