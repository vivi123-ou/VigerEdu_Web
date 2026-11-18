<?php

namespace Tests\Feature\Schools;

use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Api\Models\ApiClient;
use App\Modules\Assessment\Filament\Pages\MajorsCatalog;
use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\MajorOccupationLink;
use App\Modules\Assessment\Models\Occupation;
use App\Modules\Assessment\Models\TestSession;
use App\Modules\Assessment\Services\AssessmentService;
use App\Modules\Assessment\Services\MajorImport;
use App\Modules\Respondent\Filament\Pages\InternalReportPage;
use App\Modules\Respondent\Services\ReportAccessService;
use App\Modules\Respondent\Services\ReportForm;
use App\Modules\Schools\Filament\Pages\SchoolsCatalog;
use App\Modules\Schools\Models\School;
use App\Modules\Schools\Models\SchoolProgram;
use App\Modules\Schools\Services\ProgramImport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * Trường ↔ ngành, ngành ↔ nghề (18/11): sửa trên admin, nhập từ Excel có file mẫu; "trường có ngành này" chỉ quản trị viên
 * và tư vấn viên thấy (báo cáo nội bộ, trang Trường & vùng), khách không thấy.
 */
class SchoolProgramsTest extends TestCase
{
    use RefreshDatabase;

    private const HS01 = [
        'I' => [5, 5, 5, 5, 4, 4], 'S' => [5, 5, 4, 4, 4, 4], 'A' => [5, 4, 4, 4, 4, 4],
        'R' => [4, 4, 3, 3, 3, 3], 'C' => [4, 3, 3, 3, 3, 3], 'E' => [3, 3, 2, 2, 2, 2],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_import_matches_schools_and_majors_by_name_reports_bad_rows_and_never_duplicates(): void
    {
        $rwth = School::query()->where('short_name', 'Aachen TH')->firstOrFail();
        $psychology = Major::query()->where('name_de', 'Psychologie')->firstOrFail();
        $path = $this->xlsx([
            ['Trường', 'Ngành', 'Bậc', 'Tên chương trình', 'Ngôn ngữ', 'Link', 'Đã kiểm tra', 'Cột riêng'],
            [$rwth->short_name, 'psychologie', 'Bachelor', 'Psychologie (B.Sc.)', 'Deutsch', 'rwth-aachen.de/psychologie', 'có', 'x'],
            [$rwth->name, 'Tâm lý học', 'Master', null, 'Englisch', null, 'không', null],
            ['Trường không có', 'Psychologie', 'Bachelor', null, null, null, null, null],
            [$rwth->name, 'Ngành không có', 'Bachelor', null, null, null, null, null],
        ]);

        $import = app(ProgramImport::class);
        $mapping = $import->guess($import->headers($path));
        $this->assertSame(['Trường', 'Ngành', 'Bậc', 'Tên chương trình', 'Link'], [$mapping['school'], $mapping['major'], $mapping['degree'], $mapping['program_name'], $mapping['url']]);

        $stats = $import->import($path, $mapping);
        $this->assertSame([2, 0, 2], [$stats['created'], $stats['updated'], $stats['skipped']]);
        $this->assertStringContainsString('Dòng 4: không tìm thấy trường "Trường không có"', implode("\n", $stats['problems']));
        $this->assertStringContainsString('Dòng 5: không tìm thấy ngành', implode("\n", $stats['problems']));

        $bachelor = SchoolProgram::query()->where('degree', 'Bachelor')->sole();
        $this->assertSame([$rwth->id, $psychology->id], [$bachelor->school_id, $bachelor->major_id]);
        $this->assertSame('https://rwth-aachen.de/psychologie', $bachelor->url);
        $this->assertTrue($bachelor->is_verified);
        $this->assertSame(['Cột riêng' => 'x'], $bachelor->extra);

        $stats = $import->import($path, $mapping);
        $this->assertSame([0, 2], [$stats['created'], $stats['updated']]);
        $this->assertSame(2, SchoolProgram::query()->count(), 'Nhập lại không tạo trùng');
    }

    public function test_template_has_the_columns_of_the_import(): void
    {
        $path = app(ProgramImport::class)->template();
        $reader = new Reader;
        $reader->open($path);
        foreach ($reader->getSheetIterator() as $sheet) {
            $rows = array_map(fn ($row) => $row->toArray(), iterator_to_array($sheet->getRowIterator(), false));
            break;
        }
        $reader->close();

        $this->assertSame(array_values(SchoolProgram::IMPORTABLE), $rows[0]);
        $this->assertCount(2, $rows, 'Có một dòng ví dụ');

        $import = app(ProgramImport::class);
        $this->assertSame(array_keys(SchoolProgram::IMPORTABLE), array_keys(array_filter($import->guess($rows[0]))), 'File mẫu tự ghép đủ cột');
    }

    public function test_admin_adds_edits_and_removes_a_program_and_duplicates_are_refused(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $rwth = School::query()->where('short_name', 'Aachen TH')->firstOrFail();
        $major = Major::query()->where('name_de', 'Psychologie')->firstOrFail();
        $data = ['school_id' => $rwth->id, 'major_id' => $major->id, 'degree' => 'Bachelor', 'language' => 'Deutsch'];

        Livewire::test(SchoolsCatalog::class, ['tab' => 'chuong-trinh'])->callTableAction('create', data: $data)->assertHasNoTableActionErrors();
        Livewire::test(SchoolsCatalog::class, ['tab' => 'chuong-trinh'])->callTableAction('create', data: $data)
            ->assertHasTableActionErrors(['degree' => 'Trường này đã có ngành này ở bậc này.']);

        $program = SchoolProgram::query()->sole();
        Livewire::test(SchoolsCatalog::class, ['tab' => 'chuong-trinh'])
            ->callTableAction('edit', $program, ['school_id' => $rwth->id, 'major_id' => $major->id, 'degree' => 'Bachelor', 'is_verified' => true])
            ->assertHasNoTableActionErrors();
        $this->assertTrue($program->fresh()->is_verified);

        Livewire::test(SchoolsCatalog::class, ['tab' => 'chuong-trinh'])->callTableAction('delete', $program);
        $this->assertModelMissing($program);
        $this->assertSame(3, AdminActivity::query()->where('subject_type', 'catalog.chuong-trinh')->count());
    }

    public function test_counselor_sees_schools_of_a_major_but_cannot_edit_and_staff_cannot_see(): void
    {
        $rwth = School::query()->where('short_name', 'Aachen TH')->firstOrFail();
        $other = School::query()->where('is_hidden', false)->whereKeyNot($rwth->id)->firstOrFail();
        $psychology = Major::query()->where('name_de', 'Psychologie')->firstOrFail();
        $another = Major::query()->whereKeyNot($psychology->id)->firstOrFail();
        $mine = SchoolProgram::query()->create(['school_id' => $rwth->id, 'major_id' => $psychology->id, 'degree' => 'Bachelor']);
        $notMine = SchoolProgram::query()->create(['school_id' => $other->id, 'major_id' => $another->id, 'degree' => 'Bachelor']);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_COUNSELOR]));
        $this->get(SchoolsCatalog::getUrl(['the' => 'chuong-trinh', 'nganh' => $psychology->id]))->assertOk()->assertSee('Ngành của trường');
        Livewire::withQueryParams(['the' => 'chuong-trinh', 'nganh' => $psychology->id])->test(SchoolsCatalog::class)
            ->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$notMine])
            ->assertTableActionHidden('edit', $mine)->assertTableActionHidden('importPrograms')->assertTableActionHidden('create');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(SchoolsCatalog::getUrl(['the' => 'chuong-trinh']))->assertNotFound();
    }

    public function test_internal_report_lists_schools_of_suggested_majors_but_customer_report_does_not(): void
    {
        $session = $this->customer();
        $psychology = Major::query()->where('name_de', 'Psychologie')->firstOrFail();
        $rwth = School::query()->where('short_name', 'Aachen TH')->firstOrFail();
        SchoolProgram::query()->create(['school_id' => $rwth->id, 'major_id' => $psychology->id, 'degree' => 'Bachelor']);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_COUNSELOR]));
        $this->get(InternalReportPage::getUrl(['ma' => $session->uuid]))->assertOk()
            ->assertSee('Trường có ngành')->assertSee('Aachen TH')->assertSee('Xem cả 1 trường');

        [, $token] = ApiClient::issue('Chatbot', ['results:read']);
        $this->getJson(route('api.results.show', $session->uuid), ['X-Api-Token' => $token])->assertOk()->assertDontSee('Aachen');
        $this->getJson(route('api.catalog.show', 'programs'), ['X-Api-Token' => $token])->assertOk()
            ->assertJsonPath('items.0.school', $rwth->name)->assertJsonPath('items.0.major', $psychology->code);
    }

    public function test_major_occupations_are_edited_on_admin_and_codes_are_given_automatically(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $major = Major::query()->where('name_de', 'Psychologie')->firstOrFail();
        $occupation = Occupation::query()->whereNotIn('id', $major->occupations()->pluck('occupations.id'))->firstOrFail();
        $before = $major->occupationLinks()->count();

        $links = $major->occupationLinks()->get()->mapWithKeys(fn (MajorOccupationLink $l) => ['record-'.$l->id => ['occupation_id' => $l->occupation_id, 'link_type' => $l->link_type]])->all();
        $links['moi'] = ['occupation_id' => $occupation->id, 'link_type' => 'Liên quan'];

        Livewire::test(MajorsCatalog::class)
            ->mountTableAction('edit', $major)
            ->setTableActionData(['occupationLinks' => $links])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame($before + 1, $major->occupationLinks()->count());
        $new = MajorOccupationLink::query()->where('major_id', $major->id)->where('occupation_id', $occupation->id)->sole();
        $this->assertMatchesRegularExpression('/^LNK\d{3,}$/', $new->code);
        $this->assertSame('Admin', $new->source);
    }

    public function test_major_import_adds_new_majors_with_next_code_and_updates_existing_ones(): void
    {
        $psychology = Major::query()->where('name_de', 'Psychologie')->firstOrFail();
        $path = $this->xlsx([
            ['Tên tiếng Việt', 'Tên tiếng Đức', 'Mã Holland', 'Link nguồn', 'Đã kiểm tra'],
            ['Trồng nho và làm rượu vang', 'Weinbau und Oenologie', 'IRC, XYZ, ICR', 'www.hochschulkompass.de/weinbau', 'có'],
            ['Tâm lý học (sửa)', 'Psychologie', null, null, 'có'],
        ]);
        $expected = MajorImport::nextCode();

        $import = app(MajorImport::class);
        $stats = $import->import($path, $import->guess($import->headers($path)));

        $this->assertSame([1, 1], [$stats['created'], $stats['updated']]);
        $new = Major::query()->where('code', $expected)->sole();
        $this->assertSame(['IRC', 'ICR'], $new->holland_codes, 'Mã sai (XYZ) bị bỏ');
        $this->assertTrue($new->is_verified);
        $this->assertSame('https://www.hochschulkompass.de/weinbau', $new->source_url);
        $this->assertSame('Tâm lý học (sửa)', $psychology->fresh()->name_vi);
        $this->assertSame((bool) $psychology->source_url, $psychology->fresh()->is_verified, 'Không có link nguồn thì không đánh dấu đã kiểm tra');
    }

    /**
     * @param  list<list<string|null>>  $rows
     */
    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nganh').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    private function customer(): TestSession
    {
        $service = app(AssessmentService::class);
        $session = $service->start(null, null);
        $next = array_map(fn () => 0, self::HS01);
        $answers = [];
        foreach ($session->sessionQuestions()->get() as $item) {
            $answers[$item->question_id] = ['value' => self::HS01[$item->group][$next[$item->group]++], 'ms' => 4000];
        }
        $session = $service->finish($session, $answers);
        app(ReportForm::class)->save($session, ['ho_ten' => 'Nguyễn Minh Anh', 'ngay_sinh' => '2009-03-15', 'email' => 'minhanh@gmail.com', 'sdt_phu_huynh' => '0912345678', 'doi_tuong' => 'HS']);
        app(ReportAccessService::class)->complete($session, null);

        return $session->fresh();
    }
}
