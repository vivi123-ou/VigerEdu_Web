<?php

namespace Tests\Feature\Assessment;

use App\Models\AdminActivity;
use App\Models\User;
use App\Modules\Assessment\Filament\Pages\HollandCodes;
use App\Modules\Assessment\Filament\Pages\MajorsCatalog;
use App\Modules\Assessment\Models\CodeOverride;
use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\Occupation;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\RiasecType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * H6 – danh mục: "Giải thích các mã Holland" (6 nhóm, 15 cặp, chữ thứ ba, mã riêng) và "Ngành học & nghề gợi ý".
 */
class CatalogPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
    }

    public function test_codes_page_lists_every_tab_and_edits_group_and_pair_texts_with_history(): void
    {
        $this->get(HollandCodes::getUrl())->assertOk()
            ->assertSee('6 nhóm')->assertSee('15 cặp')->assertSee('Chữ thứ ba')->assertSee('Mã 3 chữ riêng')
            ->assertSee('Bạn thích quan sát, đặt câu hỏi');

        $type = RiasecType::query()->where('code', 'I')->firstOrFail();
        Livewire::test(HollandCodes::class)
            ->callTableAction('edit', $type, ['name' => 'Nghiên cứu', 'label' => 'Nghiên cứu, phân tích', 'description' => 'Đoạn mới cho nhóm I.'])
            ->assertHasNoTableActionErrors();
        $this->assertSame('Đoạn mới cho nhóm I.', $type->fresh()->description);
        $this->assertTrue(AdminActivity::query()->where('subject_type', 'catalog.nhom')->where('description', 'like', 'Sửa nhóm I%')->exists());

        $pair = RiasecPair::query()->where('pair', 'IS')->firstOrFail();
        $major = Major::query()->where('is_active', true)->whereNotIn('id', $pair->majors->pluck('id'))->firstOrFail();
        Livewire::test(HollandCodes::class, ['tab' => 'cap'])
            ->callTableAction('edit', $pair, ['name' => $pair->name, 'description' => 'Đoạn cặp mới.', 'majors' => [...$pair->majors->pluck('id')->all(), $major->id]])
            ->assertHasNoTableActionErrors();
        $this->assertSame('Đoạn cặp mới.', $pair->fresh()->description);
        $this->assertContains($major->id, $pair->fresh()->majors->pluck('id')->all());
    }

    public function test_code_overrides_accept_only_valid_unique_three_letter_codes(): void
    {
        $page = Livewire::test(HollandCodes::class, ['tab' => 'ma']);
        $page->callTableAction('create', data: ['code' => 'isa', 'interpretation' => 'Đoạn riêng cho ISA.'])->assertHasNoTableActionErrors();
        $this->assertSame('Đoạn riêng cho ISA.', CodeOverride::query()->where('code', 'ISA')->value('interpretation'));

        foreach (['IIS', 'ABC', 'IS', 'ISA'] as $bad) {
            Livewire::test(HollandCodes::class, ['tab' => 'ma'])
                ->callTableAction('create', data: ['code' => $bad, 'interpretation' => 'x'])
                ->assertHasTableActionErrors(['code']);
        }
    }

    public function test_majors_need_a_source_link_before_being_marked_verified_and_new_items_get_codes(): void
    {
        $major = Major::query()->where('code', 'MAJ001')->firstOrFail();

        Livewire::test(MajorsCatalog::class)
            ->callTableAction('edit', $major, ['name_vi' => $major->name_vi, 'source_url' => '', 'is_verified' => true, 'holland_codes' => ['RIE'], 'is_active' => true])
            ->assertHasTableActionErrors(['is_verified']);

        Livewire::test(MajorsCatalog::class)
            ->callTableAction('edit', $major, ['name_vi' => $major->name_vi, 'source_url' => 'https://www.hochschulkompass.de/studium', 'is_verified' => true, 'holland_codes' => ['RIE'], 'is_active' => true])
            ->assertHasNoTableActionErrors();
        $this->assertTrue($major->fresh()->is_verified);

        Livewire::test(MajorsCatalog::class)
            ->callTableAction('edit', $major, ['name_vi' => $major->name_vi, 'holland_codes' => ['RRE'], 'is_active' => true])
            ->assertHasTableActionErrors();

        Livewire::test(MajorsCatalog::class)
            ->callTableAction('create', data: ['name_vi' => 'Ngành thử', 'holland_codes' => ['SAE'], 'is_active' => true])
            ->assertHasNoTableActionErrors();
        $this->assertSame('MAJ047', Major::query()->where('name_vi', 'Ngành thử')->value('code'));

        Livewire::test(MajorsCatalog::class, ['tab' => 'nghe'])
            ->callTableAction('create', data: ['name_vi' => 'Nghề thử', 'name_de' => 'Testberuf', 'is_active' => true])
            ->assertHasNoTableActionErrors();
        $this->assertSame('OCC052', Occupation::query()->where('name_vi', 'Nghề thử')->value('code'));
    }

    public function test_only_people_with_catalog_permission_open_these_pages(): void
    {
        foreach ([User::ROLE_STAFF, User::ROLE_COUNSELOR] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(HollandCodes::getUrl())->assertNotFound();
            $this->get(MajorsCatalog::getUrl())->assertNotFound();
        }
    }
}
