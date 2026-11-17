<?php

namespace Tests\Feature\Assessment;

use App\Models\User;
use App\Modules\Assessment\Models\TestConfigVersion;
use App\Modules\Assessment\Support\TestConfig;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * H1: trang "Cài đặt bài test" lưu theo phiên bản, kiểm tra dữ liệu, quay lại bản cũ; có 6 câu mỗi trang và 5 nhãn sao.
 */
class TestSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class, RoleSeeder::class, TestConfigSeeder::class]);
        $this->actingAs(User::factory()->create());
    }

    public function test_page_shows_items_per_page_and_star_labels_with_help(): void
    {
        $this->get(route('admin.test-settings.edit'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Trang này làm gì?')
            ->assertSee('Số câu mỗi trang')
            ->assertSee('Chữ của 5 mức sao')
            ->assertSeeInOrder(['1 sao', 'Hoàn toàn không giống tôi', '5 sao', 'Hoàn toàn giống tôi'])
            ->assertSee('Phiên bản 1');
    }

    public function test_saving_creates_a_new_version_and_restoring_brings_back_the_old_values(): void
    {
        $original = TestConfig::currentVersion();

        $this->put(route('admin.test-settings.update'), ['items_per_page' => 4] + TestConfig::current())
            ->assertRedirect(route('admin.test-settings.edit'))
            ->assertSessionHas('status');

        $this->assertSame(4, TestConfig::get('items_per_page'));
        $this->assertSame(2, TestConfigVersion::count());
        $this->assertStringContainsString('Số câu mỗi trang: 6 → 4', TestConfig::currentVersion()->note);

        $this->post(route('admin.test-settings.restore', $original))->assertRedirect();

        $this->assertSame(6, TestConfig::get('items_per_page'));
        $this->assertSame(3, TestConfigVersion::count(), 'Quay lại cũng lưu thành phiên bản mới, không xóa lịch sử');
    }

    public function test_saving_without_changes_does_not_add_a_version(): void
    {
        $this->put(route('admin.test-settings.update'), TestConfig::current())
            ->assertSessionHas('status', 'Không có thay đổi nào.');

        $this->assertSame(1, TestConfigVersion::count());
    }

    public function test_invalid_values_are_rejected_and_nothing_is_saved(): void
    {
        $input = ['items_per_page' => 0, 'min_distinct_facets' => 9, 'likert_labels' => ['Một', '', 'Ba', 'Bốn', 'Năm']] + TestConfig::current();

        $this->from(route('admin.test-settings.edit'))
            ->put(route('admin.test-settings.update'), $input)
            ->assertRedirect(route('admin.test-settings.edit'))
            ->assertSessionHasErrors(['items_per_page', 'min_distinct_facets', 'likert_labels.1']);

        $this->assertSame(1, TestConfigVersion::count());
        $this->assertSame(6, TestConfig::get('items_per_page'));
    }
}
