<?php

namespace Tests\Feature;

use App\Enums\AnswerType;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\QuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_part_a_and_54_holland_questions(): void
    {
        $this->seed(QuestionSeeder::class);

        $this->assertSame(18, Question::part('A')->count(), '15 câu gốc + A16, A17, A18 tách ra');
        $this->assertSame(54, Question::part('B')->count());

        foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $group) {
            $this->assertSame(9, Question::part('B')->where('riasec', $group)->count(), "Nhóm {$group} phải có 9 câu");
        }

        $set = QuestionSet::where('code', QuestionSeeder::DEFAULT_SET_CODE)->sole();
        $this->assertTrue($set->is_default);
        $this->assertSame(AnswerType::YesNo, $set->answer_type);
        $this->assertSame(54, $set->questions()->count());
        $this->assertSame(1.0, Question::where('code', 'B01')->sole()->max_score);
    }

    public function test_seeder_is_idempotent_and_keeps_admin_edits(): void
    {
        $this->seed(QuestionSeeder::class);
        Question::where('code', 'B01')->update(['text' => 'Câu Thầy đã sửa']);
        Question::where('code', 'B02')->sole()->delete();

        $this->seed(QuestionSeeder::class);

        $this->assertSame(72, Question::withTrashed()->count());
        $this->assertSame(1, QuestionSet::count());
        $this->assertSame('Câu Thầy đã sửa', Question::where('code', 'B01')->sole()->text);
        $this->assertTrue(Question::onlyTrashed()->where('code', 'B02')->exists(), 'Câu đã xóa không bị tạo lại');
    }

    public function test_part_a_maps_to_lead_fields_of_the_data_dictionary(): void
    {
        $this->seed(QuestionSeeder::class);

        $expected = [
            'A01' => 'role', 'A02' => 'student_name', 'A03' => 'birth_year', 'A04' => 'phone', 'A05' => 'email',
            'A06' => 'parent_name', 'A16' => 'parent_phone', 'A07' => 'current_level', 'A08' => 'gpa',
            'A09' => 'english_level', 'A17' => 'german_level', 'A10' => 'desired_level', 'A11' => 'country',
            'A18' => 'region', 'A12' => 'budget', 'A13' => 'timing', 'A14' => 'interested_major', 'A15' => 'consent',
        ];

        $actual = Question::part('A')->pluck('lead_field', 'code')->all();

        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    public function test_option_values_match_standard_choice_list(): void
    {
        $this->seed(QuestionSeeder::class);
        $choices = json_decode((string) file_get_contents(base_path('docs/lua_chon_chuan.json')), true);

        $lists = ['A01' => 'role', 'A07' => 'current_level', 'A09' => 'english_level', 'A17' => 'german_level',
            'A10' => 'desired_level', 'A12' => 'budget', 'A13' => 'timing'];

        foreach ($lists as $code => $list) {
            $this->assertSame(
                array_column($choices[$list], 'value'),
                array_column(Question::where('code', $code)->sole()->options, 'value'),
                "Lựa chọn câu {$code} phải đúng danh sách {$list}",
            );
        }
    }

    public function test_required_questions_and_parent_condition(): void
    {
        $this->seed(QuestionSeeder::class);

        $this->assertEqualsCanonicalizing(['A01', 'A15'], Question::part('A')->where('is_required', true)->pluck('code')->all());
        $this->assertSame(AnswerType::Consent, Question::where('code', 'A15')->sole()->answer_type);
        $this->assertSame(['question' => 'A01', 'in' => ['HS']], Question::where('code', 'A06')->sole()->show_if);
        $this->assertSame(['question' => 'A01', 'in' => ['HS']], Question::where('code', 'A16')->sole()->show_if);
    }

    public function test_admin_seeder_creates_admin_once_without_overwriting_password(): void
    {
        config(['holland.admin.username' => 'SPN', 'holland.admin.password' => 'mat-khau-ban-dau']);

        $this->seed(AdminSeeder::class);
        $admin = User::where('username', 'SPN')->sole();
        $this->assertTrue($admin->isAdmin());

        $admin->update(['password' => 'mat-khau-moi']);
        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::count());
        $this->post(route('admin.login.store'), ['username' => 'SPN', 'password' => 'mat-khau-moi'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_seeder_skips_when_password_not_configured(): void
    {
        config(['holland.admin.password' => null]);

        $this->seed(AdminSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_full_database_seeder_runs_twice_without_duplicates(): void
    {
        config(['holland.admin.username' => 'SPN', 'holland.admin.password' => 'mat-khau']);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(72, Question::count());
        $this->assertSame(1, User::count());
    }
}
