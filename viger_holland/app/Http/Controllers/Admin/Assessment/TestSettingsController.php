<?php

namespace App\Http\Controllers\Admin\Assessment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assessment\TestSettingsRequest;
use App\Modules\Assessment\Models\TestConfigVersion;
use App\Modules\Assessment\Support\TestConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Trang "Cài đặt bài test": mọi con số của rút đề, chấm điểm, gợi ý ngành. Mỗi lần lưu là một phiên bản mới;
 * lượt làm bài ghi lại phiên bản đã dùng nên kết quả cũ không đổi. "Quay lại bản này" tạo phiên bản mới từ bản cũ.
 */
class TestSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.test-settings.edit', [
            'settings' => TestConfig::current(),
            'versions' => TestConfigVersion::query()->with('user')->latest('id')->limit(15)->get(),
            'currentId' => TestConfig::currentVersion()?->id,
        ]);
    }

    public function update(TestSettingsRequest $request): RedirectResponse
    {
        $before = TestConfig::current();
        $after = TestConfig::normalize($request->validated());

        $version = TestConfig::save($after, $request->user(), $this->describe($before, $after));

        return redirect()->route('admin.test-settings.edit')->with('status', $version
            ? 'Đã lưu cài đặt (phiên bản '.$version->id.'). Lượt làm bài mới dùng ngay cài đặt này; kết quả cũ giữ nguyên.'
            : 'Không có thay đổi nào.');
    }

    public function restore(TestConfigVersion $version): RedirectResponse
    {
        $saved = TestConfig::save($version->settings, auth()->user(), 'Quay lại phiên bản '.$version->id.' ('.$version->created_at->format('H:i d/m/Y').')');

        return redirect()->route('admin.test-settings.edit')->with('status', $saved
            ? 'Đã quay lại phiên bản '.$version->id.' (lưu thành phiên bản '.$saved->id.').'
            : 'Cài đặt hiện tại đã giống phiên bản này.');
    }

    /**
     * Mô tả ngắn các ô đã đổi, hiện trong lịch sử phiên bản.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    private function describe(array $before, array $after): string
    {
        $changed = collect($after)
            ->filter(fn ($value, $key) => ($before[$key] ?? null) != $value)
            ->keys()
            ->map(function (string $key) use ($before, $after): string {
                $label = TestConfig::label($key);

                return is_scalar($after[$key]) && is_scalar($before[$key] ?? null)
                    ? $label.': '.$this->format($before[$key]).' → '.$this->format($after[$key])
                    : $label;
            });

        return $changed->isEmpty() ? 'Lưu cài đặt' : 'Đổi '.$changed->implode('; ');
    }

    private function format(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'bật' : 'tắt',
            is_float($value) => rtrim(rtrim(number_format($value, 4, ',', ''), '0'), ','),
            default => (string) $value,
        };
    }
}
