<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Câu hỏi kiểu Google Form:
     * - display: cách hiện (buttons = nút / ô số, stars = ngôi sao, dropdown = danh sách sổ xuống); trống = theo bộ / tự động.
     * - config: tùy chọn riêng của câu (min, max, min_select, max_select, allow_other, private).
     * Bộ câu có display mặc định cho các câu thang mức.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('display', 20)->nullable()->after('options');
            $table->json('config')->nullable()->after('display');
        });

        Schema::table('question_sets', function (Blueprint $table) {
            $table->string('display', 20)->nullable()->after('scale');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['display', 'config']);
        });

        Schema::table('question_sets', function (Blueprint $table) {
            $table->dropColumn('display');
        });
    }
};
