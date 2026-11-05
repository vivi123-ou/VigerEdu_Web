<?php

namespace App\Http\Requests\Admin;

use App\Data\ScoringConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Cấu hình chấm điểm. Trên màn hình nhập theo % (60, 40, 80…) cho dễ đọc; lưu dạng 0–1.
 */
class ScoringConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $number = fn ($value) => is_string($value) ? str_replace(',', '.', trim($value)) : $value;

        $labels = collect((array) $this->input('labels', []))
            ->map(fn ($row) => ['min' => $number($row['min'] ?? null), 'label' => trim((string) ($row['label'] ?? ''))])
            ->reject(fn (array $row) => $row['label'] === '' && ($row['min'] === null || $row['min'] === ''))
            ->values()
            ->all();

        $this->merge([
            'weight_code_match' => $number($this->input('weight_code_match')),
            'weight_score_match' => $number($this->input('weight_score_match')),
            'di3_threshold' => $number($this->input('di3_threshold')),
            'labels' => $labels,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'weight_code_match' => ['required', 'numeric', 'between:0,100'],
            'weight_score_match' => ['required', 'numeric', 'between:0,100'],
            'labels' => ['required', 'array', 'min:1', 'max:8'],
            'labels.*.min' => ['required', 'numeric', 'between:0,100'],
            'labels.*.label' => ['required', 'string', 'max:50'],
            'top_university' => ['required', 'integer', 'between:0,20'],
            'top_vocational' => ['required', 'integer', 'between:0,20'],
            'di3_threshold' => ['required', 'numeric', 'between:0,100'],
            'reason_subject' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (abs((float) $this->input('weight_code_match') + (float) $this->input('weight_score_match') - 100) > 0.01) {
                $validator->errors()->add('weight_score_match', 'Hai trọng số cộng lại phải bằng 100%.');
            }

            $mins = array_map(fn (array $row) => (float) $row['min'], $this->input('labels'));

            if (! in_array(0.0, $mins, true)) {
                $validator->errors()->add('labels', 'Cần một nhãn có ngưỡng 0% (nhãn thấp nhất, dành cho mọi ngành còn lại).');
            }

            if (count($mins) !== count(array_unique($mins))) {
                $validator->errors()->add('labels', 'Hai nhãn không được có cùng ngưỡng.');
            }
        }];
    }

    /**
     * Cấu hình đã đổi về thang 0–1.
     */
    public function config(): ScoringConfig
    {
        return ScoringConfig::fromArray([
            'weight_code_match' => round((float) $this->input('weight_code_match') / 100, 4),
            'weight_score_match' => round((float) $this->input('weight_score_match') / 100, 4),
            'labels' => array_map(fn (array $row) => ['min' => round((float) $row['min'] / 100, 4), 'label' => $row['label']], $this->input('labels')),
            'top_university' => (int) $this->input('top_university'),
            'top_vocational' => (int) $this->input('top_vocational'),
            'di3_threshold' => round((float) $this->input('di3_threshold') / 100, 4),
            'reason_subject' => (string) $this->input('reason_subject'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            '*.required' => 'Vui lòng điền ô này.',
            '*.numeric' => 'Vui lòng nhập số.',
            '*.between' => 'Giá trị nằm ngoài khoảng cho phép.',
            'labels.*.min.required' => 'Vui lòng nhập ngưỡng cho nhãn.',
            'labels.*.min.between' => 'Ngưỡng từ 0 đến 100.',
            'labels.*.label.required' => 'Vui lòng nhập tên nhãn.',
            'top_university.between' => 'Từ 0 đến 20 ngành.',
            'top_vocational.between' => 'Từ 0 đến 20 nghề.',
        ];
    }
}
