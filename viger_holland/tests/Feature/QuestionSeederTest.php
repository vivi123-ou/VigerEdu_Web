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

        $this->assertSame(22, Question::part('A')->count(), '15 câu gốc + A16–A18 tách ra + A19–A22 mới');
        $this->assertSame(21, Question::part('A')->active()->count(), 'A03 năm sinh tắt (tự điền từ ngày sinh)');
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

        $this->assertSame(76, Question::withTrashed()->count());
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
            'A20' => 'current_school', 'A21' => 'province',
            // Trường mới ngoài lead/1 gửi trong extra_fields
            'A19' => 'extra_fields.birth_date', 'A22' => 'extra_fields.parent_email',
        ];

        $actual = Question::part('A')->get()->mapWithKeys(fn (Question $question) => [$question->code => $question->crmKey()])->all();

        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    public function test_region_question_uses_active_regions_from_regions_json(): void
    {
        $this->seed(QuestionSeeder::class);

        $regions = json_decode((string) file_get_contents(database_path('seeders/data/holland/regions.json')), true);
        $question = Question::where('code', 'A18')->sole();
        $values = array_column($question->options, 'value');

        $this->assertCount(17, $regions, '16 bang Đức + Wien');
        $this->assertSame(AnswerType::Single, $question->answer_type);
        $this->assertSame('region', $question->lead_field);
        $this->assertCount(16, $values, 'Wien đang tắt nên không hiện');
        $this->assertContains('Hessen', $values, 'Gửi CRM bằng tên bang');
        $this->assertContains('Thüringen', $values);
        $this->assertNotContains('Wien', $values);
    }

    public function test_province_list_has_34_provinces_plus_abroad(): void
    {
        $this->seed(QuestionSeeder::class);

        $options = Question::where('code', 'A21')->sole()->options;

        $this->assertCount(35, $options);
        $this->assertContains('Hà Nội', array_column($options, 'value'));
        $this->assertContains('Ngoài Việt Nam', array_column($options, 'value'));
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

        $this->assertEqualsCanonicalizing(
            ['A02', 'A19', 'A05', 'A04', 'A01', 'A07', 'A20', 'A21', 'A15'],
            Question::part('A')->where('is_required', true)->pluck('code')->all(),
            'Họ tên, ngày sinh, email, SĐT, vai trò, lớp, trường, tỉnh, đồng ý',
        );
        $this->assertSame(AnswerType::Consent, Question::where('code', 'A15')->sole()->answer_type);
        foreach (['A06', 'A16', 'A22'] as $code) {
            $this->assertSame(['question' => 'A01', 'in' => ['HS']], Question::where('code', $code)->sole()->show_if, "{$code} chỉ hiện khi học sinh tự làm");
        }
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

        $this->assertSame(76, Question::count());
        $this->assertSame(1, User::count());
    }
}
