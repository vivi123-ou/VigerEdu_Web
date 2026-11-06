<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Support\ContentPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Khối "Trắc nghiệm Holland" cho web chính (docs/HOP_DONG_KET_NOI.md mục 6c):
 * - GET /api/v1/public/intro: nội dung khối (JSON, có cache, CORS cho tên miền được phép);
 * - GET /embed: bản rút gọn đặt trong iframe (chỉ tên miền được phép nhúng);
 * - GET /embed.js: script tự chèn iframe và tự co giãn chiều cao.
 */
class EmbedController extends Controller
{
    public const CACHE_KEY = 'holland.public_intro';

    public function intro(Request $request): JsonResponse
    {
        $data = Cache::remember(self::CACHE_KEY, now()->addMinutes(5), fn (): array => [
            'title' => (string) setting('embed_title', setting('intro_title')),
            'subtitle' => (string) setting('embed_subtitle', setting('intro_subtitle')),
            'image_url' => media_url(setting('embed_image', setting('banner_image'))),
            'button_text' => (string) setting('embed_button_text', setting('intro_button_text')),
            'start_url' => route('home', ['utm_source' => 'web-chinh']),
            'stats' => ['total_attempts' => Attempt::count()],
        ]);

        $response = response()->json($data)->header('Cache-Control', 'public, max-age=300');

        return $this->withCors($request, $response);
    }

    public function show(): Response
    {
        $ancestors = implode(' ', ["'self'", ...ContentPages::domains(setting('embed_allowed_domains'))]);

        return response(view('public.embed')->render())
            ->header('Content-Security-Policy', "frame-ancestors {$ancestors}");
    }

    public function script(): Response
    {
        return response(view('public.embed-script', ['embedUrl' => route('embed')])->render())
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function withCors(Request $request, JsonResponse $response): JsonResponse
    {
        $origin = (string) $request->headers->get('Origin');

        if ($origin !== '' && in_array(strtolower($origin), ContentPages::domains(setting('embed_allowed_domains')), true)) {
            $response->header('Access-Control-Allow-Origin', $origin)->header('Vary', 'Origin');
        }

        return $response;
    }
}
