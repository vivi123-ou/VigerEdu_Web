<?php

namespace App\Catalogs;

use App\Contracts\MajorCatalog;
use App\Data\Major;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Danh mục ngành đọc từ file JSON (mặc định database/seeders/data/holland/majors.json).
 * Là bản dự phòng khi chưa có / không gọi được CRM.
 */
final class JsonMajorCatalog implements MajorCatalog
{
    /** @var Collection<int, Major>|null */
    private ?Collection $majors = null;

    public function __construct(private readonly string $path) {}

    public function all(): Collection
    {
        return $this->majors ??= $this->load();
    }

    /**
     * @return Collection<int, Major>
     */
    private function load(): Collection
    {
        if (! is_file($this->path)) {
            throw new RuntimeException("Không tìm thấy file danh mục ngành: {$this->path}");
        }

        $rows = json_decode((string) file_get_contents($this->path), true, flags: JSON_THROW_ON_ERROR);

        return collect($rows)->map(fn (array $row) => Major::fromArray($row))->values();
    }
}
