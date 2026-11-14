<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageEducationPages;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EducationPagesHeroTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function pages(): array
    {
        return [
            'overview' => ['/giao-duc-tai-duc/tong-quan', 'TỔNG QUAN GIÁO DỤC ĐỨC', 'GERMAN EDUCATION OVERVIEW'],
            'education system' => ['/giao-duc-tai-duc/he-thong-giao-duc', 'HỆ THỐNG GIÁO DỤC ĐỨC', 'THE GERMAN EDUCATION SYSTEM'],
            'research applied' => ['/giao-duc-tai-duc/nghien-cuu-va-ung-dung', 'NGHIÊN CỨU VÀ ỨNG DỤNG', 'RESEARCH &amp; APPLIED SCIENCES'],
            'costs opportunities' => ['/giao-duc-tai-duc/chi-phi-va-co-hoi', 'CHI PHÍ &amp; CƠ HỘI', 'COSTS &amp; OPPORTUNITIES'],
            'career opportunities' => ['/giao-duc-tai-duc/co-hoi-nghe-nghiep', 'CƠ HỘI NGHỀ NGHIỆP TẠI ĐỨC', 'CAREER OPPORTUNITIES IN GERMANY'],
        ];
    }

    #[DataProvider('pages')]
    public function test_hero_shows_vietnamese_by_default_and_english_in_english_mode(string $url, string $vietnamese, string $english): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee($vietnamese, false)
            ->assertDontSee($english, false);

        $this->withSession(['locale' => 'en'])
            ->get($url)
            ->assertOk()
            ->assertSee($english, false)
            ->assertDontSee($vietnamese, false);
    }

    public function test_english_page_title_is_used(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/giao-duc-tai-duc/tong-quan')
            ->assertSeeInOrder(['<title>', 'German Education Overview', 'VIGER.EDU.VN</title>'], false);
    }

    public function test_admin_page_is_reachable(): void
    {
        $this->actingAsAdmin();

        $this->get(ManageEducationPages::getUrl())->assertOk();
        $this->get(ManageEducationPages::getUrl(['lang' => 'en']))->assertOk();
    }

    public function test_admin_vietnamese_mode_saves_main_settings(): void
    {
        $this->actingAsAdmin();

        Livewire::test(ManageEducationPages::class)
            ->assertSchemaStateSet(['education_overview_heading' => 'TỔNG QUAN GIÁO DỤC ĐỨC'])
            ->fillForm(['education_overview_heading' => 'GIÁO DỤC ĐỨC TOÀN CẢNH'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('GIÁO DỤC ĐỨC TOÀN CẢNH', Setting::get('education_overview_heading'));
        $this->assertNull(Setting::get('education_overview_heading_en'));

        $this->get('/giao-duc-tai-duc/tong-quan')->assertSee('GIÁO DỤC ĐỨC TOÀN CẢNH');
        $this->withSession(['locale' => 'en'])->get('/giao-duc-tai-duc/tong-quan')
            ->assertSee('GERMAN EDUCATION OVERVIEW')
            ->assertDontSee('GIÁO DỤC ĐỨC TOÀN CẢNH');
    }

    public function test_admin_edits_english_version_without_touching_vietnamese(): void
    {
        $this->actingAsAdmin();

        Livewire::withQueryParams(['lang' => 'en'])
            ->test(ManageEducationPages::class)
            ->assertSchemaStateSet([
                'education_career_opportunities_heading' => 'CAREER OPPORTUNITIES IN GERMANY',
            ])
            ->fillForm([
                'education_career_opportunities_heading' => 'WORK IN GERMANY',
                'education_career_opportunities_description' => 'Jobs and residence for graduates.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('WORK IN GERMANY', Setting::get('education_career_opportunities_heading_en'));
        $this->assertNull(Setting::get('education_career_opportunities_heading'));
        $this->assertNull(Setting::get('education_career_opportunities_image_en'));

        $this->withSession(['locale' => 'en'])->get('/giao-duc-tai-duc/co-hoi-nghe-nghiep')
            ->assertSee('WORK IN GERMANY')
            ->assertSee('Jobs and residence for graduates.');
        $this->withSession(['locale' => 'vi'])->get('/giao-duc-tai-duc/co-hoi-nghe-nghiep')
            ->assertSee('CƠ HỘI NGHỀ NGHIỆP TẠI ĐỨC')
            ->assertDontSee('WORK IN GERMANY');
    }
}
