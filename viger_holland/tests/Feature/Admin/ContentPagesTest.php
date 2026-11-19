<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActivity;
use App\Models\Media;
use App\Models\User;
use App\Modules\Content\Filament\Pages\Appearance;
use App\Modules\Content\Filament\Pages\EmbedBox;
use App\Modules\Content\Filament\Pages\IntroPage;
use App\Modules\Content\Filament\Pages\MediaLibrary;
use App\Modules\Content\Filament\Pages\ReportTexts;
use App\Modules\Content\Filament\Pages\SeoSettings;
use App\Support\IntroBlocks;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Trang nội dung khách xem trên Filament (module Content): sửa, xem trước, lịch sử và quay lại, kho ảnh, các khối trang mở đầu.
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

    public function test_every_content_page_renders_with_history_and_preview_only_where_configured(): void
    {
        foreach ([IntroPage::class, ReportTexts::class, EmbedBox::class, Appearance::class, SeoSettings::class] as $page) {
            $response = $this->get($page::getUrl())->assertOk()->assertSee('Lịch sử thay đổi');
            filled($page::contentConfig()['preview'] ?? null) ? $response->assertSee('Xem trước') : $response->assertDontSee('Xem trước');
        }

        $this->get(MediaLibrary::getUrl())->assertOk()->assertSee('Kho ảnh');
        $this->get(IntroPage::getUrl())->assertSee('Sửa trực tiếp trên trang')->assertSee('Các khối nội dung');
    }

    public function test_save_intro_banner_shows_on_home_records_history_and_reverts(): void
    {
        Livewire::test(IntroPage::class)
            ->assertSchemaStateSet(['intro_title' => 'Bạn hợp với hướng ngành nào khi'], 'form')
            ->fillForm(['intro_title' => 'Chọn ngành đúng ngay từ đầu'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Đã lưu');

        $this->get(route('home'))->assertSee('Chọn ngành đúng ngay từ đầu');

        $activity = AdminActivity::where('subject_type', 'settings.intro')->sole();
        $this->assertSame(['intro_title' => 'Bạn hợp với hướng ngành nào khi'], $activity->before);

        Livewire::test(IntroPage::class)
            ->assertCanSeeTableRecords([$activity])
            ->callTableAction('revert', $activity)
            ->assertSchemaStateSet(['intro_title' => 'Bạn hợp với hướng ngành nào khi'], 'form');
        $this->get(route('home'))->assertSee('Bạn hợp với hướng ngành nào khi');
    }

    public function test_preview_opens_unsaved_text_in_new_tab_only_for_the_same_person(): void
    {
        $url = $this->previewUrl(Livewire::test(IntroPage::class)->fillForm(['intro_title' => 'Tiêu đề thử'])->call('preview'));

        $this->get($url)->assertOk()->assertSee('Tiêu đề thử')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertSame('Bạn hợp với hướng ngành nào khi', setting('intro_title'));

        $url = $this->previewUrl(Livewire::test(EmbedBox::class)->fillForm(['embed_title' => 'Khối nhúng thử'])->call('preview'));
        $this->get($url)->assertOk()->assertSee('Khối nhúng thử');
        $this->assertNotSame('Khối nhúng thử', setting('embed_title'));

        $this->actingAs(User::factory()->create());
        $this->get($url)->assertNotFound();
        $this->get(route('admin.content.preview', str_repeat('a', 40)))->assertNotFound();
    }

    public function test_validation_messages_are_vietnamese_and_nothing_is_saved(): void
    {
        Livewire::test(Appearance::class)
            ->fillForm(['primary_color' => 'đỏ', 'site_name' => ''])
            ->call('save')
            ->assertHasFormErrors([
                'primary_color' => 'Ô "Màu chủ đạo" chưa đúng dạng.',
                'site_name' => 'Vui lòng điền ô "Tên đơn vị".',
            ]);

        $this->assertNotSame('đỏ', setting('primary_color'));
    }

    public function test_link_without_https_is_completed(): void
    {
        Livewire::test(Appearance::class)->fillForm(['main_site_url' => 'viger.edu.vn'])->call('save')->assertHasNoFormErrors();

        $this->assertSame('https://viger.edu.vn', setting('main_site_url'));
    }

    public function test_media_upload_resizes_lists_in_picker_and_goes_to_trash(): void
    {
        Storage::fake('public');

        Livewire::test(MediaLibrary::class)
            ->callAction('upload', data: ['files' => [UploadedFile::fake()->image('banner.jpg', 2600, 1300)]])
            ->assertHasNoActionErrors()
            ->assertNotified('Đã tải lên 1 ảnh');

        $media = Media::sole();
        $this->assertSame(1920, $media->width);
        $this->assertSame(960, $media->height);
        Storage::disk('public')->assertExists(substr($media->path, strlen('storage/')));

        // Hộp "Chọn ảnh" của chế độ sửa trực tiếp vẫn đọc được kho.
        $this->getJson(route('admin.media.index'))->assertOk()->assertJsonPath('items.0.path', $media->path);

        Livewire::test(IntroPage::class)->fillForm(['banner_image' => $media->path])->call('save')->assertHasNoFormErrors();
        $this->get(route('home'))->assertSee($media->path);

        Livewire::test(MediaLibrary::class)->assertCanSeeTableRecords([$media])->callTableAction('delete', $media);
        $this->assertSoftDeleted($media);
        Livewire::test(MediaLibrary::class)->filterTable('trashed', false)->callTableAction('restore', $media);
        $this->assertNotSoftDeleted($media);
    }

    public function test_media_rejects_non_images(): void
    {
        Storage::fake('public');

        Livewire::test(MediaLibrary::class)
            ->callAction('upload', data: ['files' => [UploadedFile::fake()->create('virus.php', 10, 'text/plain')]])
            ->assertHasActionErrors();
        $this->assertSame(0, Media::count());

        $this->postJson(route('admin.media.store'), ['files' => [UploadedFile::fake()->create('virus.php', 10, 'text/plain')]])
            ->assertJsonValidationErrors(['files.0' => 'Chỉ nhận ảnh JPG, PNG, WebP hoặc GIF.']);
    }

    public function test_intro_blocks_add_reorder_delete_preview_and_revert(): void
    {
        // Trang bắt đầu đã chốt không có các khối cũ: mặc định tắt, admin bật lại được.
        $this->get(route('home'))->assertDontSee('Câu hỏi thường gặp')->assertDontSee('Làm bài trắc nghiệm có mất phí không?');
        $original = array_column(IntroBlocks::all(), 'id');

        $blocks = Livewire::test(IntroPage::class)->get('blocksData')['blocks'];
        $this->assertCount(3, $blocks);

        // Thêm khối cảm nhận (đang bật) ở đầu danh sách; dòng trống tự bỏ.
        $blocks = ['moi' => ['type' => 'testimonials', 'data' => [
            'block_id' => null, 'enabled' => true, 'title' => 'Phụ huynh nói gì',
            'items' => [
                ['quote' => 'Con tôi chọn được ngành phù hợp.', 'name' => 'Chị Hoa', 'role' => 'Phụ huynh, Đà Nẵng'],
                ['quote' => '', 'name' => '', 'role' => ''],
            ],
        ]]] + $blocks;

        $url = $this->previewUrl(Livewire::test(IntroPage::class)->set('blocksData.blocks', $blocks)->call('previewBlocks'));
        $this->get($url)->assertOk()->assertSee('Con tôi chọn được ngành phù hợp.');
        $this->get(route('home'))->assertDontSee('Con tôi chọn được ngành phù hợp.');

        Livewire::test(IntroPage::class)->set('blocksData.blocks', $blocks)->call('saveBlocks')
            ->assertHasNoErrors()->assertNotified('Đã lưu các khối');

        $saved = IntroBlocks::all();
        $this->assertSame('testimonials', $saved[0]['type'], 'Khối mới đứng đầu theo thứ tự kéo thả');
        $this->assertSame($original, array_column(array_slice($saved, 1), 'id'), 'Khối cũ giữ nguyên mã');
        $this->assertCount(1, $saved[0]['data']['items']);
        $this->get(route('home'))->assertSee('Phụ huynh nói gì')->assertSee('Chị Hoa');

        // Xóa khối mới → trang khách mất khối; quay lại bản trước → có lại.
        $current = Livewire::test(IntroPage::class)->get('blocksData')['blocks'];
        Livewire::test(IntroPage::class)->set('blocksData.blocks', array_slice($current, 1, null, true))->call('saveBlocks');
        $this->get(route('home'))->assertDontSee('Chị Hoa');

        $last = AdminActivity::where('subject_type', IntroBlocks::SUBJECT)->latest('id')->first();
        Livewire::test(IntroPage::class)->callTableAction('revert', $last);
        $this->get(route('home'))->assertSee('Chị Hoa');
    }

    public function test_text_image_block_requires_valid_button_url_and_strips_scripts(): void
    {
        $block = fn (array $data) => ['x' => ['type' => 'text_image', 'data' => ['block_id' => null, 'enabled' => true, 'title' => 'Vì sao du học Đức'] + $data]];

        Livewire::test(IntroPage::class)
            ->set('blocksData.blocks', $block(['button_text' => 'Tìm hiểu', 'button_url' => 'ab c']))
            ->call('saveBlocks')
            ->assertHasErrors(['blocksData.blocks']);
        $this->assertCount(3, IntroBlocks::all(), 'Lỗi thì không lưu');

        Livewire::test(IntroPage::class)
            ->set('blocksData.blocks', $block(['body' => '<p>Nhiều trường <strong>công lập</strong></p><script>x</script>', 'button_text' => 'Tìm hiểu', 'button_url' => 'viger.edu.vn/du-hoc-duc']))
            ->call('saveBlocks')
            ->assertHasNoErrors();

        $this->get(route('home'))->assertSee('Vì sao du học Đức')->assertSee('<strong>công lập</strong>', false)
            ->assertDontSee('<script>x', false)->assertSee('https://viger.edu.vn/du-hoc-duc', false);
    }

    public function test_embed_code_is_tucked_away_for_admin_and_hidden_for_staff(): void
    {
        $this->get(EmbedBox::getUrl())->assertOk()->assertSee('Dành cho người làm web chính')->assertSee('embed.js', false);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(EmbedBox::getUrl())->assertOk()->assertDontSee('Dành cho người làm web chính')->assertDontSee('embed.js', false);
    }

    /**
     * Địa chỉ trang xem trước mà nút "Xem trước" mở ở thẻ mới.
     */
    private function previewUrl(Testable $component): string
    {
        $script = collect($component->effects['xjs'] ?? [])
            ->map(fn ($item) => is_array($item) ? ($item['expression'] ?? '') : (string) $item)
            ->first(fn (string $expression) => str_starts_with($expression, 'window.open('));
        $this->assertNotNull($script, 'Nút "Xem trước" mở thẻ mới');
        preg_match('#^window\.open\(("[^"]+")#', $script, $match);

        return json_decode($match[1]);
    }
}
