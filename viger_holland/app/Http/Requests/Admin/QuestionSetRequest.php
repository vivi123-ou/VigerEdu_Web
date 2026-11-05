<?php

namespace App\Http\Requests\Admin;

use App\Models\QuestionSet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Thêm / sửa bộ câu hỏi Phần B.
 */
class QuestionSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->filled('code') ? (string) $this->input('code') : (string) $this->input('name');

        $this->merge([
            'code' => Str::slug(Str::ascii($code)),
            'is_active' => $this->boolean('is_active'),
            'is_default' => $this->boolean('is_default'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var QuestionSet|null $set */
        $set = $this->route('questionSet');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-z0-9-]+$/', Rule::unique('question_sets', 'code')->ignore($set?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'answer_type' => ['required', Rule::in(['yes_no', 'likert3', 'likert5'])],
            'scale_labels' => ['nullable', 'array'],
            'scale_labels.*' => ['required', 'string', 'max:60'],
            'per_page' => ['required', 'integer', 'between:1,30'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng đặt tên cho bộ câu.',
            'code.required' => 'Vui lòng nhập mã bộ câu.',
            'code.min' => 'Mã bộ câu cần ít nhất 2 ký tự.',
            'code.regex' => 'Mã bộ câu chỉ gồm chữ thường không dấu, số và dấu gạch ngang (ví dụ: sinh-vien).',
            'code.unique' => 'Mã này đã có bộ câu khác dùng (kể cả bộ trong thùng rác). Vui lòng chọn mã khác.',
            'answer_type.in' => 'Kiểu trả lời không hợp lệ.',
            'scale_labels.*.required' => 'Vui lòng điền nhãn cho mọi mức.',
            'per_page.between' => 'Số câu mỗi trang từ 1 đến 30.',
            'per_page.required' => 'Vui lòng nhập số câu mỗi trang.',
        ];
    }
}
