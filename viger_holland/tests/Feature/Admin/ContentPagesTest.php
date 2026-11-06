<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivity;
use App\Models\Media;
use App\Models\User;
use App\Support\ContentPages;
use App\Support\IntroBlocks;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Giai đoạn 3c: nội dung trang sửa trên admin, xem trước, lịch sử, thư viện ảnh, các khối trang giới thiệu.
 */
class ContentPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    /**
     * Dữ liệu form = giá trị hiện tại của trang + thay đổi.
     *
     * @return array<string, mixed>
     */
    private function form(string $page, array $changes = []): array
    {
        $config = ContentPages::find($page);
        $values = ContentPages::currentValues($config);

        foreach (ContentPages::fields($config) as $field) {
            if ($field['type'] === 'links') {
                $values[$field['key']] = ContentPages::links($values[$field['key']]);
            }
        }

        return array_replace($values, $changes);
    }

    public function test_every_content_page_renders(): void
    {
        foreach (array_keys(config('admin_content')) as $page) {
            $this->get(route('admin.content.edit', $page))->assertOk()->assertSee('Xem trước')->assertSee('Lịch sử thay đổi');
        }

        $this->get(route('admin.content.edit', 'embed'))->assertSee(route('embed.script'));
    }

    public function test_save_intro_banner_shows_on_home_and_records_history(): void
    {
        $this->put(route('admin.content.update', 'intro'), $this->form('intro', ['intro_title' => 'Chọn ngành đúng ngay từ đầu']))
            ->assertSessionHasNoErrors();

        $this->get(route('home'))->assertSee('Chọn ngành đúng ngay từ đầu');

        $activity = AdminActivity::where('subject_type', 'settings.intro')->sole();
        $this->assertSame(['intro_title' => 'Bạn phù hợp với ngành học nào?'], $activity->before);

        $this->post(route('admin.history.revert', $activity));
        $this->get(route('home'))->assertSee('Bạn phù hợp với ngành học nào?');
    }

    public function test_preview_shows_unsaved_text_without_saving(): void
    {
        $this->post(route('admin.content.preview', 'intro'), $this->form('intro', ['intro_title' => 'Tiêu đề thử']))
            ->assertOk()->assertSee('Tiêu đề thử');
        $this->assertSame('Bạn phù hợp với ngành học nào?', setting('intro_title'));

        $this->post(route('admin.content.preview', 'result-page'), $this->form('result-page', ['result_greeting' => 'Xin chào']))
            ->assertOk()->assertSee('Xin chào An')->assertSee('AIS');

        $this->post(route('admin.content.preview', 'email'), $this->form('email', ['email_closing' => '<p>Hẹn gặp bạn</p>']))
            ->assertOk()->assertSee('Hẹn gặp bạn');
    }

    public function test_rich_text_is_sanitized_and_links_saved_as_list(): void
    {
        $this->put(route('admin.content.update', 'result-page'), $this->form('result-page', [
            'result_contact_text' => '<p>Gọi <strong>ngay</strong><script>alert(1)</script></p>',
        ]))->assertSessionHasNoErrors();
        $this->assertSame('<p>Gọi <strong>ngay</strong></p>', setting('result_contact_text'));

        $this->put(route('admin.content.update', 'appearance'), $this->form('appearance', [
            'header_menu' => [['label' => 'Du học Đức', 'url' => 'viger.edu.vn/du-hoc-duc', 'new_tab' => '1'], ['label' => '', 'url' => '']],
            'hotline' => '0909 123 456',
        ]))->assertSessionHasNoErrors();

        $this->assertSame([['label' => 'Du học Đức', 'url' => 'https://viger.edu.vn/du-hoc-duc', 'new_tab' => true]], ContentPages::links(setting('header_menu')));
        $this->get(route('home'))->assertSee('Du học Đức')->assertSee('0909 123 456')->assertSee('Facebook');
    }

    public function test_validation_messages_are_vietnamese(): void
    {
        $this->put(route('admin.content.update', 'appearance'), $this->form('appearance', [
            'primary_color' => 'đỏ', 'header_menu' => [['label' => 'Sai', 'url' => 'viger']], 'site_name' => '',
        ]))->assertSessionHasErrors([
            'primary_color' => 'Ô "Màu chủ đạo" chưa đúng dạng.',
            'header_menu.0.url' => 'Địa chỉ link chưa đúng. Ví dụ: viger.edu.vn hoặc viger.edu.vn/du-hoc-duc',
            'site_name' => 'Vui lòng điền ô "Tên đơn vị (đầu trang)".',
        ]);
    }

    public function test_media_upload_resizes_and_is_selectable(): void
    {
        Storage::fake('public');

        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('banner.jpg', 2600, 1300)]])
            ->assertSessionHasNoErrors();

        $media = Media::sole();
        $this->assertSame(1920, $media->width);
        $this->assertSame(960, $media->height);
        Storage::disk('public')->assertExists(substr($media->path, strlen('storage/')));

        $this->getJson(route('admin.media.index'))->assertOk()->assertJsonPath('items.0.path', $media->path);

        $this->put(route('admin.content.update', 'intro'), $this->form('intro', ['banner_image' => $media->path]))->assertSessionHasNoErrors();
        $this->get(route('home'))->assertSee($media->path);

        $this->delete(route('admin.media.destroy', $media));
        $this->assertSoftDeleted($media);
        $this->post(route('admin.media.restore', $media->id));
        $this->assertNotSoftDeleted($media);
    }

    public function test_media_rejects_non_images(): void
    {
        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->create('virus.php', 10, 'text/plain')]])
            ->assertSessionHasErrors(['files.0' => 'Chỉ nhận ảnh JPG, PNG, WebP hoặc GIF.']);
    }

    public function test_intro_blocks_add_edit_toggle_reorder_delete_and_revert(): void
    {
        $this->get(route('home'))->assertSee('Cách làm bài')->assertSee('Câu hỏi thường gặp')->assertSee('Làm bài trắc nghiệm có mất phí không?');
        $this->get(route('admin.intro-blocks.index'))->assertOk()->assertSee('6 nhóm Holland');

        $this->post(route('admin.intro-blocks.store'), ['type' => 'testimonials']);
        $block = collect(IntroBlocks::all())->firstWhere('type', 'testimonials');
        $this->assertFalse($block['enabled'], 'Khối mới đang tắt');

        $this->get(route('admin.intro-blocks.edit', $block['id']))->assertOk()->assertSee('+ Thêm dòng');

        $data = ['title' => 'Phụ huynh nói gì', 'enabled' => '1', 'items' => [
            ['quote' => 'Con tôi chọn được ngành phù hợp.', 'name' => 'Chị Hoa', 'role' => 'Phụ huynh, Đà Nẵng'],
            ['quote' => '', 'name' => '', 'role' => ''],
        ]];
        $this->post(route('admin.intro-blocks.preview', $block['id']), $data)->assertOk()->assertSee('Con tôi chọn được ngành phù hợp.');
        $this->get(route('home'))->assertDontSee('Con tôi chọn được ngành phù hợp.');

        $this->put(route('admin.intro-blocks.update', $block['id']), $data)->assertSessionHasNoErrors();
        $this->get(route('home'))->assertSee('Phụ huynh nói gì')->assertSee('Chị Hoa');
        $this->assertCount(1, IntroBlocks::find($block['id'])['data']['items']);

        $ids = array_column(IntroBlocks::all(), 'id');
        $this->post(route('admin.intro-blocks.reorder'), ['order' => array_reverse($ids)]);
        $this->assertSame(array_reverse($ids), array_column(IntroBlocks::all(), 'id'));

        $this->patch(route('admin.intro-blocks.toggle', $block['id']));
        $this->get(route('home'))->assertDontSee('Chị Hoa');

        $this->delete(route('admin.intro-blocks.destroy', $block['id']));
        $this->assertNull(IntroBlocks::find($block['id']));
        $this->post(route('admin.history.revert', AdminActivity::where('subject_type', IntroBlocks::SUBJECT)->latest('id')->first()));
        $this->assertNotNull(IntroBlocks::find($block['id']), 'Quay lại bản trước khi xóa');
    }

    public function test_text_image_block_requires_https_button_url(): void
    {
        $this->post(route('admin.intro-blocks.store'), ['type' => 'text_image']);
        $block = collect(IntroBlocks::all())->firstWhere('type', 'text_image');

        $this->put(route('admin.intro-blocks.update', $block['id']), ['title' => 'Vì sao du học Đức', 'button_text' => 'Tìm hiểu', 'button_url' => 'abc'])
            ->assertSessionHasErrors(['button_url' => 'Địa chỉ nút chưa đúng. Ví dụ: viger.edu.vn/du-hoc-duc']);

        $this->put(route('admin.intro-blocks.update', $block['id']), [
            'title' => 'Vì sao du học Đức', 'body' => '<p>Nhiều trường <strong>công lập</strong></p><script>x</script>', 'enabled' => '1',
        ])->assertSessionHasNoErrors();

        $this->get(route('home'))->assertSee('Vì sao du học Đức')->assertSee('<strong>công lập</strong>', false)->assertDontSee('<script>x', false);
    }
}
