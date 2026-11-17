<?php

namespace Tests\Feature\Assessment;

use App\Models\User;
use App\Modules\Assessment\Filament\Pages\TestSettings;
use App\Modules\Assessment\Models\TestConfigVersion;
use App\Modules\Assessment\Support\TestConfig;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Trang "Cài đặt bài test" (Filament): lưu theo phiên bản, ghi rõ ô nào đổi, kiểm tra dữ liệu, quay lại bản cũ;
 * có 6 câu mỗi trang và chữ của 5 mức sao.
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

    public function test_page_shows_items_per_page_star_labels_help_and_history(): void
    {
        $this->get(route('filament.admin.pages.test-settings'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Các con số quyết định cách rút đề')
            ->assertSee('Số câu mỗi trang')
            ->assertSee('Chữ của 5 mức sao')
            ->assertSeeInOrder(['1 sao', '5 sao'])
            ->assertSee('Lịch sử các phiên bản cài đặt');

        Livewire::test(TestSettings::class)
            ->assertSchemaStateSet(['items_per_page' => 6, 'likert_labels' => TestConfig::DEFAULTS['likert_labels']])
            ->assertCanSeeTableRecords(TestConfigVersion::all());
    }

    public function test_saving_creates_a_new_version_and_restoring_brings_back_the_old_values(): void
    {
        $original = TestConfig::currentVersion();

        Livewire::test(TestSettings::class)
            ->fillForm(['items_per_page' => 4])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(4, TestConfig::get('items_per_page'));
        $this->assertSame(2, TestConfigVersion::count());
        $this->assertStringContainsString('Số câu mỗi trang: 6 → 4', TestConfig::currentVersion()->note);
        $this->assertSame(auth()->id(), TestConfig::currentVersion()->user_id);

        Livewire::test(TestSettings::class)
            ->callTableAction('restore', $original)
            ->assertSchemaStateSet(['items_per_page' => 6]);

        $this->assertSame(6, TestConfig::get('items_per_page'));
        $this->assertSame(3, TestConfigVersion::count(), 'Quay lại cũng lưu thành phiên bản mới, không xóa lịch sử');
    }

    public function test_saving_without_changes_does_not_add_a_version(): void
    {
        Livewire::test(TestSettings::class)->call('save')->assertHasNoFormErrors();

        $this->assertSame(1, TestConfigVersion::count());
    }

    public function test_invalid_values_are_rejected_and_nothing_is_saved(): void
    {
        Livewire::test(TestSettings::class)
            ->fillForm(['items_per_page' => 0, 'min_distinct_facets' => 9])
            ->call('save')
            ->assertHasFormErrors(['items_per_page', 'min_distinct_facets']);

        Livewire::test(TestSettings::class)
            ->fillForm(['likert_labels' => ['Một', '', 'Ba', 'Bốn', 'Năm']])
            ->call('save')
            ->assertHasFormErrors(['likert_labels.1']);

        $this->assertSame(1, TestConfigVersion::count());
        $this->assertSame(6, TestConfig::get('items_per_page'));
    }

    public function test_band_table_must_end_at_five(): void
    {
        Livewire::test(TestSettings::class)
            ->fillForm(['bands' => [['max' => 2.5, 'label' => 'Thấp'], ['max' => 4, 'label' => 'Cao']]])
            ->call('save')
            ->assertHasFormErrors(['bands']);

        $this->assertSame(1, TestConfigVersion::count());
    }

    public function test_only_people_with_permission_can_open_or_save(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_COUNSELOR]));

        $this->get(route('filament.admin.pages.test-settings'))->assertForbidden();
        $this->assertFalse(TestSettings::canAccess());
    }
}
