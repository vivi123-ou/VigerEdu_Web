<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Kênh Holland → CRM (lead/2 kèm holland-result/3) và trang nguồn Holland.
 */
class CrmHollandSourceTest extends TestCase
{
    use RefreshDatabase;

    private const CRM = 'http://crm.viger.test';

    private const TOKEN = 'token-holland-test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['crm.api_channels.2.token_hash' => hash('sha256', self::TOKEN)]);
    }

    private function hollandResult(array $override = []): array
    {
        return array_replace([
            'schema_version' => 'holland-result/3',
            'engine_version' => 'average_v3',
            'scores' => ['R' => 3.33, 'I' => 4.67, 'A' => 4.17, 'S' => 4.33, 'E' => 2.33, 'C' => 3.17],
            'ranking' => ['I', 'S', 'A', 'R', 'C', 'E'],
            'code_full' => 'ISA',
            'code_public' => 'IS',
            'consistency' => 'Trung bình',
            'reliability' => 'Tin cậy',
            'majors' => [
                ['id' => 'MAJ012', 'name_vi' => 'Tâm lý học', 'name_de' => 'Psychologie', 'shown_to_customer' => true],
                ['id' => 'MAJ030', 'name_vi' => 'Sư phạm', 'shown_to_customer' => false],
            ],
        ], $override);
    }

    private function send(?array $holland = null, array $contact = [])
    {
        return $this->postJson(self::CRM.'/api/v1/leads', array_filter([
            'schema_version' => 'lead/2',
            'event_id' => (string) Str::uuid(),
            'channel' => 'holland',
            'consent' => ['agreed' => true, 'text' => 'Bấm "Xem báo cáo" là bạn đồng ý...'],
            'contact' => $contact + ['full_name' => 'Nguyễn Học Sinh', 'phone' => '+84987654321'],
            'holland_result' => $holland,
        ]), ['X-Api-Token' => self::TOKEN]);
    }

    public function test_holland_channel_creates_lead_with_result(): void
    {
        $this->send($this->hollandResult())->assertCreated();

        $lead = Lead::sole();
        $this->assertSame('holland', $lead->source);
        $this->assertSame('ISA', $lead->hollandCode());
        $this->assertSame(92, $lead->holland_i);   // (4.67 − 1) / 4 × 100 ≈ 92
        $this->assertSame(4.67, $lead->hollandAverage('I'));
        $this->assertSame('Tin cậy', $lead->holland_result['reliability']);
        $this->assertStringContainsString('Nhận kết quả Holland: mã ISA · độ tin cậy: Tin cậy', $lead->activities()->pluck('content')->implode("\n"));
    }

    public function test_profile_shows_raw_scores_reliability_and_majors(): void
    {
        $this->send($this->hollandResult())->assertCreated();
        $lead = Lead::sole();
        $user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);

        $this->actingAs($user)->get(self::CRM."/leads/{$lead->id}")
            ->assertOk()
            ->assertSee('4.67/5')
            ->assertSee('Độ tin cậy: Tin cậy')
            ->assertSee('khách thấy mã IS')
            ->assertSee('Tâm lý học · Psychologie')
            ->assertSee('Sư phạm');
    }

    public function test_newer_result_replaces_older_for_existing_lead(): void
    {
        $this->send($this->hollandResult());
        $this->send($this->hollandResult(['code_full' => 'RIC', 'scores' => ['R' => 5, 'I' => 4, 'A' => 1, 'S' => 1, 'E' => 1, 'C' => 3]]))
            ->assertOk()->assertJson(['is_new' => false]);

        $lead = Lead::sole();
        $this->assertSame('RIC', $lead->hollandCode());
        $this->assertSame(100, $lead->holland_r);
    }

    public function test_invalid_holland_result_rejected_with_field_names(): void
    {
        $this->send($this->hollandResult(['schema_version' => 'holland-result/2', 'scores' => ['R' => 9], 'code_full' => 'XYZ']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['holland_result.schema_version', 'holland_result.scores.R', 'holland_result.code_full']);

        $this->assertSame(0, Lead::count());
    }

    public function test_website_token_cannot_send_as_holland(): void
    {
        config(['crm.api_channels.0.token_hash' => hash('sha256', 'web-token')]);

        $this->postJson(self::CRM.'/api/v1/leads', [
            'schema_version' => 'lead/2', 'event_id' => (string) Str::uuid(), 'channel' => 'holland',
            'consent' => ['agreed' => true, 'text' => 'x'], 'contact' => ['phone' => '+84900000000'],
        ], ['X-Api-Token' => 'web-token'])->assertForbidden();
    }

    public function test_manual_score_edit_drops_holland_code(): void
    {
        $this->send($this->hollandResult());
        $lead = Lead::sole();
        $user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);

        $this->actingAs($user)->put(self::CRM."/leads/{$lead->id}/edit/holland", [
            'holland_r' => 95, 'holland_i' => 10, 'holland_a' => 10, 'holland_s' => 10, 'holland_e' => 90, 'holland_c' => 80,
        ]);

        $lead->refresh();
        $this->assertNull($lead->holland_result);
        $this->assertSame('REC', $lead->hollandCode());
    }

    public function test_holland_source_page_insights(): void
    {
        $this->send($this->hollandResult());
        $this->send($this->hollandResult(['code_full' => 'IAS', 'reliability' => 'Cần xem lại']), ['phone' => '+84911111111']);
        Lead::factory()->create(['source' => 'website', 'holland_r' => 99]);   // khác nguồn => không tính

        $user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);
        $res = $this->actingAs($user)->get(self::CRM.'/sources/holland')->assertOk()
            ->assertSee('Phân bố mã Holland')
            ->assertSee('Điểm trung bình theo nhóm')
            ->assertSee('Cần xem lại: 1');

        $holland = $res->viewData('holland');
        $this->assertSame(2, $holland['with_result']);
        $this->assertSame(['IS' => 1, 'IA' => 1], $holland['pairs']->all());
        $this->assertEqualsWithDelta(4.67, $holland['averages']['I'], 0.02);

        // Kênh holland đã cấu hình token => có ô tình trạng kết nối
        $this->assertSame(2, $res->viewData('channel')['count_all']);
    }

    public function test_menu_shows_holland_link(): void
    {
        $user = User::factory()->create(['crm_role' => 'admin', 'active' => true]);

        $this->actingAs($user)->get(self::CRM.'/sources/holland')
            ->assertSee('href="'.self::CRM.'/sources/holland"', false);
    }
}
