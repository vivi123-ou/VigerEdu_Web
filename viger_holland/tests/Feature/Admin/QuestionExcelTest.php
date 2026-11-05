<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivity;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use App\Services\QuestionWorkbook;
use App\Support\ChoiceList;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;
use ZipArchive;

class QuestionExcelTest extends TestCase
{
    use RefreshDatabase;

    private QuestionSet $set;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create());
        $this->set = QuestionSet::where('code', 'thpt')->sole();
        Storage::fake('local');
    }

    public function test_export_has_cau_hoi_tab_with_same_columns_as_sample_sheet(): void
    {
        $path = app(QuestionWorkbook::class)->export($this->set);
        $sheets = $this->readWorkbook($path);

        $this->assertSame(['CAU_HOI', 'HUONG_DAN'], array_keys($sheets));
        $this->assertSame($this->sampleColumns(), $sheets['CAU_HOI'][0], 'Đúng tên và thứ tự cột tab CAU_HOI của Google Sheet mẫu');
        $this->assertCount(1 + Question::part('A')->count() + 54, $sheets['CAU_HOI']);

        $b01 = collect($sheets['CAU_HOI'])->first(fn (array $row) => $row[0] === 'B01');
        $this->assertSame(['B01', 'B', 'Holland'], array_slice($b01, 0, 3));
        $this->assertSame('Đúng / Không', $b01[4]);
        $this->assertSame('Đúng = 1 | Không = 0', $b01[5]);
        $this->assertSame('R', $b01[8]);

        $a02 = collect($sheets['CAU_HOI'])->first(fn (array $row) => $row[0] === 'A02');
        $this->assertSame('student_name', $a02[6]);

        $zip = new ZipArchive;
        $zip->open($path);
        $this->assertStringContainsString('<dataValidation type="list"', (string) $zip->getFromName('xl/worksheets/sheet1.xml'), 'Có ô chọn sổ xuống');
        $zip->close();
    }

    public function test_export_download_route(): void
    {
        $this->get(route('admin.excel.export', $this->set))->assertOk()->assertDownload('cau-hoi-thpt-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_preview_with_bad_rows_reports_line_numbers_and_writes_nothing(): void
    {
        $file = $this->upload([
            ['B01', 'B', 'Holland', 'Câu sửa', 'Đúng / Không', '', '', '', 'R', 1, 1, '', ''],
            ['', 'B', 'Holland', 'Câu mới thiếu nhóm', 'Đúng / Không', '', '', '', '', 2, 1, '', ''],
            ['B02', 'B', 'Holland', 'Câu trùng', 'Đúng / Không', '', '', '', 'I', 3, 1, '', ''],
            ['B02', 'B', 'Holland', 'Câu trùng lần 2', 'Đúng / Không', '', '', '', 'I', 4, 1, '', ''],
            ['', 'A', 'Thu thập', 'Câu A sai trường', 'Chữ', '', 'DS_KHACH.Ghi chú', '', 'ALL', 5, 1, '', ''],
        ]);

        $response = $this->post(route('admin.excel.preview'), ['question_set_id' => $this->set->id, 'file' => $file])->assertOk();

        $response->assertSee('chưa thể nhập')
            ->assertSee('Thiếu nhóm RIASEC')
            ->assertSee('Mã câu B02 bị trùng với dòng 4.')
            ->assertSee('không phải trường CRM')
            ->assertDontSee('Xác nhận nhập');

        $rows = collect($response->viewData('preview')['rows'])->keyBy('line');
        $this->assertNotEmpty($rows[3]['errors'], 'Dòng 3 trong Excel (dòng 1 là tiêu đề) báo thiếu nhóm');
        $this->assertSame([], $rows[2]['errors']);
        $this->assertSame(3, $response->viewData('preview')['counts']['errors']);

        $this->assertNotSame('Câu sửa', Question::where('code', 'B01')->value('text'), 'Xem trước không ghi gì');
    }

    public function test_valid_file_updates_creates_and_only_deactivates_missing_questions(): void
    {
        $rows = Question::part('A')->ordered()->get()->map(fn (Question $q) => [
            $q->code, 'A', 'Thu thập', $q->text, $q->answer_type->label(), ChoiceList::format($q->options, ' | '),
            (string) $q->crmKey(), '', 'ALL', $q->sort_order, $q->is_active ? 1 : 0, '', $q->is_required ? 'Có' : 'Không',
        ])->all();
        $rows[] = ['', 'A', 'Thu thập', 'Trường chuyên?', 'Có / Không', '', 'extra_fields.truong_chuyen', '', 'ALL', 170, 1, '', 'Không'];
        $rows[] = ['B01', 'B', 'Holland', 'Tôi thích sửa xe máy', 'Đúng / Không', 'Đúng = 1 | Không = 0', '', '', 'R', 1, 1, '', ''];
        $rows[] = ['B02', 'B', 'Holland', 'Người thích quan sát, tìm hiểu nguyên nhân', 'Đúng / Không', '', '', '', 'I', 2, 1, '', ''];
        $rows[] = ['', 'B', 'Holland', 'Câu mới nhóm C', 'Đúng / Không', '', '', '', 'C', 60, 1, '', ''];

        $file = $this->upload($rows);
        $response = $this->post(route('admin.excel.preview'), ['question_set_id' => $this->set->id, 'file' => $file])->assertOk()->assertSee('Xác nhận nhập');
        $preview = $response->viewData('preview');
        $this->assertSame(['create' => 2, 'update' => 1, 'unchanged' => count($rows) - 3, 'deactivate' => 52, 'errors' => 0], $preview['counts']);

        $this->post(route('admin.excel.import'), [
            'token' => $response->viewData('token'), 'question_set_id' => $this->set->id, 'file_name' => 'cau-hoi.xlsx',
        ])->assertRedirect(route('admin.question-sets.show', $this->set));

        $this->assertSame('Tôi thích sửa xe máy', Question::where('code', 'B01')->value('text'));
        $this->assertSame('truong_chuyen', Question::where('text', 'Trường chuyên?')->value('extra_field_key'));
        $this->assertSame($this->set->id, Question::where('text', 'Câu mới nhóm C')->value('question_set_id'));
        $this->assertSame('B55', Question::where('text', 'Câu mới nhóm C')->value('code'));

        $this->assertSame(55, Question::part('B')->count(), 'Không xóa câu thiếu');
        $this->assertFalse(Question::where('code', 'B03')->value('is_active'), 'Câu thiếu trong file bị tắt');
        $this->assertTrue(Question::where('code', 'B02')->value('is_active'));
        $this->assertTrue(AdminActivity::where('action', 'import')->exists(), 'Lưu lịch sử nhập');
        $this->get(route('admin.excel.index'))->assertSee('cau-hoi.xlsx');
    }

    public function test_exported_file_imports_back_without_changes(): void
    {
        $path = app(QuestionWorkbook::class)->export($this->set);
        $preview = app(QuestionWorkbook::class)->preview($path, $this->set);

        $this->assertSame([], $preview['file_errors']);
        $this->assertSame(0, $preview['counts']['errors'], json_encode(collect($preview['rows'])->where('errors', '!=', [])->values(), JSON_UNESCAPED_UNICODE));
        $this->assertSame(0, $preview['counts']['create']);
        $this->assertSame(0, $preview['counts']['update']);
        $this->assertSame(0, $preview['counts']['deactivate']);
    }

    public function test_file_without_cau_hoi_tab_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'x').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Sheet1');
        $writer->addRow(Row::fromValues(['abc']));
        $writer->close();

        $file = new UploadedFile($path, 'sai.xlsx', null, null, true);
        $this->post(route('admin.excel.preview'), ['question_set_id' => $this->set->id, 'file' => $file])
            ->assertOk()->assertSee('File không có tab CAU_HOI');
    }

    /**
     * @return list<string>
     */
    private function sampleColumns(): array
    {
        return $this->readWorkbook(base_path('docs/3_Mau_GoogleSheet_Viger_Holland_v2.xlsx'), 1)['CAU_HOI'][0];
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function upload(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cauhoi').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('CAU_HOI');
        $writer->addRow(Row::fromValues(QuestionWorkbook::COLUMNS));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile($path, 'cau-hoi.xlsx', null, null, true);
    }

    /**
     * @return array<string, list<list<mixed>>>
     */
    private function readWorkbook(string $path, ?int $limit = null): array
    {
        $reader = new Reader;
        $reader->open($path);
        $sheets = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $sheets[$sheet->getName()][] = $row->toArray();
                if ($limit !== null && count($sheets[$sheet->getName()]) >= $limit) {
                    break;
                }
            }
        }

        $reader->close();

        return $sheets;
    }
}
