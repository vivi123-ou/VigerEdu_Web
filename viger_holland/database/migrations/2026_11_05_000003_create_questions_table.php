<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Câu hỏi Phần A (form thông tin khách, động) và Phần B (câu Holland, thuộc một bộ câu).
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->char('part', 1)->index();
            $table->foreignId('question_set_id')->nullable()->constrained()->nullOnDelete();
            $table->text('text');
            $table->string('help_text')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('form', 50)->nullable();
            $table->string('answer_type', 20);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('show_if')->nullable();
            $table->string('lead_field', 50)->nullable();
            $table->string('extra_field_key', 50)->nullable();
            $table->char('riasec', 1)->nullable()->index();
            $table->decimal('max_score', 6, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['part', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
