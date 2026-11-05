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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('eyebrow')->nullable();
            $table->text('desc')->nullable();
            $table->string('image', 1000);
            $table->string('alt')->nullable();
            $table->string('button_primary_label')->nullable();
            $table->string('button_primary_url')->nullable();
            $table->string('button_secondary_label')->nullable();
            $table->string('button_secondary_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
