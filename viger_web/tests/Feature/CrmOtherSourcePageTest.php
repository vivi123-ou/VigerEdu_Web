<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmOtherSourcePageTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    public function test_other_page_shows_who_entered_leads(): void
    {
        $an = User::factory()->create(['crm_role' => 'admin', 'active' => true, 'full_name' => 'Nhân Viên An']);
        $binh = User::factory()->create(['crm_role' => 'admin', 'active' => true, 'full_name' => 'Nhân Viên Bình']);

        // Lead::created ghi "CRM tiếp nhận…" kèm user đang đăng nhập
        $this->actingAs($an);
        Lead::factory()->count(2)->create(['source' => 'other']);
        $this->actingAs($binh);
        Lead::factory()->create(['source' => 'other']);
        Lead::factory()->create(['source' => 'website']);   // khác nguồn => không tính

        $res = $this->get(self::CRM.'/sources/other')->assertOk()
            ->assertSee('Nguồn khác')
            ->assertSee('Người nhập khách vào CRM')
            ->assertSee('Nhân Viên An')
            ->assertDontSee('channel-card', false);

        $this->assertSame(['Nhân Viên An' => 2, 'Nhân Viên Bình' => 1], $res->viewData('creators')->all());
    }

    public function test_add_button_preselects_other_source(): void
    {
        $user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);

        $this->actingAs($user)->get(self::CRM.'/sources/other')
            ->assertSee(route('crm.leads.create', ['source' => 'other']), false);

        $this->get(self::CRM.'/leads/create?source=other')
            ->assertOk()
            ->assertSee('<option value="other" selected', false);

        // Giá trị lạ => về mặc định website
        $this->get(self::CRM.'/leads/create?source=hack')
            ->assertSee('<option value="website" selected', false);
    }

    public function test_all_four_source_menu_items_enabled(): void
    {
        $html = $this->actingAs(User::factory()->create(['crm_role' => 'admin', 'active' => true]))->get(self::CRM.'/sources/other')->getContent();

        foreach (['website', 'holland', 'chatbot', 'other'] as $s) {
            $this->assertStringContainsString('href="'.self::CRM.'/sources/'.$s.'"', $html);
        }
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }
}
