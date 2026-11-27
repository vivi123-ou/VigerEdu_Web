<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // categories.id là BIGINT UNSIGNED ($table->id()), nên parent_id phải unsignedBigInteger để tạo khóa ngoại.
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->change();
            } else {
                $table->unsignedBigInteger('parent_id')->nullable()->after('menu_group');
            }
            if (! Schema::hasColumn('categories', 'url')) {
                $table->string('url')->nullable()->after('parent_id');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            if (Schema::hasColumn('categories', 'url')) {
                $table->dropColumn('url');
            }
        });
    }
};
