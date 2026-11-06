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

    /** Tự tạo mục mới trong hồ sơ khách theo câu hỏi (mặc định cho câu mới). */
    public const AUTO = '_auto';

    /**
     * Mã trường tùy biến tạo từ câu hỏi, không trùng câu khác: "Trường chuyên?" → truong_chuyen.
     */
    private function autoKey(): string
    {
        /** @var Question|null $current */
        $current = $this->route('question');

        if ($current !== null && filled($current->extra_field_key)) {
            return $current->extra_field_key;
        }

        $base = Str::of((string) $this->input('text'))->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->value();
        // Tối đa 40 ký tự, cắt ở ranh giới từ ("truong_thpt_dang_hoc_co_phai_truong").
        if (strlen($base) > 40) {
            $cut = substr($base, 0, 41);
            $base = rtrim(str_contains($cut, '_') ? substr($cut, 0, (int) strrpos($cut, '_')) : substr($base, 0, 40), '_');
        }
        $base = preg_match('/^[a-z]/', $base) ? $base : 'cau_'.$base;
        $base = strlen($base) >= 2 ? $base : 'cau_hoi';

        $used = Question::withTrashed()->part(Question::PART_INFO)->whereNotNull('extra_field_key')
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->pluck('extra_field_key')->all();

        $key = $base;
        for ($suffix = 2; in_array($key, $used, true); $suffix++) {
            $key = $base.'_'.$suffix;
        }

        return $key;
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $number = fn (string $key) => $this->filled($key) ? str_replace(',', '.', (string) $this->input($key)) : null;

        // Thêm nhanh câu chọn: tạo sẵn 2 lựa chọn mẫu, người dùng mở thẻ để sửa.
        if ($this->boolean('quick_add') && in_array($this->input('answer_type'), ['single', 'multi'], true) && blank($this->input('options_text'))) {
            $this->merge(['options_text' => "Lựa chọn 1\nLựa chọn 2"]);
        }

        // "Tự động": tạo mục mới trong hồ sơ khách, mã lấy từ câu hỏi (người dùng không cần biết mã).
        if ($this->input('crm_target') === self::AUTO) {
            $this->merge(['crm_target' => self::CUSTOM, 'extra_field_key' => $this->autoKey()]);
        }

        $this->merge([
            'is_required' => $this->boolean('is_required'),
            'is_active' => $this->boolean('is_active'),
            'allow_other' => $this->boolean('allow_other'),
            'is_private' => $this->boolean('is_private'),
            'min' => $number('min'),
            'max' => $number('max'),
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
            'display' => ['nullable', Rule::in([Question::DISPLAY_BUTTONS, Question::DISPLAY_STARS, Question::DISPLAY_DROPDOWN])],
            'allow_other' => ['boolean'],
            'is_private' => ['boolean'],
            'min' => ['nullable', 'numeric'],
            'max' => ['nullable', 'numeric'],
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

            if ($this->filled('min') && $this->filled('max') && (float) $this->input('max') < (float) $this->input('min')) {
                $validator->errors()->add('max', 'Số lớn nhất phải lớn hơn hoặc bằng số nhỏ nhất.');
            }

            if ($type === AnswerType::Rating && $this->filled('options_text') && count(ChoiceList::parseScale($this->input('options_text')) ?? []) < 2) {
                $validator->errors()->add('options_text', 'Cần ít nhất 2 mức, mỗi dòng "Nhãn = điểm" (ví dụ: Rất hài lòng = 5).');
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

        return match (true) {
            $type?->isChoice() === true => ChoiceList::parse($this->input('options_text')),
            $type === AnswerType::Rating => (array) ChoiceList::parseScale($this->input('options_text')),
            default => [],
        };
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
            'display' => in_array($type, [AnswerType::Single, AnswerType::Rating], true) ? ($this->input('display') ?: null) : null,
            'config' => $this->config($type) ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function config(AnswerType $type): array
    {
        return array_filter([
            'allow_other' => $type->isChoice() && $this->boolean('allow_other') ? true : null,
            'min' => $type === AnswerType::Number && $this->filled('min') ? (float) $this->input('min') : null,
            'max' => $type === AnswerType::Number && $this->filled('max') ? (float) $this->input('max') : null,
            'private' => $this->boolean('is_private') ? true : null,
        ], fn ($value) => $value !== null);
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
