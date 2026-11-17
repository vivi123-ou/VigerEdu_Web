<?php

namespace App\Http\Requests\Admin\Assessment;

use App\Modules\Assessment\Support\TestConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Lưu trang "Cài đặt bài test". Chỉ quản trị viên (route có middleware menu:test-settings).
 */
class TestSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canAccessMenu('test-settings');
    }

    protected function prepareForValidation(): void
    {
        // Ô công tắc không gửi khi tắt; số thập phân gõ dấu phẩy (4,2) đổi thành dấu chấm.
        $data = ['avoid_repeat' => $this->boolean('avoid_repeat'), 'use_anchors' => $this->boolean('use_anchors')];

        foreach ($this->all() as $key => $value) {
            if (is_string($value) && preg_match('/^\s*-?\d+,\d+\s*$/', $value)) {
                $data[$key] = str_replace(',', '.', trim($value));
            }
        }

        foreach (['bands' => 'max', 'c_labels' => 'min', 'academic_labels' => 'min'] as $table => $column) {
            $rows = collect((array) $this->input($table))
                ->filter(fn ($row) => is_array($row) && (filled($row[$column] ?? null) || filled($row['label'] ?? null)))
                ->map(fn ($row) => [...$row, $column => is_string($row[$column] ?? null) ? str_replace(',', '.', trim($row[$column])) : ($row[$column] ?? null)])
                ->values()->all();
            $data[$table] = $rows;
        }

        $this->merge($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return TestConfig::rules();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $bands = collect((array) $this->input('bands'))->pluck('max')->map(fn ($value) => (float) $value);
            if ($bands->isNotEmpty() && abs($bands->max() - 5.0) > 0.0001) {
                $validator->errors()->add('bands', 'Dòng cuối của bảng nhãn mức độ quan tâm phải có mốc 5 (điểm cao nhất).');
            }
            if ($bands->count() !== $bands->unique()->count()) {
                $validator->errors()->add('bands', 'Các mốc trong bảng nhãn mức độ quan tâm không được trùng nhau.');
            }

            foreach (['c_labels' => 'chỉ số C', 'academic_labels' => 'nền tảng học tập'] as $table => $name) {
                $mins = collect((array) $this->input($table))->pluck('min')->map(fn ($value) => (float) $value);
                if ($mins->count() !== $mins->unique()->count()) {
                    $validator->errors()->add($table, "Các mốc trong bảng nhãn {$name} không được trùng nhau.");
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (TestConfig::FIELDS as $fields) {
            foreach ($fields as $key => [$label]) {
                $attributes[$key] = '"'.$label.'"';
            }
        }

        return $attributes + [
            'likert_labels.*' => 'chữ của mức trả lời',
            'bands.*.max' => 'mốc điểm',
            'bands.*.label' => 'nhãn',
            'c_labels.*.min' => 'mốc chỉ số C',
            'c_labels.*.label' => 'nhãn',
            'academic_labels.*.min' => 'mốc điểm',
            'academic_labels.*.label' => 'nhãn',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Vui lòng điền ô :attribute.',
            'integer' => 'Ô :attribute phải là số nguyên.',
            'numeric' => 'Ô :attribute phải là số (ví dụ 4,2).',
            'min' => 'Ô :attribute nhỏ nhất là :min.',
            'max' => 'Ô :attribute lớn nhất là :max.',
            'size' => 'Cần đủ :size ô :attribute.',
            'lte' => 'Số chủ đề tối thiểu không được lớn hơn số câu mỗi nhóm.',
            'boolean' => 'Ô :attribute không hợp lệ.',
            'array' => 'Ô :attribute không hợp lệ.',
            'string' => 'Ô :attribute không hợp lệ.',
        ];
    }
}
