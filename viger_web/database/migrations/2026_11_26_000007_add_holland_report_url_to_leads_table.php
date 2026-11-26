<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Link bài làm trên trang quản trị Holland (quiz.view_url trong gói lead/2)
            $table->string('holland_report_url', 500)->nullable()->after('holland_result');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('holland_report_url');
        });
    }
};
