<?php

namespace Tests\Feature\Admin;

use App\Enums\AnswerType;
use App\Models\AdminActivity;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionSetAdminTest extends TestCase
{
    use RefreshDatabase;

    private QuestionSet $set;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create());
        $this->set = QuestionSet::where('code', 'thpt')->sole();
    }

    public function test_pages_require_login(): void
    {
        auth()->logout();

        $this->get(route('admin.question-sets.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.question-sets.store'), ['name' => 'X'])->assertRedirect(route('admin.login'));
    }

    public function test_index_and_show_list_sets_and_questions(): void
    {
        $this->get(route('admin.question-sets.index'))->assertOk()->assertSee('THPT – 54 câu')->assertSee('Mặc định')->assertSee('54 / 54');

        $this->get(route('admin.question-sets.show', $this->set))->assertOk()
            ->assertSee('Người thích làm việc bằng tay')
            ->assertSee('Dán nhiều câu')
            ->assertSee('Xem như khách');
    }

    public function test_all_admin_form_pages_render(): void
    {
        $question = Question::where('code', 'B01')->sole();

        $this->get(route('admin.question-sets.create'))->assertOk()->assertSee('Kiểu câu mặc định')->assertSee('Xáo trộn thứ tự câu');
        $this->get(route('admin.question-sets.edit', $this->set))->assertOk()->assertSee('Nhãn từng mức');
        $this->get(route('admin.question-sets.questions.create', $this->set))->assertOk()->assertSee('Lưu và thêm câu khác');
        $this->get(route('admin.holland-questions.edit', $question))->assertOk()->assertSee('Tùy chọn nâng cao');
        $this->get(route('admin.info-questions.create'))->assertOk()->assertSee('Chỉ hiện khi');
        $this->get(route('admin.info-questions.edit', Question::where('lead_field', 'consent')->sole()))->assertOk()->assertSee('Chỉ sửa được câu chữ');
        $this->get(route('admin.excel.index'))->assertOk()->assertSee('Tải bộ câu hỏi');
        $this->get(route('admin.holland-questions.edit', Question::where('code', 'A02')->sole()))->assertNotFound();
    }

    public function test_question_cards_quick_add_duplicate_and_delete(): void
    {
        $this->get(route('admin.question-sets.show', $this->set))->assertOk()
            ->assertSee('data-qcard', false)->assertSee('Đúng / Không · cộng điểm cho Kỹ thuật');

        $b01 = Question::where('code', 'B01')->sole();
        $this->get(route('admin.holland-questions.card', $b01))->assertOk()->assertSee('data-autosave', false)->assertSee('Câu này cộng điểm cho nhóm');

        $this->postJson(route('admin.question-sets.questions.store', $this->set), [
            'text' => 'Tôi thích lắp ráp mô hình', 'riasec' => 'R', 'answer_type' => 'yes_no', 'is_active' => '1', 'is_required' => '1',
        ])->assertOk()->assertJsonPath('card', fn (string $html) => str_contains($html, 'Tôi thích lắp ráp mô hình'));

        $copy = $this->postJson(route('admin.questions.duplicate', $b01))->assertOk();
        $duplicate = Question::where('text', $b01->text)->where('id', '!=', $b01->id)->sole();
        $this->assertSame($b01->sort_order + 1, $duplicate->sort_order);
        $this->assertSame($this->set->id, $duplicate->question_set_id);
        $this->assertStringContainsString((string) $duplicate->id, $copy->json('card'));

        $this->deleteJson(route('admin.questions.destroy', $duplicate))->assertOk();
        $this->assertSoftDeleted($duplicate);

        $this->putJson(route('admin.holland-questions.update', $b01), ['text' => 'Câu sửa trong thẻ', 'answer_type' => 'single', 'is_active' => '1'])
            ->assertStatus(422)->assertJsonValidationErrors('choices');
    }

    public function test_create_set_with_likert5_scale(): void
    {
        $response = $this->post(route('admin.question-sets.store'), [
            'name' => 'Sinh viên', 'code' => '', 'answer_type' => 'likert5', 'per_page' => 6, 'is_active' => '1',
        ]);

        $set = QuestionSet::where('code', 'sinh-vien')->sole();
        $response->assertRedirect(route('admin.question-sets.show', $set));
        $this->assertSame(AnswerType::Likert5, $set->answer_type);
        $this->assertCount(5, $set->scaleOptions());
        $this->assertFalse($set->is_default);
        $this->assertTrue(AdminActivity::where('subject_type', 'question_set')->where('subject_id', $set->id)->exists());
    }

    public function test_set_code_must_be_unique_and_well_formed(): void
    {
        $this->post(route('admin.question-sets.store'), ['name' => 'Trùng', 'code' => 'thpt', 'answer_type' => 'yes_no', 'per_page' => 9])
            ->assertSessionHasErrors(['code' => 'Mã này đã có bộ câu khác dùng (kể cả bộ trong thùng rác). Vui lòng chọn mã khác.']);
    }

    public function test_update_scale_labels_keeps_values(): void
    {
        $this->put(route('admin.question-sets.update', $this->set), [
            'name' => $this->set->name, 'code' => 'thpt', 'answer_type' => 'yes_no', 'per_page' => 9,
            'is_active' => '1', 'is_default' => '1', 'scale_labels' => ['Đúng với tôi', 'Không đúng'],
        ])->assertRedirect(route('admin.question-sets.show', $this->set));

        $this->assertSame([['value' => 1, 'label' => 'Đúng với tôi'], ['value' => 0, 'label' => 'Không đúng']], $this->set->fresh()->scaleOptions());
    }

    public function test_changing_answer_type_resets_scale_and_question_max_score(): void
    {
        $this->put(route('admin.question-sets.update', $this->set), [
            'name' => $this->set->name, 'code' => 'thpt', 'answer_type' => 'likert3', 'per_page' => 9, 'is_active' => '1', 'is_default' => '1',
        ]);

        $set = $this->set->fresh();
        $this->assertSame(AnswerType::Likert3, $set->answer_type);
        $this->assertSame(2.0, Question::where('code', 'B01')->sole()->maxPoints($set), 'Điểm tối đa theo thang mới (0–2)');
    }

    public function test_default_set_cannot_be_deactivated_or_deleted(): void
    {
        $this->put(route('admin.question-sets.update', $this->set), [
            'name' => $this->set->name, 'code' => 'thpt', 'answer_type' => 'yes_no', 'per_page' => 9, 'is_default' => '1',
        ])->assertSessionHasErrors('is_active');

        $this->delete(route('admin.question-sets.destroy', $this->set))->assertSessionHasErrors('set');
        $this->assertNotSoftDeleted($this->set);
    }

    public function test_duplicate_copies_questions_with_new_codes_and_starts_inactive(): void
    {
        $this->post(route('admin.question-sets.duplicate', $this->set));

        $copy = QuestionSet::where('code', 'thpt-ban-sao')->sole();
        $this->assertFalse($copy->is_active);
        $this->assertSame(54, $copy->questions()->count());
        $this->assertSame('B55', $copy->questions()->orderBy('code')->first()->code);
        $this->assertSame(54, $this->set->questions()->count(), 'Bộ gốc không đổi');
    }

    public function test_delete_and_restore_set(): void
    {
        $other = QuestionSet::create(['code' => 'thu', 'name' => 'Thử', 'answer_type' => 'yes_no', 'is_active' => true]);

        $this->delete(route('admin.question-sets.destroy', $other))->assertRedirect(route('admin.question-sets.index'));
        $this->assertSoftDeleted($other);
        $this->get(route('admin.question-sets.index'))->assertSee('Thùng rác')->assertSee('Thử');

        $this->post(route('admin.question-sets.restore', $other->id));
        $this->assertNotSoftDeleted($other);
    }

    public function test_add_holland_question_gets_next_code(): void
    {
        $this->post(route('admin.question-sets.questions.store', $this->set), [
            'text' => 'Tôi thích lắp ráp mô hình', 'answer_type' => 'yes_no', 'riasec' => 'R', 'is_active' => '1', 'is_required' => '1',
        ])->assertRedirect(route('admin.question-sets.show', $this->set));

        $question = Question::where('code', 'B55')->sole();
        $this->assertSame('R', $question->riasec);
        $this->assertSame($this->set->id, $question->question_set_id);
        $this->assertSame(1.0, $question->maxPoints());
    }

    public function test_holland_question_requires_group(): void
    {
        $this->post(route('admin.question-sets.questions.store', $this->set), ['text' => 'Câu thiếu nhóm'])
            ->assertSessionHasErrors(['riasec' => 'Vui lòng chọn nhóm Holland của câu này.']);
    }

    public function test_bulk_paste_creates_questions_with_groups_from_line_prefix(): void
    {
        $this->post(route('admin.question-sets.questions.bulk', $this->set), [
            'lines' => "R | Tôi thích sửa xe\n\nI: Tôi thích thí nghiệm\nTôi thích vẽ tranh",
            'riasec' => 'A',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['B55' => 'R', 'B56' => 'I', 'B57' => 'A'], Question::whereIn('code', ['B55', 'B56', 'B57'])->orderBy('code')->pluck('riasec', 'code')->all());
        $this->assertSame('Tôi thích sửa xe', Question::where('code', 'B55')->value('text'));
    }

    public function test_bulk_paste_reports_line_without_group(): void
    {
        $this->post(route('admin.question-sets.questions.bulk', $this->set), ['lines' => "R | Câu một\nCâu hai không có nhóm"])
            ->assertSessionHasErrors('lines');

        $this->assertSame(54, Question::part('B')->count(), 'Có dòng lỗi thì không thêm câu nào');
    }

    public function test_edit_question_records_history_and_can_revert(): void
    {
        $question = Question::where('code', 'B01')->sole();
        $original = $question->text;

        $this->put(route('admin.holland-questions.update', $question), ['text' => 'Câu đã sửa', 'answer_type' => 'yes_no', 'riasec' => 'R', 'is_active' => '1', 'is_required' => '1'])
            ->assertRedirect(route('admin.question-sets.show', $this->set));
        $this->assertSame('Câu đã sửa', $question->fresh()->text);

        $activity = AdminActivity::where('subject_type', 'question')->where('subject_id', $question->id)->latest('id')->firstOrFail();
        $this->get(route('admin.holland-questions.edit', $question))->assertSee('Quay lại bản này');

        $this->post(route('admin.history.revert', $activity))->assertSessionHasNoErrors()->assertSessionHas('status', 'Đã quay lại bản trước.');
        $this->assertSame($original, $question->fresh()->text);
    }

    public function test_toggle_delete_restore_and_reorder_questions(): void
    {
        $b01 = Question::where('code', 'B01')->sole();
        $b02 = Question::where('code', 'B02')->sole();

        $this->patch(route('admin.questions.toggle', $b01));
        $this->assertFalse($b01->fresh()->is_active);

        $this->delete(route('admin.questions.destroy', $b02));
        $this->assertSoftDeleted($b02);
        $this->post(route('admin.questions.restore', $b02->id));
        $this->assertNotSoftDeleted($b02);

        $this->post(route('admin.questions.reorder'), ['order' => [$b02->id, $b01->id]])->assertSessionHasNoErrors();
        $this->assertLessThan($b01->fresh()->sort_order, $b02->fresh()->sort_order);
    }

    public function test_reorder_rejects_mixed_parts(): void
    {
        $this->post(route('admin.questions.reorder'), ['order' => [Question::where('code', 'B01')->value('id'), Question::where('code', 'A02')->value('id')]])
            ->assertSessionHasErrors('order');
    }

    public function test_admin_preview_shows_inactive_set_but_guests_do_not(): void
    {
        QuestionSet::create(['code' => 'nhap', 'name' => 'Bản nháp', 'answer_type' => 'yes_no', 'is_active' => false]);
        Question::create(['code' => 'B90', 'part' => 'B', 'question_set_id' => QuestionSet::where('code', 'nhap')->value('id'), 'text' => 'Câu bản nháp', 'answer_type' => 'yes_no', 'riasec' => 'R']);

        $this->get(route('test.show', ['bo' => 'nhap', 'xem_truoc' => 1]))->assertOk()->assertSee('Câu bản nháp')->assertSee('Đang xem trước');

        auth()->logout();
        $this->get(route('test.show', ['bo' => 'nhap', 'xem_truoc' => 1]))->assertOk()->assertDontSee('Câu bản nháp')->assertDontSee('Đang xem trước');
    }
}
