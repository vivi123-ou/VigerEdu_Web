<?php

namespace Tests\Feature\Assessment;

use App\Modules\Assessment\Models\SessionQuestion;
use App\Modules\Assessment\Models\TestSession;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\QuestionBankSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Luồng khách H3 theo mẫu đã chốt: trang bắt đầu → làm bài từng trang 6 câu chấm sao → kết quả sơ lược.
 * Lượt làm bài gắn với phiên trình duyệt, câu trả lời lưu sau mỗi trang, không nhảy cóc, kết quả không lộ tên ngành bị làm mờ.
 */
class CustomerFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Điểm từng câu theo nhóm để ra hồ sơ HS01 của Thầy (khách thấy IS). */
    private const HS01 = [
        'I' => [5, 5, 5, 5, 4, 4], 'S' => [5, 5, 4, 4, 4, 4], 'A' => [5, 4, 4, 4, 4, 4],
        'R' => [4, 4, 3, 3, 3, 3], 'C' => [4, 3, 3, 3, 3, 3], 'E' => [3, 3, 2, 2, 2, 2],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class, CatalogSeeder::class, QuestionBankSeeder::class, TestConfigSeeder::class]);
    }

    public function test_start_page_follows_the_approved_mockup_with_editable_texts_and_a_sample_from_the_catalog(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Bạn hợp với hướng ngành nào khi')
            ->assertSee('du học Đức?')
            ->assertSee('VIGER.EDU.VN')
            ->assertSee('From Vietnam to Europe')
            ->assertSee('Báo cáo mẫu')
            ->assertSee('Nghiên cứu – Xã hội')
            ->assertSee('Tâm lý học')
            ->assertSee(route('test.start'), false)
            ->assertSee('0819 949 968')
            ->assertDontSee('Câu hỏi thường gặp', false)
            ->assertDontSee('name="robots"', false);
    }

    public function test_quiz_and_result_need_a_started_test(): void
    {
        $this->get(route('test.show'))->assertRedirect(route('home'));
        $this->get(route('test.result'))->assertRedirect(route('home'));
        $this->post(route('test.submit'), ['page' => 1])->assertRedirect(route('home'));
    }

    public function test_starting_creates_a_test_remembers_the_device_and_shows_page_one(): void
    {
        $response = $this->post(route('test.start'), ['utm_source' => 'facebook']);

        $response->assertRedirect(route('test.show'))->assertCookie('holland_device');
        $session = TestSession::sole();
        $this->assertSame(['utm_source' => 'facebook'], $session->utm);
        $this->assertSame(64, strlen($session->device_hash), 'Lưu bản băm của cookie, không lưu giá trị gốc');

        $page = $this->get(route('test.show'))->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false);
        $first = $session->sessionQuestions()->orderBy('display_order')->first();
        $page->assertSee($first->text_snapshot)
            ->assertSee('Hoàn toàn không giống tôi')
            ->assertSee('Hoàn toàn giống tôi')
            ->assertSee('aria-valuenow="0"', false);
        $this->assertSame(6, substr_count($page->getContent(), 'data-question'), 'Mỗi trang 6 câu');
        $this->assertSame(30, substr_count($page->getContent(), 'type="radio"'), '6 câu × 5 sao');
    }

    public function test_every_question_on_a_page_is_required_but_chosen_answers_are_kept(): void
    {
        $session = $this->begin();
        $items = $this->pageItems($session, 1);
        $partial = $this->answersFor($items->take(4));

        $this->from(route('test.show'))->post(route('test.submit'), ['page' => 1, 'direction' => 'next', 'answers' => $partial])
            ->assertRedirect(route('test.show', ['trang' => 1]))
            ->assertSessionHasErrors('answers');

        $this->assertSame(4, $session->responses()->count(), 'Câu đã chọn được lưu, không bắt làm lại');
        $this->get(route('test.show'))->assertSee('Chưa chọn – câu này còn thiếu');

        $this->post(route('test.submit'), ['page' => 1, 'direction' => 'next', 'answers' => $this->answersFor($items)])
            ->assertRedirect(route('test.show', ['trang' => 2]));
    }

    public function test_cannot_skip_ahead_but_can_go_back_without_losing_answers(): void
    {
        $session = $this->begin();
        $this->answerPage($session, 1);

        $this->get(route('test.show', ['trang' => 5]))->assertOk()->assertSee($this->pageItems($session, 2)->first()->text_snapshot);

        $partial = $this->answersFor($this->pageItems($session, 2)->take(2));
        $this->post(route('test.submit'), ['page' => 2, 'direction' => 'back', 'answers' => $partial])
            ->assertRedirect(route('test.show', ['trang' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertSame(8, $session->responses()->count());

        $this->get(route('test.show', ['trang' => 1]))->assertOk()->assertSee('checked', false);
    }

    public function test_finishing_all_pages_shows_the_short_result_without_leaking_hidden_majors(): void
    {
        $session = $this->begin();
        for ($page = 1; $page <= 6; $page++) {
            $response = $this->answerPage($session, $page);
        }
        $response->assertRedirect(route('test.result'));

        $session->refresh();
        $this->assertSame('answered', $session->status);
        $this->assertSame('ISA', $session->code3);

        $result = $this->get(route('test.result'))->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->assertSee('Kết quả Holland của bạn')
            ->assertSee('Nghiên cứu – Xã hội')
            ->assertSee('Tâm lý học')
            ->assertSee('Khoa học sức khỏe')
            ->assertSee('Gợi ý chính')
            ->assertSee('Xem báo cáo đầy đủ')
            ->assertSee(route('report.request'), false);

        $hidden = collect($session->result['customer']['majors'])->slice(2)->pluck('name_vi');
        $this->assertNotEmpty($hidden);
        foreach ($hidden as $name) {
            $result->assertDontSee($name);
        }
        $result->assertDontSee('ISA')->assertDontSee('Ngành nền của cặp');

        $this->get(route('test.show'))->assertRedirect(route('test.result'));
    }

    public function test_pressing_start_again_resumes_and_a_new_attempt_gets_new_questions(): void
    {
        $device = $this->post(route('test.start'))->getCookie('holland_device')->getValue();
        $first = TestSession::latest('id')->first();
        $this->answerPage($first, 1);
        $this->post(route('test.start'))->assertRedirect(route('test.show'));
        $this->assertSame(1, TestSession::count(), 'Bấm bắt đầu lần nữa khi đang làm dở thì làm tiếp, không tạo lượt mới');

        for ($page = 2; $page <= 6; $page++) {
            $this->answerPage($first, $page);
        }
        // Trình duyệt thật tự gửi lại cookie của máy; trong test phải gửi kèm.
        $this->withCookie('holland_device', $device)->post(route('test.start'))->assertRedirect(route('test.show'));
        $second = TestSession::latest('id')->first();

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->device_hash, $second->device_hash);
        $this->assertSame([], array_values(array_intersect(
            $first->sessionQuestions()->pluck('question_id')->all(),
            $second->sessionQuestions()->pluck('question_id')->all(),
        )), 'Làm lại trên cùng máy thì gặp câu khác');
    }

    private function begin(): TestSession
    {
        $this->post(route('test.start'));

        return TestSession::latest('id')->first();
    }

    /**
     * @return Collection<int, SessionQuestion>
     */
    private function pageItems(TestSession $session, int $page)
    {
        return $session->sessionQuestions()->orderBy('display_order')->get()->chunk(6)->values()[$page - 1]->values();
    }

    private function answerPage(TestSession $session, int $page): TestResponse
    {
        return $this->post(route('test.submit'), ['page' => $page, 'direction' => 'next', 'answers' => $this->answersFor($this->pageItems($session, $page)), 'ms' => []]);
    }

    /**
     * Trả lời theo hồ sơ HS01: câu thứ k của nhóm G (theo thứ tự xuất hiện trong cả đề) lấy điểm HS01[G][k].
     *
     * @param  Collection<int, SessionQuestion>  $items
     * @return array<int, int>
     */
    private function answersFor($items): array
    {
        $answers = [];
        foreach ($items as $item) {
            $order = TestSession::find($item->test_session_id)->sessionQuestions()->where('group', $item->group)->where('display_order', '<', $item->display_order)->count();
            $answers[$item->question_id] = self::HS01[$item->group][$order];
        }

        return $answers;
    }
}
