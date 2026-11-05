<?php

namespace Tests\Feature\Admin;

use App\Enums\AnswerType;
use App\Models\Attempt;
use App\Models\OutboxMessage;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use App\Services\QuestionWorkbook;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * Câu hỏi kiểu Google Form: mỗi câu một kiểu (Đúng/Không, thang mức, ngôi sao, chọn 1, chọn nhiều), không bắt buộc,
 * và form thông tin khách có đoạn văn, đánh giá, "Khác…", giới hạn số, thông tin riêng tư. Tất cả làm trên admin.
 */
class QuestionTypesTest extends TestCase
{
    use RefreshDatabase;

    private QuestionSet $set;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutDefer();
        Mail::fake();
        $this->actingAs(User::factory()->create());

        $this->post(route('admin.question-sets.store'), ['name' => 'Bộ trộn kiểu', 'code' => 'tron', 'answer_type' => 'yes_no', 'per_page' => 10, 'is_active' => '1']);
        $this->set = QuestionSet::where('code', 'tron')->sole();
    }

    /**
     * Tạo bộ trộn 4 kiểu câu qua trang admin. Trả về mã câu theo thứ tự tạo.
     *
     * @return array{yesno: string, stars: string, single: string, multi: string}
     */
    private function createMixedQuestions(): array
    {
        $store = fn (array $data) => $this->post(route('admin.question-sets.questions.store', $this->set), [...$data, 'is_active' => '1'])->assertSessionHasNoErrors();

        $store(['text' => 'Tôi thích sửa xe', 'answer_type' => 'yes_no', 'riasec' => 'R', 'is_required' => '1']);
        $store(['text' => 'Tôi thích thí nghiệm', 'answer_type' => 'likert5', 'riasec' => 'I', 'display' => 'stars', 'is_required' => '1']);
        $store(['text' => 'Giờ ra chơi bạn thường làm gì?', 'answer_type' => 'single', 'is_required' => '1', 'choices' => [
            ['label' => 'Vẽ tranh', 'group' => 'A', 'points' => '1'],
            ['label' => 'Giúp bạn học bài', 'group' => 'S', 'points' => '1'],
            ['label' => 'Không làm gì', 'group' => '', 'points' => ''],
        ]]);
        $store(['text' => 'Bạn thích hoạt động nào?', 'answer_type' => 'multi', 'max_select' => '2', 'choices' => [
            ['label' => 'Bán hàng', 'group' => 'E', 'points' => ''],
            ['label' => 'Sắp xếp sổ sách', 'group' => 'C', 'points' => '2'],
            ['label' => 'Tổ chức sự kiện', 'group' => 'E', 'points' => '1'],
        ]]);

        $codes = $this->set->questions()->orderBy('code')->pluck('code')->all();

        return ['yesno' => $codes[0], 'stars' => $codes[1], 'single' => $codes[2], 'multi' => $codes[3]];
    }

    public function test_admin_creates_choice_questions_with_groups_and_points(): void
    {
        $codes = $this->createMixedQuestions();

        $single = Question::where('code', $codes['single'])->sole();
        $this->assertSame(AnswerType::Single, $single->answer_type);
        $this->assertNull($single->riasec);
        $this->assertSame(['value' => '3', 'label' => 'Không làm gì', 'group' => null, 'points' => 0], $single->choices()[2]);
        $this->assertSame(['A', 'S'], $single->scoredGroups());

        $multi = Question::where('code', $codes['multi'])->sole();
        $this->assertSame(1, $multi->choices()[0]['points'], 'Để trống điểm = 1');
        $this->assertSame(2, $multi->configValue('max_select'));
        $this->assertFalse($multi->is_required);

        $this->assertSame('stars', Question::where('code', $codes['stars'])->value('display'));

        $this->get(route('admin.question-sets.show', $this->set))->assertOk()->assertSee('Chọn nhiều')->assertSee('không bắt buộc');
        $this->get(route('admin.holland-questions.edit', $single))->assertOk()->assertSee('Vẽ tranh')->assertSee('+ Thêm lựa chọn');
    }

    public function test_choice_question_needs_scored_option(): void
    {
        $this->post(route('admin.question-sets.questions.store', $this->set), [
            'text' => 'Câu không tính điểm', 'answer_type' => 'single',
            'choices' => [['label' => 'Một', 'group' => ''], ['label' => 'Hai', 'group' => '']],
        ])->assertSessionHasErrors(['choices' => 'Cần ít nhất một lựa chọn có nhóm Holland để câu này được tính điểm.']);
    }

    public function test_customer_sees_each_type_and_scoring_follows_choices(): void
    {
        $codes = $this->createMixedQuestions();
        auth()->logout();

        $this->get(route('test.show', ['bo' => 'tron']))->assertOk()
            ->assertSee('class="stars"', false)
            ->assertSee('name="answers['.$codes['multi'].'][]"', false)
            ->assertSee('type="checkbox"', false)
            ->assertSee('Giúp bạn học bài')
            ->assertSee('(không bắt buộc)');

        // Chọn quá số ô tối đa → báo lỗi
        $base = ['bo' => 'tron', 'info' => $this->info()];
        $this->post(route('test.submit'), [...$base, 'answers' => [
            $codes['yesno'] => '1', $codes['stars'] => '3', $codes['single'] => '1', $codes['multi'] => ['1', '2', '3'],
        ]])->assertSessionHasErrors(['answers.'.$codes['multi'] => 'Chỉ chọn tối đa 2 ô.']);

        // Hợp lệ: câu chọn nhiều tick "Sắp xếp sổ sách" (C 2 điểm) + "Bán hàng" (E 1 điểm)
        $this->post(route('test.submit'), [...$base, 'answers' => [
            $codes['yesno'] => '1', $codes['stars'] => '3', $codes['single'] => '1', $codes['multi'] => ['1', '2'],
        ]])->assertSessionHasNoErrors();

        $percent = Attempt::sole()->result['percent'];
        $this->assertEqualsWithDelta(1.0, $percent['R'], 0.0001);
        $this->assertEqualsWithDelta(0.75, $percent['I'], 0.0001, 'Thang 5 mức 0–4: chọn 3');
        $this->assertEqualsWithDelta(1.0, $percent['A'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $percent['S'], 0.0001);
        $this->assertEqualsWithDelta(0.5, $percent['E'], 0.0001, 'Đạt 1 / tối đa 2');
        $this->assertEqualsWithDelta(1.0, $percent['C'], 0.0001);

        $answers = OutboxMessage::sole()->payload['quiz']['answers'];
        $multi = collect($answers)->firstWhere('code', $codes['multi']);
        $this->assertNull($multi['group']);
        $this->assertSame('Bán hàng, Sắp xếp sổ sách', $multi['answer']);
        $this->assertSame([['group' => 'E', 'value' => 1, 'max' => 2], ['group' => 'C', 'value' => 2, 'max' => 2]], $multi['scores']);
    }

    public function test_optional_question_can_be_skipped_and_is_not_counted(): void
    {
        $codes = $this->createMixedQuestions();
        auth()->logout();

        $this->post(route('test.submit'), ['bo' => 'tron', 'info' => $this->info(), 'answers' => [
            $codes['yesno'] => '1', $codes['stars'] => '4', $codes['single'] => '2',
        ]])->assertSessionHasNoErrors();

        $attempt = Attempt::sole();
        $this->assertCount(3, $attempt->answers);
        $this->assertEqualsWithDelta(0.0, $attempt->result['percent']['E'], 0.0001, 'Nhóm không có câu nào được tính → 0');
    }

    public function test_excel_round_trip_keeps_choice_groups(): void
    {
        $this->createMixedQuestions();
        $workbook = app(QuestionWorkbook::class);

        $preview = $workbook->preview($workbook->export($this->set), $this->set);

        $this->assertSame(0, $preview['counts']['errors'], json_encode(collect($preview['rows'])->where('errors', '!=', [])->values(), JSON_UNESCAPED_UNICODE));
        $this->assertSame(0, $preview['counts']['update']);
        $this->assertSame(0, $preview['counts']['create']);
    }

    public function test_excel_import_reads_scored_choices(): void
    {
        $workbook = app(QuestionWorkbook::class);
        $rows = $this->setRowsFromExport($workbook);
        $rows[] = ['', 'B', 'Holland', 'Bạn thích môn nào?', 'Chọn nhiều', 'Vật lý = I:1 | Mỹ thuật = A:2 | Thể dục', '', '', '', 50, 1, '', 'Không'];

        $path = $this->writeSheet($rows);
        $preview = $workbook->preview($path, $this->set);
        $this->assertSame(0, $preview['counts']['errors'], json_encode(collect($preview['rows'])->where('errors', '!=', [])->values(), JSON_UNESCAPED_UNICODE));

        $workbook->apply($preview, $this->set, 'test.xlsx');

        $question = Question::where('text', 'Bạn thích môn nào?')->sole();
        $this->assertSame(AnswerType::Multi, $question->answer_type);
        $this->assertFalse($question->is_required);
        $this->assertSame([
            ['value' => '1', 'label' => 'Vật lý', 'group' => 'I', 'points' => 1],
            ['value' => '2', 'label' => 'Mỹ thuật', 'group' => 'A', 'points' => 2],
            ['value' => '3', 'label' => 'Thể dục', 'group' => null, 'points' => 0],
        ], $question->choices());
    }

    public function test_info_form_new_types_other_option_number_limits_and_private(): void
    {
        $store = fn (array $data) => $this->post(route('admin.info-questions.store'), [...$data, 'is_active' => '1'])->assertSessionHasNoErrors();

        $store(['text' => 'Bạn biết Viger qua đâu?', 'answer_type' => 'single', 'options_text' => "Facebook\nBạn bè", 'allow_other' => '1', 'crm_target' => '_custom', 'extra_field_key' => 'nguon_biet']);
        $store(['text' => 'Bạn mong đợi gì?', 'answer_type' => 'textarea', 'crm_target' => '_custom', 'extra_field_key' => 'mong_doi']);
        $store(['text' => 'Bạn tự tin với tiếng Đức mức nào?', 'answer_type' => 'rating', 'crm_target' => '_custom', 'extra_field_key' => 'tu_tin']);
        $store(['text' => 'Số anh chị em', 'answer_type' => 'number', 'min' => '0', 'max' => '10', 'is_private' => '1', 'crm_target' => '_custom', 'extra_field_key' => 'anh_chi_em']);

        $codes = Question::whereIn('extra_field_key', ['nguon_biet', 'mong_doi', 'tu_tin', 'anh_chi_em'])->pluck('code', 'extra_field_key');
        $this->assertTrue(Question::where('code', $codes['anh_chi_em'])->sole()->isPrivate());
        auth()->logout();

        $this->get(route('test.show'))->assertOk()
            ->assertSee('<textarea', false)
            ->assertSee('name="info_other['.$codes['nguon_biet'].']"', false)
            ->assertSee('class="stars"', false);

        $answers = $this->hollandAnswers();
        $info = [...$this->info(), $codes['nguon_biet'] => Question::OTHER, $codes['mong_doi'] => 'Muốn học kỹ thuật', $codes['tu_tin'] => '4', $codes['anh_chi_em'] => '12'];

        $this->post(route('test.submit'), ['answers' => $answers, 'info' => $info])
            ->assertSessionHasErrors(['info.'.$codes['nguon_biet'] => 'Vui lòng ghi rõ ở ô "Khác".', 'info.'.$codes['anh_chi_em'] => 'Số lớn nhất là 10.']);

        $info[$codes['anh_chi_em']] = '2';
        $this->post(route('test.submit'), ['answers' => $answers, 'info' => $info, 'info_other' => [$codes['nguon_biet'] => 'Hội thảo ở trường']])
            ->assertSessionHasNoErrors();

        $extra = (array) OutboxMessage::sole()->payload['extra_fields'];
        $this->assertSame('Hội thảo ở trường', $extra['nguon_biet']);
        $this->assertSame('Muốn học kỹ thuật', $extra['mong_doi']);
        $this->assertSame('4', $extra['tu_tin']);

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.attempts.show', Attempt::sole()))->assertOk()
            ->assertSee('Hội thảo ở trường')
            ->assertDontSee('Số anh chị em')
            ->assertDontSee('lan@example.com');
    }

    /**
     * @return array<string, string>
     */
    private function hollandAnswers(): array
    {
        return QuestionSet::where('code', 'thpt')->sole()->questions()->pluck('code')->mapWithKeys(fn (string $code) => [$code => '1'])->all();
    }

    /**
     * @return array<string, string>
     */
    private function info(): array
    {
        return [
            'A02' => 'Nguyễn Thị Lan', 'A19' => '2009-05-20', 'A05' => 'lan@example.com', 'A04' => '0912345678',
            'A01' => 'PH', 'A07' => 'Lớp 11', 'A20' => 'THPT Lê Hồng Phong', 'A21' => 'Hà Nội', 'A15' => '1',
        ];
    }

    /**
     * @return list<list<mixed>>
     */
    private function setRowsFromExport(QuestionWorkbook $workbook): array
    {
        $reader = new Reader;
        $reader->open($workbook->export($this->set));
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
            break;
        }
        $reader->close();

        return array_slice($rows, 1);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function writeSheet(array $rows): string
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

        return $path;
    }
}
