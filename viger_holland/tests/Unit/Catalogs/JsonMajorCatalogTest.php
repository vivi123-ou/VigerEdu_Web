<?php

namespace Tests\Unit\Catalogs;

use App\Catalogs\JsonMajorCatalog;
use App\Data\Major;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class JsonMajorCatalogTest extends TestCase
{
    private const MAJORS_JSON = __DIR__.'/../../../database/seeders/data/holland/majors.json';

    public function test_loads_75_majors_split_by_type(): void
    {
        $majors = (new JsonMajorCatalog(self::MAJORS_JSON))->all();

        $this->assertCount(75, $majors);
        $this->assertCount(60, $majors->where('type', Major::TYPE_UNIVERSITY));
        $this->assertCount(15, $majors->where('type', Major::TYPE_VOCATIONAL));
        $this->assertTrue($majors->every(fn (Major $major) => $major->hollandCodes !== []), 'Ngành nào cũng có ít nhất 1 mã Holland');
    }

    public function test_maps_vietnamese_keys_to_contract_fields(): void
    {
        $major = (new JsonMajorCatalog(self::MAJORS_JSON))->all()->firstWhere('code', 'N043');

        $this->assertSame(Major::TYPE_UNIVERSITY, $major->type);
        $this->assertSame('Ngôn ngữ và Văn học Đức', $major->nameVi);
        $this->assertSame('Germanistik', $major->nameDe);
        $this->assertSame('Ngôn ngữ – Văn hóa – Truyền thông', $major->field);
        $this->assertSame(['IAS'], $major->hollandCodes);
        $this->assertSame('G4', $major->testasPrimary);
        $this->assertNull($major->testasSecondary, 'Ô trống thành null');
        $this->assertTrue($major->isActive);
    }

    public function test_accepts_crm_contract_keys_too(): void
    {
        $major = Major::fromArray([
            'code' => 'N999', 'type' => 'vocational', 'name_vi' => 'Nghề thử', 'field' => 'Dịch vụ',
            'holland_codes' => ['sec', 'XYZ', 'ES'], 'testas_primary' => 'G3', 'is_active' => 0,
        ]);

        $this->assertSame(Major::TYPE_VOCATIONAL, $major->type);
        $this->assertSame(['SEC'], $major->hollandCodes, 'Viết hoa, bỏ mã không hợp lệ');
        $this->assertFalse($major->isActive);
    }

    public function test_missing_file_gives_clear_error(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Không tìm thấy file danh mục ngành');

        (new JsonMajorCatalog('/khong/co/file.json'))->all();
    }
}
