<?php

namespace Tests\Feature\Admin;

use App\Data\ScoringConfig;
use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return array_replace([
            'weight_code_match' => '70', 'weight_score_match' => '30',
            'labels' => [
                ['min' => '85', 'label' => 'Rất hợp'], ['min' => '60', 'label' => 'Hợp'],
                ['min' => '0', 'label' => 'Chưa hợp'], ['min' => '', 'label' => ''],
            ],
            'top_university' => 4, 'top_vocational' => 2, 'di3_threshold' => '10', 'reason_subject' => 'bạn',
        ], $overrides);
    }

    public function test_defaults_shown_and_engine_uses_config_defaults(): void
    {
        $this->get(route('admin.scoring.edit'))->assertOk()->assertSee('Rất phù hợp')->assertSee('Chạy thử')->assertSee('HS01 Lan');

        $this->assertSame(ScoringConfig::defaults()->toArray(), app(ScoringConfig::class)->toArray());
    }

    public function test_save_converts_percent_and_engine_uses_saved_values(): void
    {
        $this->put(route('admin.scoring.update'), [...$this->form(), 'action' => 'save'])->assertSessionHasNoErrors();

        $config = app(ScoringConfig::class);
        $this->assertSame(0.7, $config->weightCodeMatch);
        $this->assertSame(0.3, $config->weightScoreMatch);
        $this->assertSame(4, $config->topUniversity);
        $this->assertSame(0.1, $config->di3Threshold);
        $this->assertSame([['min' => 0.85, 'label' => 'Rất hợp'], ['min' => 0.6, 'label' => 'Hợp'], ['min' => 0.0, 'label' => 'Chưa hợp']], $config->labels);
        $this->assertTrue(AdminActivity::where('subject_type', AdminActivity::SUBJECT_SCORING)->exists());
    }

    public function test_weights_must_sum_to_100_and_need_zero_label(): void
    {
        $this->put(route('admin.scoring.update'), $this->form(['weight_score_match' => '40']))
            ->assertSessionHasErrors(['weight_score_match' => 'Hai trọng số cộng lại phải bằng 100%.']);

        $this->put(route('admin.scoring.update'), $this->form(['labels' => [['min' => '50', 'label' => 'Hợp']]]))
            ->assertSessionHasErrors('labels');

        $this->assertNull(Setting::where('key', ScoringConfig::SETTING_KEY)->value('value'));
    }

    public function test_trial_compares_without_saving(): void
    {
        $response = $this->put(route('admin.scoring.update'), [...$this->form(['top_university' => 2]), 'action' => 'trial',
            'p' => ['R' => 33, 'I' => 67, 'A' => 89, 'S' => 56, 'E' => 11, 'C' => 22]]);

        $response->assertSessionHas('trial', fn (array $trial) => $trial['current']['code'] === 'AIS'
            && count($trial['current']['university']) === 5
            && count($trial['trial']['university']) === 2
            && $trial['current']['university'][0]['name'] === 'Ngôn ngữ và Văn học Đức');

        $this->assertNull(Setting::where('key', ScoringConfig::SETTING_KEY)->value('value'), 'Chạy thử không lưu');
        $this->followRedirects($response)->assertSee('Với các con số vừa gõ');
    }

    public function test_reset_and_revert(): void
    {
        $this->put(route('admin.scoring.update'), [...$this->form(), 'action' => 'save']);
        $this->post(route('admin.scoring.reset'));
        $this->assertSame(0.6, app(ScoringConfig::class)->weightCodeMatch);

        $activity = AdminActivity::where('subject_type', AdminActivity::SUBJECT_SCORING)->latest('id')->firstOrFail();
        $this->post(route('admin.history.revert', $activity));
        $this->assertSame(0.7, app(ScoringConfig::class)->weightCodeMatch, 'Quay lại bản trước lần đặt lại');
    }

    public function test_groups_update_shows_on_settings_and_can_revert(): void
    {
        $names = ['R' => 'Thực tế', 'I' => 'Nghiên cứu', 'A' => 'Nghệ thuật', 'S' => 'Xã hội', 'E' => 'Quản lý', 'C' => 'Nghiệp vụ'];

        $this->get(route('admin.groups.edit'))->assertOk()->assertSee('Kỹ thuật');
        $this->put(route('admin.groups.update'), ['name' => $names, 'description' => ['R' => 'Mô tả mới']])->assertSessionHasNoErrors();

        $this->assertSame('Thực tế', setting('group_name_R'));
        $this->assertSame('Mô tả mới', setting('group_desc_R'));

        $this->post(route('admin.history.revert', AdminActivity::where('subject_type', AdminActivity::SUBJECT_GROUPS)->sole()));
        $this->assertSame('Kỹ thuật', setting('group_name_R'));
    }
}
