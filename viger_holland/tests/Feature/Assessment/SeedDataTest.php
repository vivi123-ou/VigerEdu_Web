<?php

namespace Tests\Feature\Assessment;

use App\Modules\Access\Models\Role;
use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\RiasecType;
use App\Modules\Assessment\Models\TestConfigVersion;
use App\Modules\Respondent\Models\FormField;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\QuestionBankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * H1: dữ liệu ban đầu nạp đủ, đúng cấu trúc của Thầy, và chạy lại không trùng, không ghi đè chỗ admin đã sửa.
 */
class SeedDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_twice_gives_the_same_counts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $first = $this->counts();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($first, $this->counts());
        $this->assertSame([
            'roles' => 3, 'permissions' => 15, 'riasec_types' => 6, 'riasec_pairs' => 15, 'pair_major' => 30,
            'third_letters' => 6, 'major_groups' => 7, 'subjects' => 9, 'majors' => 46, 'environments' => 14,
            'occupations' => 51, 'major_occupation' => 53, 'question_facets' => 60, 'questions' => 300,
            'test_config_versions' => 1, 'form_fields' => 8,
        ], $first);
    }

    public function test_question_bank_matches_the_structure_of_the_300_item_library(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $group) {
            $this->assertSame(50, Question::where('group', $group)->count(), "Nhóm {$group} phải có 50 câu");
            $this->assertSame(10, DB::table('question_facets')->where('group', $group)->count(), "Nhóm {$group} phải có 10 chủ đề");
            $this->assertSame(
                ['A' => 10, 'B' => 25, 'C' => 15],
                Question::where('group', $group)->selectRaw('priority_tier, count(*) as total')->groupBy('priority_tier')->orderBy('priority_tier')->pluck('total', 'priority_tier')->map(fn ($n) => (int) $n)->all(),
            );
        }

        $this->assertSame(300, Question::approved()->count(), 'Thầy đã chọn bộ 300 câu nên nạp ở trạng thái đã duyệt');
        $this->assertSame(['A' => 5.0, 'B' => 3.0, 'C' => 1.0], [
            'A' => (float) Question::where('priority_tier', 'A')->value('selection_weight'),
            'B' => (float) Question::where('priority_tier', 'B')->value('selection_weight'),
            'C' => (float) Question::where('priority_tier', 'C')->value('selection_weight'),
        ]);

        $stillSecondPerson = Question::pluck('text', 'code')->filter(fn ($text) => preg_match('/(?<!\p{L})[Ee]m(?!\p{L})/u', $text));
        $this->assertEmpty($stillSecondPerson, 'Còn câu viết "em": '.$stillSecondPerson->keys()->implode(', '));
        $this->assertSame('Tôi thích lắp một mô hình cơ khí từ các bộ phận rời.', Question::where('code', 'R001')->value('text'));
    }

    public function test_first_person_rewrite_only_touches_the_standalone_word(): void
    {
        $this->assertSame('Tôi thích xem phim.', QuestionBankSeeder::firstPerson('Em thích xem phim.'));
        $this->assertSame('Nếu có cơ hội, tôi muốn thử.', QuestionBankSeeder::firstPerson('Nếu có cơ hội, em muốn thử.'));
        $this->assertSame('Tôi thích kem và chè.', QuestionBankSeeder::firstPerson('Em thích kem và chè.'));
        $this->assertSame('Tôi thích Emma Watson, kem, xem, đem.', QuestionBankSeeder::firstPerson('Em thích Emma Watson, kem, xem, đem.'));
    }

    public function test_pairs_keep_major_order_and_form_fields_map_to_crm_keys(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(['MAJ002', 'MAJ001'], RiasecPair::where('pair', 'RI')->first()->majors->pluck('code')->all());
        $this->assertSame(['RIE'], Major::where('code', 'MAJ001')->value('holland_codes'));

        $this->assertSame([
            'ho_ten' => 'student_name', 'ngay_sinh' => 'birth_date', 'email' => 'email', 'sdt_phu_huynh' => 'parent_phone', 'so_dien_thoai' => 'phone',
            'truong' => 'current_school', 'tinh' => 'province', 'doi_tuong' => 'role',
        ], FormField::orderBy('sort_order')->pluck('crm_key', 'key')->all());

        $this->assertSame(['ho_ten', 'ngay_sinh', 'email', 'sdt_phu_huynh', 'doi_tuong'], FormField::where('is_required', true)->orderBy('sort_order')->pluck('key')->all());
        $this->assertSame(['HS', 'PH', 'KHAC'], array_column(FormField::where('key', 'doi_tuong')->value('options'), 'value'));
    }

    public function test_group_and_pair_explanations_are_loaded_and_never_overwrite_admin_edits(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(6, RiasecType::query()->whereNotNull('description')->count());
        $this->assertSame(15, RiasecPair::query()->whereNotNull('description')->count());
        $this->assertSame(6, DB::table('third_letters')->whereNotNull('description')->whereNotNull('specialty_vi')->count());
        $this->assertSame(0, DB::table('occupations')->whereNull('name_vi')->count(), 'Nghề nào cũng có tên tiếng Việt');
        $this->assertStringStartsWith('Bạn thích quan sát, đặt câu hỏi “vì sao”', RiasecType::where('code', 'I')->value('description'));

        // Database cũ chưa có đoạn giải thích: chạy seeder thì được điền; đoạn admin đã sửa thì giữ nguyên.
        RiasecType::where('code', 'R')->update(['description' => null]);
        RiasecPair::where('pair', 'IS')->update(['description' => 'Đoạn admin tự viết']);
        $this->seed(DatabaseSeeder::class);

        $this->assertStringStartsWith('Bạn thích làm việc bằng tay', RiasecType::where('code', 'R')->value('description'));
        $this->assertSame('Đoạn admin tự viết', RiasecPair::where('pair', 'IS')->value('description'));
    }

    public function test_reseeding_does_not_overwrite_what_admin_changed(): void
    {
        $this->seed(DatabaseSeeder::class);

        Question::where('code', 'R001')->update(['text' => 'Câu admin đã sửa', 'review_status' => Question::STATUS_SUSPENDED]);
        Question::where('code', 'R002')->first()->delete();
        FormField::where('key', 'truong')->update(['label' => 'Trường đang học', 'is_visible' => false]);
        Role::where('code', 'COUNSELOR')->first()->permissions()->detach();
        Major::where('code', 'MAJ001')->first()->delete();
        TestConfigVersion::create(['settings' => ['items_per_page' => 4], 'note' => 'Admin đổi']);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame('Câu admin đã sửa', Question::where('code', 'R001')->value('text'));
        $this->assertSame(Question::STATUS_SUSPENDED, Question::where('code', 'R001')->value('review_status'));
        $this->assertNull(Question::where('code', 'R002')->first(), 'Câu đã cho vào thùng rác không được nạp lại');
        $this->assertSame(300, Question::withTrashed()->count());
        $this->assertSame('Trường đang học', FormField::where('key', 'truong')->value('label'));
        $this->assertSame(0, Role::where('code', 'COUNSELOR')->first()->permissions()->count());
        $this->assertSame(46, Major::withTrashed()->count());
        $this->assertSame(2, TestConfigVersion::count());
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $tables = ['roles', 'permissions', 'riasec_types', 'riasec_pairs', 'pair_major', 'third_letters', 'major_groups', 'subjects',
            'majors', 'environments', 'occupations', 'major_occupation', 'question_facets', 'questions', 'test_config_versions', 'form_fields'];

        return collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()])->all();
    }
}
