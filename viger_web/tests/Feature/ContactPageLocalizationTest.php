<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageContactPage;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactPageLocalizationTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'ADMIN', 'active' => true]));
        Filament::setCurrentPanel('admin');
    }

    public function test_contact_page_shows_vietnamese_by_default(): void
    {
        $this->get('/lien-he')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Liên Hệ', '</title>'], false)
            ->assertSee('KẾT NỐI VỚI VIGER EDU')
            ->assertSee('Mạng xã hội')
            ->assertSee('placeholder="Tên của bạn"', false)
            ->assertSee('Đăng ký ngay')
            ->assertDontSee('CONNECT WITH VIGER EDU');
    }

    public function test_contact_page_shows_english_in_english_mode(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/en/contact')
            ->assertOk()
            ->assertSeeInOrder(['<title>', 'Contact', '</title>'], false)
            ->assertSee('CONNECT WITH VIGER EDU')
            ->assertSee('Get in Touch with')
            ->assertSee('Social media')
            ->assertSee('placeholder="Your name"', false)
            ->assertSee('Register now')
            ->assertSee('(084) 819949968')
            ->assertDontSee('KẾT NỐI VỚI VIGER EDU')
            ->assertDontSee('Mạng xã hội')
            ->assertDontSee('Tên của bạn')
            ->assertDontSee('Đăng ký ngay');
    }

    public function test_validation_and_success_messages_follow_locale(): void
    {
        $this->withSession(['locale' => 'en'])
            ->post('/en/contact', [])
            ->assertSessionHasErrors(['name' => 'Please enter your name.']);

        $this->withSession(['locale' => 'en'])
            ->post('/en/contact', ['name' => 'Anna', 'email' => 'anna@example.com', 'phone' => '0123'])
            ->assertSessionHas('status', 'Thank you! VIGER EDU will contact you within 24 hours.');
    }

    public function test_admin_edits_english_version_without_touching_vietnamese(): void
    {
        $this->actingAsAdmin();

        $this->get(ManageContactPage::getUrl(['lang' => 'en']))->assertOk();

        Livewire::withQueryParams(['lang' => 'en'])
            ->test(ManageContactPage::class)
            ->assertSchemaStateSet(['contact_hero_title' => 'CONNECT WITH VIGER EDU'])
            ->fillForm([
                'contact_hero_title' => 'TALK TO VIGER EDU',
                'contact_phone' => '000',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('TALK TO VIGER EDU', Setting::get('contact_hero_title_en'));
        $this->assertNull(Setting::get('contact_hero_title'));
        $this->assertNull(Setting::get('contact_phone'));
        $this->assertNull(Setting::get('contact_phone_en'));

        $this->withSession(['locale' => 'en'])->get('/en/contact')->assertSee('TALK TO VIGER EDU');
        $this->withSession(['locale' => 'vi'])->get('/lien-he')
            ->assertSee('KẾT NỐI VỚI VIGER EDU')
            ->assertDontSee('TALK TO VIGER EDU');
    }

    public function test_admin_vietnamese_mode_still_saves(): void
    {
        $this->actingAsAdmin();

        Livewire::test(ManageContactPage::class)
            ->fillForm(['contact_hero_title' => 'LIÊN HỆ VIGER', 'contact_phone' => '0909'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('LIÊN HỆ VIGER', Setting::get('contact_hero_title'));
        $this->assertSame('0909', Setting::get('contact_phone'));
    }
}
