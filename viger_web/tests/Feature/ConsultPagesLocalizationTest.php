<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageConsultPages;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultPagesLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');
    }

    public function test_consult_page_shows_vietnamese_by_default(): void
    {
        $this->get('/tu-van')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Tư vấn Du học', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('TƯ VẤN DU HỌC')
            ->assertSee('Định hướng &amp; Đánh giá', false)
            ->assertSee('Điều kiện du học hệ Đại học')
            ->assertDontSee('STUDY ABROAD CONSULTING');
    }

    public function test_consult_page_shows_english_in_english_mode(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/tu-van')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Study Abroad Consulting', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('STUDY ABROAD CONSULTING')
            ->assertSee('Your journey with VIGER EDU')
            ->assertSee('Guidance &amp; Assessment', false)
            ->assertSee('Requirements for Bachelor Studies')
            ->assertDontSee('TƯ VẤN DU HỌC')
            ->assertDontSee('Định hướng &amp; Đánh giá', false)
            ->assertDontSee('Điều kiện du học hệ Đại học');
    }

    public function test_bachelor_and_master_heroes_are_translated(): void
    {
        $this->get('/tu-van/du-hoc-dai-hoc')->assertOk()->assertSee('DU HỌC ĐẠI HỌC TẠI ĐỨC');
        $this->get('/tu-van/du-hoc-cao-hoc')->assertOk()->assertSee('DU HỌC CAO HỌC TẠI ĐỨC');

        $this->withSession(['locale' => 'en'])
            ->get('/tu-van/du-hoc-dai-hoc')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Bachelor Study Consulting', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('BACHELOR STUDIES IN GERMANY')
            ->assertDontSee('DU HỌC ĐẠI HỌC TẠI ĐỨC');

        $this->withSession(['locale' => 'en'])
            ->get('/tu-van/du-hoc-cao-hoc')
            ->assertOk()
            ->assertSee('GRADUATE STUDIES IN GERMANY')
            ->assertDontSee('DU HỌC CAO HỌC TẠI ĐỨC');
    }

    public function test_consult_process_page_is_translated(): void
    {
        $this->get('/tu-van/quy-trinh-tu-van')
            ->assertOk()
            ->assertSee('QUY TRÌNH TƯ VẤN DU HỌC')
            ->assertSee('Tư vấn miễn phí');

        $this->withSession(['locale' => 'en'])
            ->get('/tu-van/quy-trinh-tu-van')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Consulting Process', 'VIGER.EDU.VN</title>'], false)
            ->assertSee('STUDY ABROAD CONSULTING PROCESS')
            ->assertSee('Free consultation')
            ->assertSee('Support After Arrival')
            ->assertDontSee('QUY TRÌNH TƯ VẤN DU HỌC')
            ->assertDontSee('Tư vấn miễn phí');
    }

    public function test_admin_page_is_reachable(): void
    {
        $this->actingAsAdmin();

        $this->get(ManageConsultPages::getUrl())->assertOk()->assertSee('Du học Đại học')->assertSee('Du học Cao học');
        $this->get(ManageConsultPages::getUrl(['lang' => 'en']))->assertOk();
    }

    public function test_admin_vietnamese_mode_saves_main_settings(): void
    {
        $this->actingAsAdmin();

        Livewire::test(ManageConsultPages::class)
            ->assertSchemaStateSet(['consult_bachelor_heading' => 'DU HỌC ĐẠI HỌC TẠI ĐỨC'])
            ->fillForm(['consult_bachelor_heading' => 'CỬ NHÂN TẠI ĐỨC'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('CỬ NHÂN TẠI ĐỨC', Setting::get('consult_bachelor_heading'));
        $this->assertNull(Setting::get('consult_bachelor_heading_en'));

        $this->get('/tu-van/du-hoc-dai-hoc')->assertSee('CỬ NHÂN TẠI ĐỨC');
        $this->withSession(['locale' => 'en'])->get('/tu-van/du-hoc-dai-hoc')
            ->assertSee('BACHELOR STUDIES IN GERMANY')
            ->assertDontSee('CỬ NHÂN TẠI ĐỨC');
    }

    public function test_admin_edits_english_version_without_touching_vietnamese(): void
    {
        $this->actingAsAdmin();

        Livewire::withQueryParams(['lang' => 'en'])
            ->test(ManageConsultPages::class)
            ->assertSchemaStateSet(['consult_master_heading' => 'GRADUATE STUDIES IN GERMANY'])
            ->fillForm(['consult_master_heading' => 'MASTER\'S IN GERMANY'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('MASTER\'S IN GERMANY', Setting::get('consult_master_heading_en'));
        $this->assertNull(Setting::get('consult_master_heading'));

        $this->withSession(['locale' => 'en'])->get('/tu-van/du-hoc-cao-hoc')->assertSee('MASTER&#039;S IN GERMANY', false);
        $this->withSession(['locale' => 'vi'])->get('/tu-van/du-hoc-cao-hoc')
            ->assertSee('DU HỌC CAO HỌC TẠI ĐỨC')
            ->assertDontSee('MASTER&#039;S IN GERMANY', false);
    }
}
