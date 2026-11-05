<?php

namespace App\Contracts;

use App\Data\Major;
use Illuminate\Support\Collection;

/**
 * Danh mục ngành / nghề kèm mã Holland. Nguồn chính là CRM (giai đoạn 4),
 * bản dự phòng là majors.json (JsonMajorCatalog).
 */
interface MajorCatalog
{
    /**
     * Toàn bộ ngành (cả đang bật và đang tắt; engine tự bỏ ngành đang tắt).
     *
     * @return Collection<int, Major>
     */
    public function all(): Collection;
}
