<?php

namespace Tests\Feature;

use App\Mail\TestResultMail;
use App\Models\Attempt;
use App\Models\OutboxMessage;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class TakeTestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutDefer();
        Mail::fake();
        RateLimiter::clear('127.0.0.1');
    }

    public function test_test_page_shows_holland_questions_first_and_info_form_last_without_counts(): void
    {
        $response = $this->get(route('test.show'))->assertOk();

        $response->assertSeeInOrder(['Người thích làm việc bằng tay', 'Bạn đã hoàn thành!', 'Họ và tên', 'Xem kết quả']);
        $response->assertDontSee('/54')->assertDontSee('phút');
        $response->assertSee('name="answers[B01]"', false);
        $response->assertSee('name="info[A02]"', false);
    }

    public function test_valid_submission_creates_attempt_result_and_outbox_lead(): void
    {
        $response = $this->post(route('test.submit'), $this->payload());

        $attempt = Attempt::sole();
        $response->assertRedirect(route('result.show', $attempt->result_token));

        $this->assertSame('AIS', $attempt->holland_code);
        $this->assertSame('N043', $attempt->result['top_university'][0]['major_code']);
        $this->assertCount(75, $attempt->result['ranking']);
        $this->assertCount(54, $attempt->answers);
        $this->assertSame(Attempt::SYNC_PENDING, $attempt->crm_sync_status);
        $this->assertTrue(Str::isUuid($attempt->result_token));

        $lead = OutboxMessage::sole();
        $this->assertSame(OutboxMessage::STATUS_PENDING, $lead->status);
        $this->assertSame($attempt->id, $lead->attempt_id);
    }

    public function test_lead_payload_follows_lead_1_contract(): void
    {
        $this->post(route('test.submit'), $this->payload());

        $attempt = Attempt::sole();
        $payload = OutboxMessage::sole()->payload;

        $this->assertSame('lead/1', $payload['schema_version']);
        $this->assertSame('holland', $payload['source']);
        $this->assertSame('HL-'.$attempt->uuid, $payload['external_ref']);
        $this->assertTrue($payload['consent']);
        $this->assertSame('HS', $payload['role']);
        $this->assertSame('Nguyễn Thị Lan', $payload['student_name']);
        $this->assertSame('0912345678', $payload['phone'], 'SĐT đã chuẩn hóa (+84 → 0, bỏ dấu cách)');
        $this->assertSame('lan@example.com', $payload['email']);
        $this->assertSame(2009, $payload['birth_year'], 'Năm sinh tự điền từ ngày sinh');
        $this->assertSame('Lớp 11', $payload['current_level']);
        $this->assertSame('THPT Lê Hồng Phong', $payload['current_school']);
        $this->assertSame('TP. Hồ Chí Minh', $payload['province']);
        $this->assertSame('Trần Văn Ba', $payload['parent_name']);
        $this->assertSame(['birth_date' => '2009-05-20', 'parent_email' => 'ba@example.com'], $payload['extra_fields']);
        $this->assertNull($payload['gpa'], 'Câu không trả lời thì null');

        $this->assertSame('holland-result/1', $payload['holland_result']['schema_version']);
        $this->assertArrayNotHasKey('ranking', $payload['holland_result']);

        $this->assertSame($attempt->uuid, $payload['quiz']['attempt_id']);
        $this->assertCount(54, $payload['quiz']['answers']);
        $this->assertSame(['code' => 'B01', 'group' => 'R', 'text' => 'Người thích làm việc bằng tay, sửa chữa đồ vật', 'value' => 1, 'max' => 1], $payload['quiz']['answers'][0]);
        $this->assertSame(route('admin.attempts.show', $attempt), $payload['quiz']['view_url']);
    }

    public function test_extra_field_added_by_admin_is_sent_in_extra_fields(): void
    {
        Question::create([
            'code' => 'A23', 'part' => 'A', 'text' => 'Trường THPT đang học có phải trường chuyên?', 'answer_type' => 'boolean',
            'extra_field_key' => 'truong_chuyen', 'sort_order' => 95, 'is_active' => true,
        ]);

        $this->post(route('test.submit'), $this->payload(['A23' => '1']))->assertRedirect();

        $this->assertSame('1', OutboxMessage::sole()->payload['extra_fields']['truong_chuyen']);
    }

    public function test_missing_consent_is_rejected_and_nothing_saved(): void
    {
        $this->post(route('test.submit'), $this->payload(['A15' => null]))
            ->assertSessionHasErrors(['info.A15' => 'Bạn cần đồng ý để xem kết quả.']);

        $this->assertSame(0, Attempt::count());
        $this->assertSame(0, OutboxMessage::count());
    }

    public function test_missing_both_phone_and_email_is_rejected(): void
    {
        Question::whereIn('code', ['A04', 'A05'])->update(['is_required' => false]);

        $this->post(route('test.submit'), $this->payload(['A04' => null, 'A05' => null]))
            ->assertSessionHasErrors(['info.A04', 'info.A05']);

        $this->assertSame(0, Attempt::count());
    }

    public function test_required_info_and_invalid_values_show_vietnamese_errors(): void
    {
        $this->post(route('test.submit'), $this->payload(['A02' => '', 'A04' => '12345', 'A05' => 'khong-phai-email', 'A19' => '2999-01-01']))
            ->assertSessionHasErrors([
                'info.A02' => 'Vui lòng điền thông tin này.',
                'info.A04' => 'Số điện thoại chưa đúng (cần 10 số, ví dụ 0912 345 678).',
                'info.A05' => 'Email chưa đúng (ví dụ: ten@gmail.com).',
                'info.A19' => 'Ngày sinh phải trước hôm nay.',
            ]);
    }

    public function test_unanswered_holland_question_is_rejected(): void
    {
        $payload = $this->payload();
        unset($payload['answers']['B10']);

        $this->post(route('test.submit'), $payload)->assertSessionHasErrors(['answers.B10' => 'Bạn chưa trả lời câu này.']);
    }

    public function test_hidden_conditional_questions_are_ignored(): void
    {
        // Phụ huynh tự làm → câu thông tin phụ huynh (chỉ hiện khi HS) bị bỏ qua, không gửi CRM
        $this->post(route('test.submit'), $this->payload(['A01' => 'PH', 'A22' => 'khong-hop-le']))->assertRedirect();

        $payload = OutboxMessage::sole()->payload;
        $this->assertSame('PH', $payload['role']);
        $this->assertNull($payload['parent_name']);
        $this->assertArrayNotHasKey('parent_email', $payload['extra_fields']);
    }

    public function test_likert_question_set_is_scored_with_its_scale(): void
    {
        $set = QuestionSet::create(['code' => 'sv', 'name' => 'Sinh viên', 'answer_type' => 'likert5', 'per_page' => 6, 'is_active' => true]);
        foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $i => $group) {
            Question::create(['code' => 'B9'.$i, 'part' => 'B', 'question_set_id' => $set->id, 'text' => "Câu {$group}", 'answer_type' => 'likert5', 'riasec' => $group, 'sort_order' => $i]);
        }

        $answers = ['B90' => '4', 'B91' => '3', 'B92' => '0', 'B93' => '1', 'B94' => '2', 'B95' => '0'];
        $this->post(route('test.submit'), ['bo' => 'sv', 'answers' => $answers, 'info' => $this->info()])->assertRedirect();

        $attempt = Attempt::sole();
        $this->assertSame('RIE', $attempt->holland_code);
        $this->assertEqualsWithDelta(0.75, $attempt->result['percent']['I'], 0.001);
        $this->assertSame(4, $attempt->answers[0]['max']);
        $this->assertSame($set->id, $attempt->question_set_id);
    }

    public function test_result_page_shows_code_and_majors_without_fit_numbers(): void
    {
        $this->post(route('test.submit'), $this->payload());
        $attempt = Attempt::sole();

        $response = $this->get(route('result.show', $attempt->result_token))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('Chào Lan')
            ->assertSee('AIS')
            ->assertSee('Ngôn ngữ và Văn học Đức')
            ->assertSee('Rất phù hợp')
            ->assertSee('Nghệ thuật');

        $response->assertDontSee('0,8011')->assertDontSee('0.8011')->assertDontSee('801')
            ->assertDontSee('GREEN')->assertDontSee('YELLOW')->assertDontSee('RED')
            ->assertDontSee('đặt lịch', false)
            ->assertDontSee('0912345678')->assertDontSee('lan@example.com');
    }

    public function test_wrong_result_token_returns_404(): void
    {
        $this->get(route('result.show', (string) Str::uuid()))->assertNotFound();
        $this->get('/ket-qua/khong-phai-uuid')->assertNotFound();
    }

    public function test_result_email_is_sent_to_customer(): void
    {
        $this->post(route('test.submit'), $this->payload());

        Mail::assertSent(TestResultMail::class, fn (TestResultMail $mail) => $mail->hasTo('lan@example.com'));
    }

    public function test_result_email_contains_code_and_link(): void
    {
        $this->post(route('test.submit'), $this->payload());
        $attempt = Attempt::sole();

        $html = (new TestResultMail($attempt))->render();

        $this->assertStringContainsString('AIS', $html);
        $this->assertStringContainsString(route('result.show', $attempt->result_token), $html);
    }

    public function test_contact_details_are_encrypted_and_never_logged(): void
    {
        Log::spy();

        $this->post(route('test.submit'), $this->payload());

        $raw = \DB::table('attempts')->value('contact');
        $this->assertStringNotContainsString('0912345678', (string) $raw);
        $this->assertStringNotContainsString('0912345678', (string) \DB::table('outbox')->value('payload'));

        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('debug');
    }

    public function test_submission_is_throttled_after_five_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('test.submit'), $this->payload())->assertRedirect();
        }

        $this->post(route('test.submit'), $this->payload())->assertSessionHasErrors('submit');
        $this->assertSame(5, Attempt::count());
    }

    public function test_admin_can_see_attempt_list_and_full_ranking_without_contact_details(): void
    {
        $this->post(route('test.submit'), $this->payload());
        $attempt = Attempt::sole();
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.attempts.index'))->assertOk()->assertSee('AIS')->assertSee('Chờ gửi')
            ->assertDontSee('0912345678')->assertDontSee('Nguyễn Thị Lan');

        $this->get(route('admin.attempts.show', $attempt))
            ->assertOk()
            ->assertSee('Xếp hạng đầy đủ các ngành')
            ->assertSee('0,8011')
            ->assertSee('Người thích làm việc bằng tay')
            ->assertSee('THPT Lê Hồng Phong')
            ->assertDontSee('0912345678')
            ->assertDontSee('lan@example.com');

        $this->get(route('admin.attempts.show', [$attempt, 'loai' => 'hoc-nghe']))
            ->assertOk()->assertSee('H007')->assertDontSee('N043 ·');
    }

    public function test_attempt_detail_requires_login(): void
    {
        $this->post(route('test.submit'), $this->payload());

        $this->get(route('admin.attempts.show', Attempt::sole()))->assertRedirect(route('admin.login'));
    }

    /**
     * Bài làm mẫu HS01 (R3 I6 A8 S5 E1 C2 trên 9 câu mỗi nhóm) + thông tin hợp lệ.
     *
     * @param  array<string, mixed>  $infoOverrides
     * @return array<string, mixed>
     */
    private function payload(array $infoOverrides = []): array
    {
        $correct = ['R' => 3, 'I' => 6, 'A' => 8, 'S' => 5, 'E' => 1, 'C' => 2];
        $seen = array_fill_keys(array_keys($correct), 0);
        $answers = [];

        foreach (Question::part('B')->active()->ordered()->get() as $question) {
            $answers[$question->code] = $correct[$question->riasec] > $seen[$question->riasec]++ ? '1' : '0';
        }

        return ['answers' => $answers, 'info' => array_merge($this->info(), $infoOverrides)];
    }

    /**
     * @return array<string, mixed>
     */
    private function info(): array
    {
        return [
            'A02' => 'Nguyễn Thị Lan', 'A19' => '2009-05-20', 'A05' => 'Lan@Example.com', 'A04' => '+84 912 345 678',
            'A01' => 'HS', 'A07' => 'Lớp 11', 'A20' => 'THPT Lê Hồng Phong', 'A21' => 'TP. Hồ Chí Minh',
            'A06' => 'Trần Văn Ba', 'A22' => 'ba@example.com', 'A15' => '1',
        ];
    }
}
