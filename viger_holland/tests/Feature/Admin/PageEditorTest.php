<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivity;
use App\Models\User;
use App\Support\IntroBlocks;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Sửa trực tiếp trên trang": mở trang khách ở chế độ sửa, lưu chữ / ảnh / khối, kiểm tra quyền.
 */
class PageEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_edit_mode_marks_editable_text_only_for_logged_in_editor_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.page-editor.show', 'trang-gioi-thieu'))->assertOk()
            ->assertSee('data-page-editor', false)
            ->assertSee('Đang sửa: Trang mở đầu')
            ->assertSee('data-ed="intro_title"', false)
            ->assertSee('data-ed="block:faq01:items.0.question"', false)
            ->assertSee('data-ed="group_name_R"', false)
            ->assertSee('data-ed="hotline"', false)
            ->assertSee('data-ed-image="banner_image"', false)
            ->assertSee('js/page-editor.js', false);

        // Trang báo cáo và email: làm lại ở GĐ3.
        $this->get('/admin/sua-trang/trang-ket-qua')->assertNotFound();

        $this->get(route('home'))->assertOk()->assertDontSee('data-ed=', false)->assertDontSee('page-editor.js', false);
    }

    public function test_save_changes_text_rich_blocks_and_image_with_history(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('admin.page-editor.save'), ['changes' => [
            'intro_title' => "  Chọn ngành\n đúng  ",
            'group_name_R' => 'Kỹ thuật – Thực hành',
            'block:faq01:items.0.question' => 'Có mất phí không?',
            'block:faq01:items.0.answer' => '<p>Không <img src=x onerror=alert(1)></p>',
            'banner_image' => 'storage/media/2026/11/abc.jpg',
            'hotline' => '0909 123 456',
        ]])->assertOk()->assertJsonPath('saved', 6);

        $this->assertSame('Chọn ngành đúng', setting('intro_title'));
        $this->assertSame('Kỹ thuật – Thực hành', setting('group_name_R'));
        $this->assertSame('storage/media/2026/11/abc.jpg', setting('banner_image'));
        $faq = IntroBlocks::find('faq01');
        $this->assertSame('Có mất phí không?', $faq['data']['items'][0]['question']);
        $this->assertSame('<p>Không </p>', $faq['data']['items'][0]['answer']);

        $this->get(route('home'))->assertSee('Chọn ngành đúng')->assertSee('0909 123 456')->assertDontSee('Có mất phí không?', 'Khối câu hỏi thường gặp đang tắt');
        $this->patch(route('admin.intro-blocks.toggle', 'faq01'));
        $this->get(route('home'))->assertSee('Có mất phí không?');

        $intro = AdminActivity::where('subject_type', 'settings.intro')->sole();
        $this->post(route('admin.history.revert', $intro));
        $this->assertSame('Bạn hợp với hướng ngành nào khi', setting('intro_title'));
    }

    public function test_save_rejects_unknown_keys_empty_required_and_bad_images(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('admin.page-editor.save'), ['changes' => ['primary_color' => '#000000']])->assertStatus(422);
        $this->postJson(route('admin.page-editor.save'), ['changes' => ['intro_title' => '   ']])
            ->assertStatus(422)->assertJsonValidationErrors(['intro_title' => 'Ô này không được để trống.']);
        $this->postJson(route('admin.page-editor.save'), ['changes' => ['banner_image' => 'javascript:alert(1)']])->assertStatus(422);
        $this->postJson(route('admin.page-editor.save'), ['changes' => ['block:faq01:data.evil' => 'x']])->assertStatus(422);
    }

    public function test_staff_can_edit_content_but_not_footer_contact(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));

        $this->get(route('admin.page-editor.show', 'trang-gioi-thieu'))->assertOk()
            ->assertSee('data-ed="intro_title"', false)
            ->assertDontSee('data-ed="hotline"', false);

        $this->postJson(route('admin.page-editor.save'), ['changes' => ['intro_title' => 'Tiêu đề mới']])->assertOk();
        $this->postJson(route('admin.page-editor.save'), ['changes' => ['hotline' => '0909']])
            ->assertStatus(422)->assertJsonValidationErrors(['hotline' => 'Bạn không có quyền sửa nội dung này.']);
    }
}
