<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'relationship', 'occupation', 'address',
        'student_name', 'student_dob', 'student_gender', 'student_school', 'education_level',
        'gpa', 'german_level', 'english_level', 'desired_major', 'desired_state',
        'program_interest', 'budget', 'expected_time', 'profile_status',
        'holland_r', 'holland_i', 'holland_a', 'holland_s', 'holland_e', 'holland_c',
    ];

    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // I. Phụ huynh (full_name / phone / email đã có = liên hệ chính)
            $table->string('relationship', 50)->nullable()->after('full_name');
            $table->string('occupation')->nullable()->after('email');
            $table->string('address')->nullable()->after('occupation');

            // II. Học sinh
            $table->string('student_name')->nullable();
            $table->date('student_dob')->nullable();
            $table->string('student_gender', 10)->nullable();
            $table->string('student_school')->nullable();
            $table->string('education_level', 50)->nullable();
            $table->decimal('gpa', 4, 2)->nullable();
            $table->string('german_level', 20)->nullable();
            $table->string('english_level', 50)->nullable();
            $table->string('desired_major')->nullable();
            $table->string('desired_state', 50)->nullable();

            // III. Nhu cầu tư vấn
            $table->string('program_interest', 100)->nullable();
            $table->string('budget', 100)->nullable();
            $table->string('expected_time', 100)->nullable();
            $table->string('profile_status', 50)->nullable();

            // IV. Holland (0–100)
            foreach (['r', 'i', 'a', 's', 'e', 'c'] as $l) {
                $table->unsignedTinyInteger("holland_{$l}")->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', fn (Blueprint $table) => $table->dropColumn(self::COLUMNS));
    }
};
