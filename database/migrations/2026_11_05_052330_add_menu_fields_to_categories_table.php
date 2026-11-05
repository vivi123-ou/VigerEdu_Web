<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'menu_group')) {
                $table->string('menu_group', 50)->nullable()->after('slug');
            }
            if (! Schema::hasColumn('categories', 'is_in_menu')) {
                $table->boolean('is_in_menu')->default(false)->after('menu_group');
            }
            if (! Schema::hasColumn('categories', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_in_menu');
            }
            if (! Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable()->after('sort_order');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $columns = [];
            foreach (['menu_group', 'is_in_menu', 'sort_order', 'description'] as $column) {
                if (Schema::hasColumn('categories', $column)) {
                    $columns[] = $column;
                }
            }
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
