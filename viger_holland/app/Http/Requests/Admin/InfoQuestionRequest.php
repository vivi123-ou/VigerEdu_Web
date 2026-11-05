<?php

namespace App\Http\Requests\Admin;

use App\Enums\AnswerType;
use App\Models\Question;
use App\Support\ChoiceList;
use App\Support\LeadFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Thêm / sửa một câu Phần A (form "Nhận kết quả").
 * crm_target: '' (không gửi CRM), một trường chuẩn của lead/1, hoặc '_custom' kèm extra_field_key.
 */
class InfoQuestionRequest extends FormRequest
{
    public const CUSTOM = '_custom';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_required' => $this->boolean('is_required'),
            'is_active' => $this->boolean('is_active'),
            'extra_field_key' => $this->filled('extra_field_key')
                ? Str::of((string) $this->input('extra_field_key'))->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value()
                : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:500'],
            'answer_type' => ['required', Rule::in(array_keys(AnswerType::partAOptions()))],
            'options_text' => ['nullable', 'string', 'max:5000'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'crm_target' => ['nullable', 'string', Rule::in([...LeadFields::selectable(), self::CUSTOM])],
            'extra_field_key' => ['nullable', 'required_if:crm_target,'.self::CUSTOM, 'string', 'max:50'],
            'show_if_question' => ['nullable', 'string', Rule::exists('questions', 'code')->where('part', Question::PART_INFO)->whereNull('deleted_at')],
            'show_if_values' => ['nullable', 'array'],
            'show_if_values.*' => ['string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
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

            /** @var Question|null $current */
            $current = $this->route('question');
            $type = AnswerType::from($this->input('answer_type'));

            if (in_array($type, [AnswerType::Single, AnswerType::Multi], true) && $this->options() === []) {
                $validator->errors()->add('options_text', 'Câu chọn cần ít nhất một lựa chọn (mỗi dòng một lựa chọn).');
            }

            if ($this->input('crm_target') === self::CUSTOM && ! LeadFields::isValidExtraKey((string) $this->input('extra_field_key'))) {
                $validator->errors()->add('extra_field_key', 'Mã trường cần bắt đầu bằng chữ, chỉ gồm chữ thường không dấu, số và dấu gạch dưới (ví dụ: truong_chuyen).');
            }

            $leadField = $this->leadField();
            $extraKey = $this->extraFieldKey();
            $others = Question::part(Question::PART_INFO)->when($current, fn ($query) => $query->whereKeyNot($current->id));

            if ($leadField !== null && ($used = (clone $others)->where('lead_field', $leadField)->value('code'))) {
                $validator->errors()->add('crm_target', "Trường này đang được câu {$used} dùng. Mỗi trường CRM chỉ nhận từ một câu.");
            }

            if ($extraKey !== null && ($used = (clone $others)->where('extra_field_key', $extraKey)->value('code'))) {
                $validator->errors()->add('extra_field_key', "Mã trường này đang được câu {$used} dùng. Vui lòng đặt mã khác.");
            }

            if ($this->filled('show_if_question') && $current && $this->input('show_if_question') === $current->code) {
                $validator->errors()->add('show_if_question', 'Câu không thể phụ thuộc vào chính nó.');
            }

            if ($this->filled('show_if_question') && empty($this->input('show_if_values'))) {
                $validator->errors()->add('show_if_values', 'Chọn ít nhất một câu trả lời để câu này hiện ra.');
            }
        }];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function options(): array
    {
        $type = AnswerType::tryFrom((string) $this->input('answer_type'));

        return in_array($type, [AnswerType::Single, AnswerType::Multi], true) ? ChoiceList::parse($this->input('options_text')) : [];
    }

    public function leadField(): ?string
    {
        if ($this->input('answer_type') === AnswerType::Consent->value) {
            return 'consent';
        }

        $target = $this->input('crm_target');

        return filled($target) && $target !== self::CUSTOM ? $target : null;
    }

    public function extraFieldKey(): ?string
    {
        return $this->input('crm_target') === self::CUSTOM && $this->input('answer_type') !== AnswerType::Consent->value
            ? $this->input('extra_field_key')
            : null;
    }

    /**
     * @return array{question: string, in: list<string>}|null
     */
    public function showIf(): ?array
    {
        if (! $this->filled('show_if_question')) {
            return null;
        }

        return ['question' => (string) $this->input('show_if_question'), 'in' => array_values(array_map('strval', (array) $this->input('show_if_values')))];
    }

    /**
     * Cột lưu vào bảng questions.
     *
     * @return array<string, mixed>
     */
    public function questionAttributes(): array
    {
        $type = AnswerType::from($this->input('answer_type'));

        return [
            'text' => $this->input('text'),
            'answer_type' => $type,
            'options' => $this->options() ?: null,
            'help_text' => $this->input('help_text'),
            'placeholder' => $this->input('placeholder'),
            'is_required' => $type === AnswerType::Consent ? true : $this->boolean('is_required'),
            'is_active' => $this->boolean('is_active'),
            'lead_field' => $this->leadField(),
            'extra_field_key' => $this->extraFieldKey(),
            'show_if' => $this->showIf(),
            'note' => $this->input('note'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.required' => 'Vui lòng nhập câu hỏi.',
            'answer_type.required' => 'Vui lòng chọn kiểu trả lời.',
            'answer_type.in' => 'Kiểu trả lời không hợp lệ.',
            'crm_target.in' => 'Trường CRM không hợp lệ.',
            'extra_field_key.required_if' => 'Vui lòng nhập mã trường (ví dụ: truong_chuyen).',
            'show_if_question.exists' => 'Câu điều kiện không tồn tại.',
        ];
    }
}
