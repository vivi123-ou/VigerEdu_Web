<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmLeadTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['active' => true, 'full_name' => 'Tư Vấn Một']);
    }

    public function test_guest_cannot_see_leads(): void
    {
        $this->get(self::CRM.'/leads')->assertRedirect(self::CRM.'/login');
    }

    public function test_index_lists_leads_with_pagination(): void
    {
        Lead::factory()->create(['full_name' => 'Nguyễn Văn Test', 'assigned_to' => $this->user->id]);
        Lead::factory()->count(20)->create();

        $this->actingAs($this->user)->get(self::CRM.'/leads')
            ->assertOk()
            ->assertSee('Khách hàng tiềm năng')
            ->assertSee('21 khách hàng')
            ->assertSee('aria-label="Trang sau"', false);
    }

    public function test_search_and_filters(): void
    {
        Lead::factory()->create(['full_name' => 'Nguyễn An', 'phone' => '0900000001', 'status' => 'new', 'source' => 'website']);
        Lead::factory()->create(['full_name' => 'Trần Bình', 'phone' => '0900000002', 'status' => 'consulting', 'source' => 'holland', 'assigned_to' => $this->user->id]);

        $this->actingAs($this->user);

        $this->get(self::CRM.'/leads?q=0900000002')->assertSee('Trần Bình')->assertDontSee('class="link-strong">Nguyễn An</a>', false);
        $this->get(self::CRM.'/leads?status=new')->assertSee('Nguyễn An')->assertDontSee('class="link-strong">Trần Bình</a>', false);
        $this->get(self::CRM.'/leads?source=holland')->assertSee('Trần Bình')->assertDontSee('class="link-strong">Nguyễn An</a>', false);
        $this->get(self::CRM.'/leads?assigned_to=none')->assertSee('Nguyễn An')->assertDontSee('class="link-strong">Trần Bình</a>', false);
        $this->get(self::CRM.'/leads?assigned_to='.$this->user->id)->assertSee('Trần Bình')->assertDontSee('class="link-strong">Nguyễn An</a>', false);
    }

    public function test_date_filter(): void
    {
        Lead::factory()->create(['full_name' => 'Lead Hôm Nay', 'created_at' => now()]);
        Lead::factory()->create(['full_name' => 'Lead Cũ', 'created_at' => now()->subDays(20)]);

        $this->actingAs($this->user)->get(self::CRM.'/leads?date=7d')
            ->assertSee('Lead Hôm Nay')->assertDontSee('Lead Cũ');
    }

    public function test_store_creates_lead(): void
    {
        $this->actingAs($this->user)->get(self::CRM.'/leads/create')->assertOk()->assertSee('Thêm khách hàng');

        $this->post(self::CRM.'/leads', [
            'full_name' => 'Lê Văn Mới',
            'phone' => '0912345678',
            'email' => 'moi@example.com',
            'source' => 'chatbot',
            'status' => 'new',
            'assigned_to' => $this->user->id,
        ])->assertSessionHas('success');

        $lead = Lead::firstWhere('phone', '0912345678');
        $this->assertSame($this->user->id, $lead->assigned_to);

        // Tạo xong chuyển sang trang chi tiết, lịch sử có dòng "tiếp nhận" + "phân công"
        $this->get(self::CRM.'/leads/'.$lead->id)
            ->assertOk()
            ->assertSee($lead->code())
            ->assertSee('CRM tiếp nhận khách hàng từ nguồn AI Chatbot')
            ->assertSee('Phân công tư vấn viên: Tư Vấn Một');
    }

    public function test_show_page_renders_all_sections(): void
    {
        $lead = Lead::factory()->create(['full_name' => 'Nguyễn Văn A', 'student_name' => 'Nguyễn Văn C', 'holland_i' => 90, 'holland_s' => 80, 'holland_r' => 70, 'holland_c' => 10]);

        $this->actingAs($this->user)->get(self::CRM.'/leads/'.$lead->id)
            ->assertOk()
            ->assertSee('VIGER-LEAD-'.str_pad($lead->id, 5, '0', STR_PAD_LEFT))
            ->assertSeeInOrder(['Thông tin phụ huynh', 'Thông tin học sinh', 'Nhu cầu tư vấn du học', 'Thông tin Holland', 'Lịch sử tư vấn', 'Ghi chú và hoạt động'])
            ->assertSee('Nguyễn Văn C')
            ->assertSee('Xem kết quả Holland');

        $this->assertSame('ISR', $lead->hollandCode());
    }

    public function test_update_section_and_log_history(): void
    {
        $lead = Lead::factory()->create(['status' => 'new', 'phone' => '0900000009']);
        $this->actingAs($this->user);

        $this->get(self::CRM."/leads/{$lead->id}/edit/student")->assertOk()->assertSee('Thông tin học sinh');
        $this->get(self::CRM."/leads/{$lead->id}/edit/unknown")->assertNotFound();

        $this->put(self::CRM."/leads/{$lead->id}/edit/student", [
            'student_name' => 'Nguyễn Văn C',
            'gpa' => '8.5',
            'german_level' => 'B1',
            'desired_state' => 'Bayern',
        ])->assertRedirect(self::CRM."/leads/{$lead->id}#student");

        $this->put(self::CRM."/leads/{$lead->id}/edit/general", [
            'source' => $lead->source, 'status' => 'consulting', 'assigned_to' => $this->user->id,
        ]);

        // Sửa phụ huynh giữ nguyên SĐT của chính mình => không bị báo trùng
        $this->put(self::CRM."/leads/{$lead->id}/edit/parent", ['full_name' => 'Tên Mới', 'phone' => '0900000009'])
            ->assertSessionHasNoErrors();

        $lead->refresh();
        $this->assertSame('Nguyễn Văn C', $lead->student_name);
        $this->assertSame('consulting', $lead->status);

        $log = $lead->activities()->pluck('content')->implode("\n");
        $this->assertStringContainsString('Cập nhật Thông tin học sinh: Họ và tên, GPA, Tiếng Đức, Bang mong muốn.', $log);
        $this->assertStringContainsString('Đổi trạng thái: Mới → Đang tư vấn.', $log);
        $this->assertStringContainsString('Phân công tư vấn viên: Tư Vấn Một.', $log);
        $this->assertStringContainsString('Cập nhật Thông tin phụ huynh (liên hệ chính): Họ và tên.', $log);
    }

    public function test_update_validates_fields(): void
    {
        $lead = Lead::factory()->create();
        $this->actingAs($this->user);

        $this->put(self::CRM."/leads/{$lead->id}/edit/holland", ['holland_r' => 150])->assertSessionHasErrors('holland_r');
        $this->put(self::CRM."/leads/{$lead->id}/edit/student", ['gpa' => 11, 'student_gender' => 'X'])->assertSessionHasErrors(['gpa', 'student_gender']);
        $this->put(self::CRM."/leads/{$lead->id}/edit/parent", ['full_name' => ''])->assertSessionHasErrors(['full_name', 'phone']);
    }

    public function test_add_note(): void
    {
        $lead = Lead::factory()->create();
        $this->actingAs($this->user);

        $this->post(self::CRM."/leads/{$lead->id}/notes", ['type' => 'call', 'content' => 'Gọi phụ huynh, hẹn gặp thứ 7.'])
            ->assertRedirect(self::CRM."/leads/{$lead->id}#history");

        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'call', 'user_id' => $this->user->id]);
        $this->post(self::CRM."/leads/{$lead->id}/notes", ['type' => 'system', 'content' => 'x'])->assertSessionHasErrors('type');
        $this->post(self::CRM."/leads/{$lead->id}/notes", ['type' => 'note', 'content' => ''])->assertSessionHasErrors('content');
    }

    public function test_only_admin_can_delete(): void
    {
        $lead = Lead::factory()->create();
        $lead->log('Ghi chú', 'note');

        $staff = User::factory()->create(['active' => true, 'role' => 'USER']);
        $this->actingAs($staff)->delete(self::CRM."/leads/{$lead->id}")->assertForbidden();
        $this->get(self::CRM."/leads/{$lead->id}")->assertDontSee('Xoá khách hàng');

        $admin = User::factory()->create(['active' => true, 'role' => 'ADMIN']);
        $this->actingAs($admin)->delete(self::CRM."/leads/{$lead->id}")->assertRedirect(self::CRM.'/leads');

        $this->assertModelMissing($lead);
        $this->assertDatabaseMissing('lead_activities', ['lead_id' => $lead->id]);
    }

    public function test_store_validates_phone_and_duplicates(): void
    {
        Lead::factory()->create(['phone' => '0912345678']);
        $this->actingAs($this->user);

        $base = ['full_name' => 'X', 'source' => 'website', 'status' => 'new'];

        $this->post(self::CRM.'/leads', [...$base, 'phone' => 'abc'])->assertSessionHasErrors('phone');
        $this->post(self::CRM.'/leads', [...$base, 'phone' => '0912345678'])->assertSessionHasErrors('phone');
        $this->post(self::CRM.'/leads', [...$base, 'phone' => '0911111111', 'source' => 'hack'])->assertSessionHasErrors('source');
    }

    public function test_export_csv_respects_filters(): void
    {
        Lead::factory()->create(['full_name' => 'Xuất Một', 'status' => 'new']);
        Lead::factory()->create(['full_name' => 'Không Xuất', 'status' => 'lost']);

        $response = $this->actingAs($this->user)->get(self::CRM.'/leads/export?status=new');
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Xuất Một', $csv);
        $this->assertStringNotContainsString('Không Xuất', $csv);
    }
}
