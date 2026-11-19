<?php

namespace Tests\Feature\Assessment;

use App\Models\User;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\CreateQuestion;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\EditQuestion;
use App\Modules\Assessment\Filament\Resources\Questions\Pages\ListQuestions;
use App\Modules\Assessment\Models\Question;
use App\Modules\Assessment\Models\QuestionFacet;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Trang "Thư viện câu hỏi" (Filament): lọc theo nhóm / trạng thái, duyệt / tạm ngưng nhiều câu, sửa câu
 * (tăng phiên bản khi đổi chữ, ghi lý do khi đổi trọng số), thêm câu mới, thùng rác; không xóa hẳn.
 */
class QuestionLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class, RoleSeeder::class, TestConfigSeeder::class, CatalogSeeder::class, QuestionBankSeeder::class]);
        $this->actingAs(User::factory()->create());
    }

    public function test_list_shows_help_and_group_tabs_and_filters_by_group(): void
    {
        $this->get(route('filament.admin.resources.questions.index'))
            ->assertOk()
            ->assertSee('Kho 300 câu')
            ->assertSee('R – Thực hành');

        $rQuestions = Question::where('group', 'R')->orderBy('code')->limit(5)->get();
        $iQuestion = Question::where('group', 'I')->orderBy('code')->first();

        Livewire::test(ListQuestions::class)
            ->set('activeTab', 'R')
            ->assertCanSeeTableRecords($rQuestions)
            ->assertCanNotSeeTableRecords([$iQuestion]);

        Livewire::test(ListQuestions::class)
            ->searchTable('mô hình cơ khí')
            ->assertCanSeeTableRecords(Question::where('code', 'R001')->get());
    }

    public function test_bulk_suspend_then_approve(): void
    {
        $questions = Question::whereIn('code', ['R001', 'R002'])->get();

        Livewire::test(ListQuestions::class)->callTableBulkAction('status_suspended', $questions);
        $this->assertSame(2, Question::whereIn('code', ['R001', 'R002'])->where('review_status', Question::STATUS_SUSPENDED)->count());
        $this->assertSame(298, Question::approved()->count(), 'Câu tạm ngưng không còn được rút vào đề');

        Livewire::test(ListQuestions::class)->callTableBulkAction('status_approved', $questions);
        $this->assertSame(300, Question::approved()->count());
    }

    public function test_editing_text_bumps_version_and_changing_weight_requires_a_reason(): void
    {
        $question = Question::where('code', 'R001')->first();

        Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
            ->fillForm(['selection_weight' => 2])
            ->call('save')
            ->assertHasFormErrors(['weight_change_reason' => 'required']);

        Livewire::test(EditQuestion::class, ['record' => $question->getRouteKey()])
            ->fillForm(['text' => 'Tôi thích lắp ráp mô hình từ các bộ phận rời.', 'selection_weight' => 2, 'weight_change_reason' => 'Thầy giảm tần suất câu này'])
            ->call('save')
            ->assertHasNoFormErrors();

        $question->refresh();
        $this->assertSame('Tôi thích lắp ráp mô hình từ các bộ phận rời.', $question->text);
        $this->assertSame('1.1', $question->item_version);
        $this->assertSame(2.0, $question->selection_weight);
        $this->assertSame('Thầy giảm tần suất câu này', $question->weightAudits()->value('reason'));
        $this->assertSame(auth()->id(), $question->weightAudits()->value('user_id'));
        $this->assertSame('R', $question->group, 'Không đổi được nhóm của câu đã có');
    }

    public function test_new_question_gets_next_code_in_group_and_starts_as_draft(): void
    {
        Livewire::test(CreateQuestion::class)
            ->fillForm([
                'group' => 'R',
                'question_facet_id' => QuestionFacet::where('group', 'R')->value('id'),
                'text' => 'Tôi thích sửa xe đạp.',
                'priority_tier' => 'C',
                'selection_weight' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $question = Question::where('code', 'R051')->firstOrFail();
        $this->assertSame(Question::STATUS_DRAFT, $question->review_status, 'Câu mới chưa được rút vào đề cho tới khi duyệt');
        $this->assertSame('1.0', $question->item_version);
    }

    public function test_trash_and_restore_but_never_force_delete(): void
    {
        $question = Question::where('code', 'R003')->first();

        Livewire::test(ListQuestions::class)->callTableBulkAction('delete', [$question]);
        $this->assertSoftDeleted($question);

        Livewire::test(ListQuestions::class)
            ->filterTable('trashed', false)
            ->callTableBulkAction('restore', [$question]);
        $this->assertNotSoftDeleted($question);

        $this->assertFalse(auth()->user()->can('forceDelete', $question), 'Không ai xóa hẳn câu hỏi');
    }

    public function test_people_without_permission_cannot_open_or_edit(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));

        $this->get(route('filament.admin.resources.questions.index'))->assertNotFound();
        $this->get(route('filament.admin.resources.questions.edit', Question::first()))->assertNotFound();
    }
}
