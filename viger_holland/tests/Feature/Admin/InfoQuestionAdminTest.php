<?php

namespace Tests\Feature\Admin;

use App\Enums\AnswerType;
use App\Models\AdminActivity;
use App\Models\Question;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfoQuestionAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_index_shows_question_cards_and_card_form_has_crm_mapping(): void
    {
        $this->get(route('admin.info-questions.index'))->assertOk()
            ->assertSee('Họ và tên')
            ->assertSee('Ô đồng ý · luôn ở cuối form')
            ->assertSee('data-qcard', false)
            ->assertSee('+ Gõ câu hỏi mới rồi nhấn Enter');

        $this->get(route('admin.info-questions.card', Question::where('code', 'A02')->sole()))->assertOk()
            ->assertSee('data-autosave', false)
            ->assertSee('Lưu câu trả lời vào hồ sơ khách (CRM) ở mục')
            ->assertSee('<option value="student_name" selected>', false);
    }

    public function test_quick_add_creates_question_with_auto_crm_field_and_choice_placeholders(): void
    {
        $this->postJson(route('admin.info-questions.store'), [
            'text' => 'Trường THPT đang học có phải trường chuyên?', 'answer_type' => 'single',
            'is_active' => '1', 'crm_target' => '_auto', 'quick_add' => '1',
        ])->assertOk()->assertJsonStructure(['card']);

        $question = Question::where('code', 'A23')->sole();
        $this->assertSame('truong_thpt_dang_hoc_co_phai_truong', $question->extra_field_key);
        $this->assertSame(['Lựa chọn 1', 'Lựa chọn 2'], array_column($question->options, 'label'));
    }

    public function test_card_autosave_returns_summary_and_merges_history(): void
    {
        $question = Question::where('code', 'A20')->sole();
        $form = ['text' => 'Trường bạn đang học', 'answer_type' => 'text', 'crm_target' => 'current_school', 'is_required' => '1', 'is_active' => '1'];

        $this->putJson(route('admin.info-questions.update', $question), $form)
            ->assertOk()->assertJson(['text' => 'Trường bạn đang học', 'meta' => 'Chữ · bắt buộc', 'is_active' => true]);
        $this->putJson(route('admin.info-questions.update', $question), [...$form, 'text' => 'Trường đang học hiện nay'])->assertOk();

        $activity = AdminActivity::where('subject_type', 'question')->where('subject_id', $question->id)->sole();
        $this->assertSame('Trường đang học', $activity->before['text'], 'Hai lần tự lưu liên tiếp gộp thành một dòng lịch sử, giữ bản gốc');
        $this->assertSame('Trường đang học hiện nay', $activity->after['text']);

        $this->putJson(route('admin.info-questions.update', $question), [...$form, 'answer_type' => 'single'])
            ->assertStatus(422)->assertJsonValidationErrors('options_text');
    }

    public function test_create_question_with_custom_crm_field(): void
    {
        $this->post(route('admin.info-questions.store'), [
            'text' => 'Trường THPT đang học có phải trường chuyên?',
            'answer_type' => 'boolean',
            'crm_target' => '_custom',
            'extra_field_key' => 'Trường chuyên',
            'is_active' => '1',
        ])->assertRedirect(route('admin.info-questions.index'));

        $question = Question::where('code', 'A23')->sole();
        $this->assertSame(AnswerType::Boolean, $question->answer_type);
        $this->assertSame('truong_chuyen', $question->extra_field_key, 'Mã tự chuyển về chữ thường không dấu');
        $this->assertNull($question->lead_field);
        $this->assertLessThan(Question::where('lead_field', 'consent')->value('sort_order'), $question->sort_order, 'Câu mới đứng trước ô đồng ý');
    }

    public function test_choice_question_parses_options_lines(): void
    {
        $this->post(route('admin.info-questions.store'), [
            'text' => 'Biết đến Viger qua đâu?', 'answer_type' => 'single',
            'options_text' => "Facebook = FB\nBạn bè giới thiệu\n\n", 'crm_target' => '', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame([['value' => 'FB', 'label' => 'Facebook'], ['value' => 'Bạn bè giới thiệu', 'label' => 'Bạn bè giới thiệu']],
            Question::where('code', 'A23')->value('options'));
    }

    public function test_validation_errors_are_in_vietnamese(): void
    {
        $this->post(route('admin.info-questions.store'), ['text' => 'Chọn gì?', 'answer_type' => 'single', 'options_text' => ''])
            ->assertSessionHasErrors(['options_text' => 'Câu chọn cần ít nhất một lựa chọn (mỗi dòng một lựa chọn).']);

        $this->post(route('admin.info-questions.store'), ['text' => 'SĐT khác', 'answer_type' => 'phone', 'crm_target' => 'phone'])
            ->assertSessionHasErrors(['crm_target' => 'Trường này đang được câu A04 dùng. Mỗi trường CRM chỉ nhận từ một câu.']);

        $this->post(route('admin.info-questions.store'), ['text' => 'Mã sai', 'answer_type' => 'text', 'crm_target' => '_custom', 'extra_field_key' => '123'])
            ->assertSessionHasErrors('extra_field_key');
    }

    public function test_condition_show_if_is_saved(): void
    {
        $this->post(route('admin.info-questions.store'), [
            'text' => 'Nghề nghiệp của phụ huynh', 'answer_type' => 'text', 'is_active' => '1',
            'show_if_question' => 'A01', 'show_if_values' => ['PH'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['question' => 'A01', 'in' => ['PH']], Question::where('code', 'A23')->value('show_if'));
    }

    public function test_consent_cannot_be_turned_off_or_deleted(): void
    {
        $consent = Question::where('lead_field', 'consent')->sole();

        $this->patch(route('admin.questions.toggle', $consent))->assertSessionHasErrors('question');
        $this->delete(route('admin.questions.destroy', $consent))->assertSessionHasErrors('question');

        $this->put(route('admin.info-questions.update', $consent), ['text' => 'Tôi đồng ý cho lưu thông tin', 'answer_type' => 'consent'])->assertSessionHasNoErrors();
        $consent->refresh();
        $this->assertTrue($consent->is_active);
        $this->assertTrue($consent->is_required);
        $this->assertSame('consent', $consent->lead_field);
        $this->assertSame('Tôi đồng ý cho lưu thông tin', $consent->text);
    }

    public function test_edit_keeps_existing_mapping(): void
    {
        $question = Question::where('code', 'A02')->sole();

        $this->get(route('admin.info-questions.edit', $question))->assertOk()->assertSee('Gửi sang CRM vào trường');

        $this->put(route('admin.info-questions.update', $question), [
            'text' => 'Họ và tên học sinh', 'answer_type' => 'text', 'crm_target' => 'student_name', 'is_required' => '1', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('student_name', $question->fresh()->lead_field);
        $this->assertSame('Họ và tên học sinh', $question->fresh()->text);
    }
}
