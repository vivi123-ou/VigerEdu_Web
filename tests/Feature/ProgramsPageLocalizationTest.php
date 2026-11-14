<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageProgramsPage;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramsPageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');
    }

    public function test_programs_page_shows_vietnamese_by_default(): void
    {
        $this->get('/chuong-trinh-hoc')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Chương trình Đào tạo', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('CHƯƠNG TRÌNH ĐÀO TẠO')
            ->assertSee('Chưa biết chọn chương trình nào?')
            ->assertSee('Nhận tư vấn ngay')
            ->assertDontSee('STUDY PROGRAMS');
    }

    public function test_programs_page_shows_english_in_english_mode(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/chuong-trinh-hoc')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Study Programs', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('STUDY PROGRAMS')
            ->assertSee('A Wide Range of Options')
            ->assertSee('Not sure which program to choose?')
            ->assertSee('Get free advice')
            ->assertDontSee('CHƯƠNG TRÌNH ĐÀO TẠO')
            ->assertDontSee('Chưa biết chọn chương trình nào?')
            ->assertDontSee('Nhận tư vấn ngay');
    }

    public function test_admin_page_is_reachable(): void
    {
        $this->actingAsAdmin();

        $this->get(ManageProgramsPage::getUrl())->assertOk()->assertSee('Khối kêu gọi cuối trang');
        $this->get(ManageProgramsPage::getUrl(['lang' => 'en']))->assertOk();
    }

    public function test_admin_vietnamese_mode_saves_hero_and_cta(): void
    {
        $this->actingAsAdmin();

        Livewire::test(ManageProgramsPage::class)
            ->assertSchemaStateSet([
                'programs_main_heading' => 'CHƯƠNG TRÌNH ĐÀO TẠO',
                'programs_cta_button_url' => '/lien-he',
            ])
            ->fillForm([
                'programs_main_heading' => 'CÁC CHƯƠNG TRÌNH TẠI ĐỨC',
                'programs_cta_title' => 'Cần tư vấn chọn chương trình?',
                'programs_cta_button_url' => '/tu-van',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('CÁC CHƯƠNG TRÌNH TẠI ĐỨC', Setting::get('programs_main_heading'));
        $this->assertSame('Cần tư vấn chọn chương trình?', Setting::get('programs_cta_title'));
        $this->assertSame('/tu-van', Setting::get('programs_cta_button_url'));

        $this->get('/chuong-trinh-hoc')
            ->assertSee('CÁC CHƯƠNG TRÌNH TẠI ĐỨC')
            ->assertSee('Cần tư vấn chọn chương trình?')
            ->assertSee('href="/tu-van"', false);

        // Bản English vẫn dùng chữ tiếng Anh, liên kết dùng chung
        $this->withSession(['locale' => 'en'])->get('/chuong-trinh-hoc')
            ->assertSee('STUDY PROGRAMS')
            ->assertSee('Not sure which program to choose?')
            ->assertSee('href="/tu-van"', false);
    }

    public function test_admin_edits_english_version_without_touching_vietnamese(): void
    {
        $this->actingAsAdmin();

        Livewire::withQueryParams(['lang' => 'en'])
            ->test(ManageProgramsPage::class)
            ->assertSchemaStateSet([
                'programs_main_heading' => 'STUDY PROGRAMS',
                'programs_cta_title' => 'Not sure which program to choose?',
            ])
            ->fillForm([
                'programs_main_heading' => 'PROGRAMS IN GERMANY',
                'programs_cta_title' => 'Need help choosing?',
                'programs_cta_button_url' => '/hacked',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('PROGRAMS IN GERMANY', Setting::get('programs_main_heading_en'));
        $this->assertSame('Need help choosing?', Setting::get('programs_cta_title_en'));
        $this->assertNull(Setting::get('programs_main_heading'));
        $this->assertNull(Setting::get('programs_cta_button_url'));
        $this->assertNull(Setting::get('programs_cta_button_url_en'));

        $this->withSession(['locale' => 'en'])->get('/chuong-trinh-hoc')
            ->assertSee('PROGRAMS IN GERMANY')
            ->assertSee('Need help choosing?');
        $this->withSession(['locale' => 'vi'])->get('/chuong-trinh-hoc')
            ->assertSee('CHƯƠNG TRÌNH ĐÀO TẠO')
            ->assertDontSee('PROGRAMS IN GERMANY');
    }
}
