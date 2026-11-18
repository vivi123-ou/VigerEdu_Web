<?php

namespace App\Modules\Api\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assessment\Models\Major;
use App\Modules\Assessment\Models\RiasecPair;
use App\Modules\Assessment\Models\RiasecType;
use App\Modules\Assessment\Models\ThirdLetter;
use Illuminate\Http\JsonResponse;

/**
 * Làm gì:   GET /api/v1/catalog/{types|pairs|third|majors} (hợp đồng mục 3.3): danh mục để chatbot giải thích mã, ngành
 *           đúng như báo cáo. Ngành chỉ gồm ngành đang dùng, kèm dấu đã kiểm tra nguồn chính thức.
 * Ai gọi:   chatbot, CRM; middleware api.token:results:read.
 * Dữ liệu:  riasec_types, riasec_pairs + pair_major, third_letters, majors.
 */
class CatalogController extends Controller
{
    public function show(string $type): JsonResponse
    {
        $items = match ($type) {
            'types' => RiasecType::query()->orderBy('hex_index')->get(['code', 'name', 'label', 'description']),
            'pairs' => RiasecPair::query()->with('majors:id,code')->orderBy('pair')->get()->map(fn (RiasecPair $p) => [
                'pair' => $p->pair, 'name' => $p->name, 'relation' => $p->relation, 'description' => $p->description,
                'base_majors' => $p->majors->pluck('code')->all(),
            ]),
            'third' => ThirdLetter::query()->get(['code', 'specialty_vi', 'specialty_de', 'skills', 'description']),
            'majors' => Major::query()->where('is_active', true)->orderBy('sort_order')
                ->get(['code', 'name_vi', 'name_de', 'name_en', 'holland_codes', 'routes', 'is_verified', 'source_url'])
                // Hệ đào tạo chỉ trả khi ngành đã kiểm tra (dữ liệu chưa kiểm tra có ghi chú nội bộ như "Cần tra").
                ->map(fn (Major $m) => ['routes' => $m->is_verified ? $m->routes : null] + $m->toArray()),
            default => abort(404),
        };

        return response()->json(['schema_version' => 'holland-catalog/1', 'type' => $type, 'items' => $items]);
    }
}
