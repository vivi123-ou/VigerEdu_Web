<?php

namespace Tests\Feature\Schools;

use App\Models\User;
use App\Modules\Schools\Filament\Pages\SchoolsCatalog;
use App\Modules\Schools\Models\Country;
use App\Modules\Schools\Models\Region;
use App\Modules\Schools\Models\School;
use App\Modules\Schools\Services\SchoolImport;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * H6 – danh mục Trường & vùng: 16 bang, 390 trường (20 trường Hành chính / Đặc thù ẩn), tư vấn viên xem, quản trị viên sửa,
 * nhập Excel của nước khác có bước ghép cột (tự đoán), nhập lại không tạo trùng.
 */
class SchoolsCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_seed_has_germany_16_states_and_390_schools_with_special_ones_hidden(): void
    {
        $this->assertSame(16, Region::query()->count());
        $this->assertSame(390, School::query()->count());
        $this->assertSame(20, School::query()->where('is_hidden', true)->count());
        $this->assertSame(0, School::query()->whereNull('region_id')->count());
        $this->assertNull(Region::query()->where('code', 'BW')->value('extra')['thong_doc'] ?? null, 'Bỏ cột thống đốc');
        $this->assertSame(1952, Region::query()->where('code', 'BW')->value('founded_year'), 'Bỏ dấu chú thích [8]');

        $this->seed(DatabaseSeeder::class);
        $this->assertSame(390, School::query()->count(), 'Chạy lại không trùng');
    }

    public function test_counselor_reads_visible_schools_but_cannot_edit_or_import(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_COUNSELOR]));

        $hidden = School::query()->where('is_hidden', true)->firstOrFail();
        $rwth = School::query()->where('short_name', 'Aachen TH')->firstOrFail();

        $this->get(SchoolsCatalog::getUrl())->assertOk()->assertSee('Trường & vùng');
        Livewire::test(SchoolsCatalog::class)->searchTable('Aachen')
            ->assertCanSeeTableRecords([$rwth])->assertCanNotSeeTableRecords([$hidden])
            ->assertTableActionHidden('edit', $rwth)->assertTableActionHidden('import')->assertTableActionHidden('create');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_STAFF]));
        $this->get(SchoolsCatalog::getUrl())->assertNotFound();
    }

    public function test_import_guesses_columns_adds_new_country_schools_and_updates_without_duplicates(): void
    {
        $austria = Country::query()->create(['code' => 'AT', 'name' => 'Áo', 'sort_order' => 2]);
        $path = $this->xlsx([
            ['Code', 'Name', 'Bundesland', 'Type', 'City', 'Website', 'Ghi chú riêng'],
            ['W1', 'Universität Wien', 'Wien', 'ĐH Nghiên cứu', 'Wien', 'https://univie.ac.at', 'lớn nhất Áo'],
            ['G1', 'TU Graz', 'Steiermark', 'ĐH Hành chính', 'Graz', 'https://tugraz.at', null],
            ['X9', '', 'Wien', '', '', '', ''],
        ]);

        $import = app(SchoolImport::class);
        $mapping = $import->guess($import->headers($path));
        $this->assertSame('Name', $mapping['name']);
        $this->assertSame('Code', $mapping['external_code']);
        $this->assertSame('Bundesland', $mapping['region']);

        $stats = $import->import($path, $austria, $mapping, ['ĐH Hành chính']);
        $this->assertSame(['created' => 2, 'updated' => 0, 'skipped' => 1], $stats);
        $vienna = School::query()->where('external_code', 'W1')->firstOrFail();
        $this->assertSame('Wien', $vienna->region->name);
        $this->assertSame($austria->id, $vienna->region->country_id);
        $this->assertSame(['Ghi chú riêng' => 'lớn nhất Áo'], $vienna->extra);
        $this->assertTrue(School::query()->where('external_code', 'G1')->value('is_hidden'));

        $stats = $import->import($path, $austria, $mapping);
        $this->assertSame(['created' => 0, 'updated' => 2, 'skipped' => 1], $stats);
        $this->assertSame(392, School::query()->count());
    }

    public function test_admin_can_edit_a_school_and_hide_it(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $rwth = School::query()->where('short_name', 'Aachen TH')->firstOrFail();

        Livewire::test(SchoolsCatalog::class)
            ->callTableAction('edit', $rwth, ['country_id' => $rwth->country_id, 'region_id' => $rwth->region_id, 'name' => 'RWTH Aachen University', 'is_hidden' => true])
            ->assertHasNoTableActionErrors();
        $this->assertSame('RWTH Aachen University', $rwth->fresh()->name);
        $this->assertTrue($rwth->fresh()->is_hidden);
    }

    /**
     * @param  list<list<string|null>>  $rows
     */
    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'truong').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }
}
