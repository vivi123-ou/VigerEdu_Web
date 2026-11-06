<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\MajorCatalog;
use App\Contracts\ScoringEngine;
use App\Data\HollandResult;
use App\Data\ScoringConfig;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScoringConfigRequest;
use App\Models\AdminActivity;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cấu hình chấm điểm: trọng số, ngưỡng và tên nhãn, số ngành gợi ý, ngưỡng hồ sơ phẳng.
 * Nút "Chạy thử" chấm một hồ sơ mẫu bằng giá trị đang gõ (chưa lưu) để so với cấu hình đang dùng.
 */
class ScoringController extends Controller
{
    private const RIASEC = ['R', 'I', 'A', 'S', 'E', 'C'];

    public function __construct(private readonly MajorCatalog $catalog) {}

    public function edit(Request $request): View
    {
        return view('admin.scoring.edit', [
            'config' => ScoringConfig::current(),
            'defaults' => ScoringConfig::defaults(),
            'trialPercent' => $this->trialPercent($request),
            'activities' => AdminActivity::forSubject(AdminActivity::SUBJECT_SCORING, null),
        ]);
    }

    public function update(ScoringConfigRequest $request): RedirectResponse
    {
        if ($request->input('action') === 'trial') {
            return $this->trial($request);
        }

        $before = Setting::query()->where('key', ScoringConfig::SETTING_KEY)->value('value');
        $after = json_encode($request->config()->toArray(), JSON_UNESCAPED_UNICODE);

        if ($before === $after) {
            return redirect()->route('admin.scoring.edit')->with('status', 'Không có thay đổi nào.');
        }

        Setting::put(ScoringConfig::SETTING_KEY, $after, 'scoring');
        AdminActivity::record(AdminActivity::ACTION_UPDATE, AdminActivity::SUBJECT_SCORING, null, 'Sửa cấu hình chấm điểm',
            [ScoringConfig::SETTING_KEY => $before], [ScoringConfig::SETTING_KEY => $after]);

        return redirect()->route('admin.scoring.edit')->with('status', 'Đã lưu. Từ giờ các lượt làm bài mới được chấm theo cấu hình này (lượt cũ giữ nguyên kết quả).');
    }

    /**
     * Đặt lại như bản chốt ban đầu.
     */
    public function reset(): RedirectResponse
    {
        $before = Setting::query()->where('key', ScoringConfig::SETTING_KEY)->value('value');
        $after = json_encode(ScoringConfig::defaults()->toArray(), JSON_UNESCAPED_UNICODE);

        Setting::put(ScoringConfig::SETTING_KEY, $after, 'scoring');
        AdminActivity::record(AdminActivity::ACTION_UPDATE, AdminActivity::SUBJECT_SCORING, null, 'Đặt lại cấu hình chấm điểm như ban đầu',
            [ScoringConfig::SETTING_KEY => $before], [ScoringConfig::SETTING_KEY => $after]);

        return redirect()->route('admin.scoring.edit')->with('status', 'Đã đặt lại cấu hình chấm điểm như ban đầu.');
    }

    /**
     * Chạy thử: chấm hồ sơ mẫu bằng cấu hình đang dùng và bằng cấu hình đang gõ, hiện cạnh nhau. Không lưu gì.
     */
    private function trial(ScoringConfigRequest $request): RedirectResponse
    {
        $percent = $this->trialPercent($request);

        return redirect()->route('admin.scoring.edit', ['p' => $percent])
            ->withInput()
            ->with('trial', [
                'percent' => $percent,
                'current' => $this->summary($this->engine(ScoringConfig::current())->scorePercent($this->ratio($percent), $this->catalog->all())),
                'trial' => $this->summary($this->engine($request->config())->scorePercent($this->ratio($percent), $this->catalog->all())),
            ]);
    }

    private function engine(ScoringConfig $config): ScoringEngine
    {
        $class = config('holland.engines.'.config('holland.engine'));

        return app()->make($class, ['config' => $config]);
    }

    /** Hồ sơ gợi ý sẵn cho ô "Chạy thử" (admin sửa tự do). */
    private const TRIAL_DEFAULT = ['R' => 30, 'I' => 70, 'A' => 85, 'S' => 55, 'E' => 15, 'C' => 20];

    /**
     * Hồ sơ dùng để chạy thử: % 6 nhóm (0–100).
     *
     * @return array<string, int>
     */
    private function trialPercent(Request $request): array
    {
        $input = (array) ($request->input('p') ?? $request->query('p') ?? []);
        $default = self::TRIAL_DEFAULT;

        return collect(self::RIASEC)->mapWithKeys(fn (string $group) => [
            $group => max(0, min(100, (int) ($input[$group] ?? $default[$group]))),
        ])->all();
    }

    /**
     * @param  array<string, int>  $percent
     * @return array<string, float>
     */
    private function ratio(array $percent): array
    {
        return array_map(fn (int $value) => $value / 100, $percent);
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(HollandResult $result): array
    {
        $data = $result->toArray();
        $row = fn (array $major) => ['name' => $major['name'], 'label' => $major['label'], 'fit' => $major['fit'] ?? null];

        return [
            'code' => $data['code'],
            'university' => array_map($row, $data['top_university']),
            'vocational' => array_map($row, $data['top_vocational']),
        ];
    }
}
