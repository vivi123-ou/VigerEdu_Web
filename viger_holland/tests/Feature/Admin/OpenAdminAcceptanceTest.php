<?php

namespace Tests\Feature\Admin;

use App\Models\Attempt;
use App\Models\OutboxMessage;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Test chấp nhận bắt buộc (docs/PROMPT_2_HOLLAND.md mục 0b.5): chỉ dùng trang admin, không sửa code.
 * Thêm câu Phần A "Trường chuyên?" (gửi CRM mã truong_chuyen) + bộ câu B mới 30 câu thang 5 mức
 * → khách thấy câu mới, chấm đúng theo thang mới, gói CRM có extra_fields.truong_chuyen.
 */
class OpenAdminAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_adds_custom_info_question_and_new_likert5_set_without_code(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->withoutDefer();
        Mail::fake();
        $admin = User::factory()->create();

        // 1. Thêm câu Phần A
        $this->actingAs($admin)->post(route('admin.info-questions.store'), [
            'text' => 'Trường THPT đang học có phải trường chuyên?',
            'answer_type' => 'boolean', 'crm_target' => '_custom', 'extra_field_key' => 'truong_chuyen', 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $infoCode = Question::where('extra_field_key', 'truong_chuyen')->value('code');

        // 2. Thêm bộ câu mới thang 5 mức + dán 30 câu (5 câu mỗi nhóm)
        $this->post(route('admin.question-sets.store'), [
            'name' => 'Sinh viên – 30 câu', 'code' => 'sinh-vien', 'answer_type' => 'likert5', 'per_page' => 6, 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $set = QuestionSet::where('code', 'sinh-vien')->sole();

        $lines = collect(['R', 'I', 'A', 'S', 'E', 'C'])
            ->flatMap(fn (string $group) => collect(range(1, 5))->map(fn (int $i) => "{$group} | Câu sinh viên nhóm {$group} số {$i}"))
            ->implode("\n");
        $this->post(route('admin.question-sets.questions.bulk', $set), ['lines' => $lines])->assertSessionHasNoErrors();
        $this->assertSame(30, $set->questions()->count());

        auth()->logout();

        // 3. Khách thấy câu mới
        $this->get(route('test.show', ['bo' => 'sinh-vien']))->assertOk()
            ->assertSee('Câu sinh viên nhóm R số 1')
            ->assertSee('Trường THPT đang học có phải trường chuyên?')
            ->assertSee('Hoàn toàn đúng')
            ->assertDontSee('Người thích làm việc bằng tay');

        // 4. Nộp bài: A, I, S cao; điểm thang 0–4
        $points = ['R' => 1, 'I' => 3, 'A' => 4, 'S' => 2, 'E' => 0, 'C' => 1];
        $answers = $set->questions()->get()->mapWithKeys(fn (Question $q) => [$q->code => (string) $points[$q->riasec]])->all();

        $this->post(route('test.submit'), [
            'bo' => 'sinh-vien',
            'answers' => $answers,
            'info' => [
                'A02' => 'Lê Minh', 'A19' => '2005-01-02', 'A05' => 'minh@example.com', 'A04' => '0912345678',
                'A01' => 'HS', 'A07' => 'SV năm 1', 'A20' => 'ĐH Bách khoa', 'A21' => 'Hà Nội', 'A15' => '1',
                $infoCode => '1',
            ],
        ])->assertSessionHasNoErrors();

        $attempt = Attempt::sole();
        $this->assertSame($set->id, $attempt->question_set_id);
        $this->assertSame('AIS', $attempt->holland_code);
        $this->assertEqualsWithDelta(1.0, $attempt->result['percent']['A'], 0.0001, 'A: 5 câu × 4 điểm / 20');
        $this->assertEqualsWithDelta(0.75, $attempt->result['percent']['I'], 0.0001);
        $this->assertEqualsWithDelta(0.0, $attempt->result['percent']['E'], 0.0001);

        // 5. Gói gửi CRM có trường mới
        $payload = OutboxMessage::sole()->payload;
        $this->assertSame('1', (string) ((array) $payload['extra_fields'])['truong_chuyen']);
        $this->assertSame('sinh-vien', $payload['quiz']['question_set']);
        $this->assertCount(30, $payload['quiz']['answers']);
        $this->assertSame(4, $payload['quiz']['answers'][0]['max']);
    }
}
