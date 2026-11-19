<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageHomePage;
use App\Models\Banner;
use App\Models\Setting;
use App\Models\User;
use App\Support\DefaultBanners;
use App\Support\HomePage;
use Database\Seeders\BannerSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');
    }

    public function test_home_page_shows_vietnamese_content_by_default(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Trang chủ', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('Hành trình của bạn')
            ->assertSee('Học viên nhập học')
            ->assertSee('Miễn học phí – Chất lượng quốc tế')
            ->assertDontSee('Your journey');
    }

    public function test_home_page_shows_english_content_in_english_mode(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Home', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('Your journey')
            ->assertSee('Students enrolled')
            ->assertSee('5 steps to a German university')
            ->assertSee('Journeys we are proud of')
            ->assertSee('Duc Nguyen')
            ->assertSee('Tuition-free – World-class quality')
            ->assertSee('alt="University campus"', false)
            ->assertDontSee('Hành trình của bạn')
            ->assertDontSee('Học viên nhập học')
            ->assertDontSee('Miễn học phí – Chất lượng quốc tế');
    }

    public function test_list_translation_keeps_shared_numbers_and_names_from_vietnamese(): void
    {
        Setting::set('home_hero_stats', [
            ['number' => 700, 'suffix' => '+', 'label' => 'Học viên'],
            ['number' => 98, 'suffix' => '%', 'label' => 'Visa'],
        ], 'home', 'json');
        Setting::set('home_hero_stats_en', [['label' => 'Students']], 'home', 'json');

        $stats = HomePage::get('home_hero_stats', 'en');

        $this->assertSame(['number' => 700, 'suffix' => '+', 'label' => 'Students'], $stats[0]);
        // Mục chưa dịch => giữ bản tiếng Việt
        $this->assertSame(['number' => 98, 'suffix' => '%', 'label' => 'Visa'], $stats[1]);
    }

    public function test_admin_edits_english_version_without_touching_vietnamese(): void
    {
        $this->actingAsAdmin();

        $page = Livewire::withQueryParams(['lang' => 'en'])
            ->test(ManageHomePage::class)
            ->assertSchemaStateSet([
                'home_hero_heading' => 'Your journey',
            ]);

        // Danh sách: con số lấy từ bản tiếng Việt, chữ là bản tiếng Anh
        $firstStat = array_values($page->get('data.home_hero_stats'))[0];
        $this->assertEquals(500, $firstStat['number']);
        $this->assertSame('Students enrolled', $firstStat['label']);

        $page
            ->fillForm([
                'home_hero_heading' => 'Your German adventure',
                'home_hero_button_url' => '/hacked',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Your German adventure', Setting::get('home_hero_heading_en'));
        $this->assertNull(Setting::get('home_hero_heading'));
        $this->assertNull(Setting::get('home_hero_button_url_en'));
        $this->assertNull(Setting::get('home_hero_button_url'));
        $this->assertSame([['label' => 'Students enrolled'], ['label' => 'Visa success rate'], ['label' => 'Years of experience']], json_decode(Setting::get('home_hero_stats_en'), true));

        $this->withSession(['locale' => 'en'])->get('/')->assertSee('Your German adventure');
        $this->withSession(['locale' => 'vi'])->get('/')->assertSee('Hành trình của bạn')->assertDontSee('Your German adventure');
    }

    public function test_admin_vietnamese_mode_still_saves_main_settings(): void
    {
        $this->actingAsAdmin();

        Livewire::test(ManageHomePage::class)
            ->assertSchemaStateSet(['home_hero_heading' => 'Hành trình của bạn'])
            ->fillForm(['home_hero_heading' => 'Chinh phục nước Đức'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Chinh phục nước Đức', Setting::get('home_hero_heading'));
        $this->assertNull(Setting::get('home_hero_heading_en'));
        $this->withSession(['locale' => 'en'])->get('/')->assertSee('Your journey');
    }

    public function test_banner_slider_uses_english_translations(): void
    {
        $this->seed(BannerSeeder::class);

        $this->withSession(['locale' => 'en'])->get('/')
            ->assertSee('Study today, work in Europe tomorrow')
            ->assertDontSee('Học tập hôm nay, làm việc tại Châu Âu ngày mai');

        $this->withSession(['locale' => 'vi'])->get('/')
            ->assertSee('Học tập hôm nay, làm việc tại Châu Âu ngày mai');
    }

    public function test_banner_migration_adds_english_to_existing_default_banners_only(): void
    {
        $slide = DefaultBanners::SLIDES[0];
        $defaultId = DB::table('banners')->insertGetId([
            'image' => $slide['image'],
            'title' => json_encode(['vi' => $slide['vi']['title']]),
            'desc' => json_encode(['vi' => $slide['vi']['desc']]),
            'button_secondary_label' => null,
        ]);
        $customId = DB::table('banners')->insertGetId([
            'image' => DefaultBanners::SLIDES[1]['image'],
            'title' => json_encode(['vi' => 'Tự viết', 'en' => 'Custom English']),
        ]);

        $migration = require database_path('migrations/2026_11_12_083654_add_english_translations_to_default_banners.php');
        $migration->up();

        $default = Banner::find($defaultId);
        $this->assertSame($slide['en']['title'], $default->getTranslation('title', 'en'));
        $this->assertSame($slide['vi']['title'], $default->getTranslation('title', 'vi'));
        $this->assertSame($slide['en']['button_secondary_label'], $default->getTranslation('button_secondary_label', 'en'));
        $this->assertSame(['vi' => 'Tự viết', 'en' => 'Custom English'], Banner::find($customId)->getTranslations('title'));
    }
}
