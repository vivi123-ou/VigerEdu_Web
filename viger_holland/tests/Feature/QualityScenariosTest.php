<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Models\User;
use App\Support\IntroBlocks;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kiểm thử ~100 tình huống dùng thật trên trang khách và trang quản trị:
 * mở mọi trang, phân quyền, nhập sai / bỏ trống / quá dài, chèn mã độc, đường dẫn sai.
 */
class QualityScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->withoutDefer();
        Mail::fake();
        $this->resetSubmitLimit();
    }

    /**
     * Xóa bộ đếm "5 lần nộp bài / phút" (khóa = md5(tên giới hạn + IP)).
     */
    private function resetSubmitLimit(): void
    {
        RateLimiter::clear(md5('submit-test127.0.0.1'));
        RateLimiter::clear('submit-test:127.0.0.1');
    }

    /**
     * @return array<string, string>
     */
    private function adminPages(): array
    {
        $set = QuestionSet::where('is_default', true)->sole();
        $holland = $set->questions()->first();
        $info = Question::where('code', 'A02')->sole();

        $this->post(route('test.submit'), $this->submission());
        $attempt = Attempt::firstOrFail();

        return [
            'Bảng điều khiển' => route('admin.dashboard'),
            'Danh sách bộ câu' => route('admin.question-sets.index'),
            'Thêm bộ câu' => route('admin.question-sets.create'),
            'Câu hỏi trong bộ' => route('admin.question-sets.show', $set),
            'Cài đặt bộ' => route('admin.question-sets.edit', $set),
            'Thêm câu Holland' => route('admin.question-sets.questions.create', $set),
            'Sửa câu Holland' => route('admin.holland-questions.edit', $holland),
            'Thẻ câu Holland' => route('admin.holland-questions.card', $holland),
            'Form thông tin khách' => route('admin.info-questions.index'),
            'Thêm câu thông tin' => route('admin.info-questions.create'),
            'Sửa câu thông tin' => route('admin.info-questions.edit', $info),
            'Thẻ câu thông tin' => route('admin.info-questions.card', $info),
            'Cấu hình chấm điểm' => route('admin.scoring.edit'),
            'Mô tả 6 nhóm' => route('admin.groups.edit'),
            'Excel' => route('admin.excel.index'),
            'Banner (biểu mẫu)' => route('admin.content.edit', 'intro'),
            'Trang kết quả (biểu mẫu)' => route('admin.content.edit', 'result-page'),
            'Email (biểu mẫu)' => route('admin.content.edit', 'email'),
            'Nhúng web chính' => route('admin.content.edit', 'embed'),
            'Giao diện & chân trang' => route('admin.content.edit', 'appearance'),
            'Các khối nội dung' => route('admin.intro-blocks.index'),
            'Sửa khối FAQ' => route('admin.intro-blocks.edit', 'faq01'),
            'Thư viện ảnh' => route('admin.media.index'),
            'Lượt làm bài' => route('admin.attempts.index'),
            'Lọc lượt làm bài' => route('admin.attempts.index', ['ma' => 'ais', 'thoi_gian' => '7-ngay', 'bo' => $set->id]),
            'Chi tiết lượt làm bài' => route('admin.attempts.show', $attempt),
            'Lọc xếp hạng ngành' => route('admin.attempts.show', [$attempt, 'loai' => 'hoc-nghe']),
            'Sửa trên trang: giới thiệu' => route('admin.page-editor.show', 'trang-gioi-thieu'),
            'Sửa trên trang: kết quả' => route('admin.page-editor.show', 'trang-ket-qua'),
            'Sửa trên trang: email' => route('admin.page-editor.show', 'email'),
            'Mục sắp có: hàng đợi CRM' => route('admin.section', 'outbox'),
            'Mục sắp có: kết nối' => route('admin.section', 'connections'),
            'Mục sắp có: cài đặt' => route('admin.section', 'settings'),
            'Mục sắp có: tài khoản' => route('admin.section', 'accounts'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function submission(array $info = [], array $extra = []): array
    {
        $answers = QuestionSet::where('is_default', true)->sole()->questions()->pluck('code')
            ->mapWithKeys(fn (string $code, int $index) => [$code => $index % 3 === 0 ? '1' : '0'])->all();

        return ['answers' => $answers, 'info' => array_replace([
            'A02' => 'Trần Minh Khoa', 'A19' => '2008-03-15', 'A05' => 'khoa@example.com', 'A04' => '0912345678',
            'A01' => 'PH', 'A07' => 'Lớp 12', 'A20' => 'THPT Nguyễn Du', 'A21' => 'Hà Nội', 'A15' => '1',
        ], $info), ...$extra];
    }

    // ---------- A. Quản trị viên mở được mọi trang (34 tình huống) ----------

    public function test_admin_opens_every_page_without_error(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ($this->adminPages() as $name => $url) {
            $response = $this->get($url);
            $this->assertSame(200, $response->status(), "Trang \"{$name}\" phải mở được ({$url})");
            $this->assertStringNotContainsString('ErrorException', (string) $response->getContent(), $name);
        }
    }

    // ---------- B. Marketing chỉ vào được phần nội dung (34 tình huống) ----------

    public function test_marketing_role_sees_only_content_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_EDITOR]));
        $allowed = ['Bảng điều khiển', 'Mô tả 6 nhóm', 'Banner (biểu mẫu)', 'Trang kết quả (biểu mẫu)', 'Email (biểu mẫu)', 'Nhúng web chính',
            'Các khối nội dung', 'Sửa khối FAQ', 'Thư viện ảnh', 'Lượt làm bài', 'Lọc lượt làm bài', 'Chi tiết lượt làm bài', 'Lọc xếp hạng ngành',
            'Sửa trên trang: giới thiệu', 'Sửa trên trang: kết quả', 'Sửa trên trang: email'];

        foreach ($this->adminPages() as $name => $url) {
            $expected = in_array($name, $allowed, true) ? 200 : 403;
            $this->assertSame($expected, $this->get($url)->status(), "Marketing mở \"{$name}\" phải trả {$expected}");
        }
    }

    // ---------- C. Trang khách và đường dẫn sai (10 tình huống) ----------

    public function test_public_pages_and_wrong_links(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('test.show'))->assertOk();
        $this->get(route('test.show', ['bo' => 'bo-khong-ton-tai']))->assertOk()->assertSee('name="answers[B01]"', false);
        $this->get(route('test.show', ['xem_truoc' => 1, 'bo' => 'bo-khong-ton-tai']))->assertOk()->assertDontSee('Đang xem trước');
        $this->get(route('embed'))->assertOk();
        $this->get(route('embed.script'))->assertOk();
        $this->getJson(route('api.public.intro'))->assertOk();
        $this->get('/ket-qua/khong-phai-ma')->assertNotFound();
        $this->get('/ket-qua/'.Str::uuid())->assertNotFound();
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    // ---------- D. Khách nhập sai / bỏ trống / nhập lạ (14 tình huống) ----------

    public function test_customer_input_mistakes_are_caught_in_vietnamese(): void
    {
        $cases = [
            'bỏ trống họ tên' => [['A02' => ''], 'info.A02', 'Vui lòng điền thông tin này.'],
            'email sai dạng' => [['A05' => 'khoa@'], 'info.A05', 'Email chưa đúng (ví dụ: ten@gmail.com).'],
            'SĐT thiếu số' => [['A04' => '09123'], 'info.A04', 'Số điện thoại chưa đúng (cần 10 số, ví dụ 0912 345 678).'],
            'ngày sinh ở tương lai' => [['A19' => now()->addYear()->toDateString()], 'info.A19', 'Ngày sinh phải trước hôm nay.'],
            'ngày sinh sai dạng' => [['A19' => '15/03/2008'], 'info.A19', 'Ngày chưa đúng, vui lòng chọn lại.'],
            'lựa chọn bị sửa' => [['A07' => 'Lớp 99'], 'info.A07', 'Lựa chọn không hợp lệ, vui lòng chọn lại.'],
            'không đồng ý' => [['A15' => ''], 'info.A15', 'Bạn cần đồng ý để xem kết quả.'],
            'tên quá dài' => [['A02' => str_repeat('a', 300)], 'info.A02', 'Nội dung quá dài.'],
        ];

        foreach ($cases as $name => [$info, $field, $message]) {
            $this->resetSubmitLimit();
            $this->post(route('test.submit'), $this->submission($info))->assertSessionHasErrors([$field => $message]);
        }

        $this->resetSubmitLimit();
        $payload = $this->submission();
        unset($payload['answers']['B05']);
        $this->post(route('test.submit'), $payload)->assertSessionHasErrors(['answers.B05' => 'Bạn chưa trả lời câu này.']);

        $this->resetSubmitLimit();
        $payload = $this->submission();
        $payload['answers']['B01'] = '7';
        $this->post(route('test.submit'), $payload)->assertSessionHasErrors('answers.B01');

        $this->assertSame(0, Attempt::count(), 'Có lỗi thì không lưu lượt làm bài nào');

        // SĐT kiểu gì cũng nhận: +84, dấu cách, dấu chấm
        foreach (['+84 912 345 678', '0912.345.678', '0912 345 678'] as $phone) {
            $this->resetSubmitLimit();
            $this->post(route('test.submit'), $this->submission(['A04' => $phone]))->assertSessionHasNoErrors();
        }
        $this->assertSame(3, Attempt::count());

        // Gửi 6 lần trong 1 phút: lần thứ 6 bị chặn
        $this->resetSubmitLimit();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('test.submit'), $this->submission());
        }
        $this->post(route('test.submit'), $this->submission())->assertSessionHasErrors('submit');
    }

    // ---------- E. Mã độc và dữ liệu xấu không lọt ra trang (12 tình huống) ----------

    public function test_malicious_input_is_escaped_or_rejected(): void
    {
        $xss = '<script>alert("x")</script>';

        // Khách gõ mã độc vào ô chữ → trang admin hiện chữ thường, không chạy
        $this->post(route('test.submit'), $this->submission(['A20' => $xss]))->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.attempts.show', Attempt::sole()))->assertOk()->assertDontSee($xss, false);

        // Admin gõ mã độc vào câu hỏi / tiêu đề → trang khách không chạy mã
        $this->post(route('admin.info-questions.store'), ['text' => $xss, 'answer_type' => 'text', 'is_active' => '1', 'crm_target' => '_auto'])->assertSessionHasNoErrors();
        $this->post(route('admin.question-sets.questions.store', QuestionSet::where('is_default', true)->sole()), ['text' => $xss, 'answer_type' => 'yes_no', 'riasec' => 'R', 'is_active' => '1', 'is_required' => '1'])->assertSessionHasNoErrors();
        $this->postJson(route('admin.page-editor.save'), ['changes' => ['intro_title' => $xss, 'result_contact_text' => '<p onclick="x()">Hi</p>'.$xss]])->assertOk();
        $this->get(route('test.show'))->assertDontSee($xss, false)->assertSee('&lt;script&gt;', false);
        $this->get(route('home'))->assertDontSee($xss, false);
        $this->assertStringNotContainsString('onclick', (string) setting('result_contact_text'));

        // Link javascript: bị từ chối
        $this->post(route('admin.intro-blocks.store'), ['type' => 'text_image']);
        $block = collect(IntroBlocks::all())->firstWhere('type', 'text_image');
        $this->put(route('admin.intro-blocks.update', $block['id']), ['title' => 'A', 'button_text' => 'Bấm', 'button_url' => 'javascript:alert(1)'])->assertSessionHasErrors('button_url');

        // File giả ảnh, khóa lạ, ảnh đường dẫn lạ
        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->create('anh.jpg.php', 5, 'application/x-php')]])->assertSessionHasErrors('files.0');
        $this->postJson(route('admin.page-editor.save'), ['changes' => ['admin_password' => 'x']])->assertStatus(422);
        $this->postJson(route('admin.page-editor.save'), ['changes' => ['logo_path' => '../../.env']])->assertStatus(422);
        $this->postJson(route('admin.page-editor.save'), ['changes' => []])->assertStatus(422);

        // Không đăng nhập thì không lưu được gì
        auth()->logout();
        $this->postJson(route('admin.page-editor.save'), ['changes' => ['intro_title' => 'Hack']])->assertUnauthorized();
        $this->assertNotSame('Hack', setting('intro_title'));
    }
}
