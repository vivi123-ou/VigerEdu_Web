<?php

namespace App\Http\Requests\Admin;

use App\Support\ChoiceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Thêm / sửa một câu Holland (Phần B).
 */
class HollandQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'max_score' => $this->filled('max_score') ? str_replace(',', '.', (string) $this->input('max_score')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:500'],
            'riasec' => ['required', Rule::in(['R', 'I', 'A', 'S', 'E', 'C'])],
            'form' => ['nullable', 'string', 'max:50'],
            'max_score' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'options_text' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('options_text') && empty(ChoiceList::parseScale($this->input('options_text')))) {
                $validator->errors()->add('options_text', 'Mỗi dòng cần có dạng "Nhãn = điểm", điểm là số (ví dụ: Đúng = 1).');
            }
        }];
    }

    /**
     * Thang riêng của câu (để trống = theo thang của bộ).
     *
     * @return list<array{value: int|float, label: string}>|null
     */
    public function scale(): ?array
    {
        return $this->filled('options_text') ? ChoiceList::parseScale($this->input('options_text')) : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.required' => 'Vui lòng nhập nội dung câu hỏi.',
            'text.max' => 'Câu hỏi quá dài (tối đa 500 ký tự).',
            'riasec.required' => 'Vui lòng chọn nhóm Holland của câu này.',
            'riasec.in' => 'Nhóm Holland không hợp lệ.',
            'max_score.numeric' => 'Điểm tối đa phải là số.',
            'max_score.gt' => 'Điểm tối đa phải lớn hơn 0.',
        ];
    }
}
