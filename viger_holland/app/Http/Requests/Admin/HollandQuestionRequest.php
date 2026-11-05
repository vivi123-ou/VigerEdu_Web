<?php

namespace App\Http\Requests\Admin;

use App\Enums\AnswerType;
use App\Models\Question;
use App\Models\QuestionSet;
use App\Support\ChoiceList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Thêm / sửa một câu Holland kiểu Google Form:
 * - Đúng/Không, thang mức: một nhóm (riasec), thang riêng (options_text) hoặc theo bộ, hiện dạng ô số hoặc ngôi sao.
 * - Chọn 1 / chọn nhiều: danh sách lựa chọn choices[i][label|group|points], mỗi lựa chọn cộng điểm cho một nhóm.
 */
class HollandQuestionRequest extends FormRequest
{
    private const GROUPS = ['R', 'I', 'A', 'S', 'E', 'C'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $choices = collect((array) $this->input('choices', []))
            ->map(fn ($row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'group' => filled($row['group'] ?? null) ? (string) $row['group'] : null,
                'points' => filled($row['points'] ?? null) ? str_replace(',', '.', (string) $row['points']) : null,
            ])
            ->reject(fn (array $row) => $row['label'] === '')
            ->values()
            ->all();

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_required' => $this->boolean('is_required'),
            'max_score' => $this->filled('max_score') ? str_replace(',', '.', (string) $this->input('max_score')) : null,
            'choices' => $choices,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = AnswerType::tryFrom((string) $this->input('answer_type'));
        $isChoice = $type?->isChoice() ?? false;

        return [
            'text' => ['required', 'string', 'max:500'],
            'answer_type' => ['required', Rule::in(array_keys(AnswerType::hollandOptions()))],
            'riasec' => [$isChoice ? 'nullable' : 'required', Rule::in(self::GROUPS)],
            'display' => ['nullable', Rule::in([Question::DISPLAY_BUTTONS, Question::DISPLAY_STARS, Question::DISPLAY_DROPDOWN])],
            'form' => ['nullable', 'string', 'max:50'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'max_score' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'options_text' => ['nullable', 'string', 'max:1000'],
            'choices' => [$isChoice ? 'required' : 'nullable', 'array', 'max:30'],
            'choices.*.label' => ['required', 'string', 'max:200'],
            'choices.*.group' => ['nullable', Rule::in(self::GROUPS)],
            'choices.*.points' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'min_select' => ['nullable', 'integer', 'min:0', 'max:30'],
            'max_select' => ['nullable', 'integer', 'min:0', 'max:30'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_required' => ['boolean'],
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

            $type = AnswerType::from($this->input('answer_type'));

            if ($type === AnswerType::Scale && ! $this->filled('options_text')) {
                $validator->errors()->add('options_text', 'Thang mức tự đặt cần các mức, mỗi dòng "Nhãn = điểm" (ví dụ: Rất thích = 4).');
            }

            if (! $type->isChoice() && $this->filled('options_text') && count(ChoiceList::parseScale($this->input('options_text')) ?? []) < 2) {
                $validator->errors()->add('options_text', 'Cần ít nhất 2 mức, mỗi dòng "Nhãn = điểm", điểm là số (ví dụ: Đúng = 1).');
            }

            if ($type->isChoice()) {
                $choices = $this->input('choices');

                if (count($choices) < 2) {
                    $validator->errors()->add('choices', 'Cần ít nhất 2 lựa chọn.');
                }

                if (! collect($choices)->contains(fn (array $row) => filled($row['group']) && (float) ($row['points'] ?? 1) > 0)) {
                    $validator->errors()->add('choices', 'Cần ít nhất một lựa chọn có nhóm Holland để câu này được tính điểm.');
                }

                $min = (int) $this->input('min_select');
                $max = (int) $this->input('max_select');
                if ($type === AnswerType::Multi && $max > 0 && $min > $max) {
                    $validator->errors()->add('max_select', 'Số ô tối đa phải lớn hơn hoặc bằng số ô tối thiểu.');
                }
            }
        }];
    }

    /**
     * Cột lưu vào bảng questions.
     *
     * @return array<string, mixed>
     */
    public function questionAttributes(QuestionSet $set): array
    {
        $type = AnswerType::from($this->input('answer_type'));

        if ($type->isChoice()) {
            $options = collect($this->input('choices'))->values()->map(fn (array $row, int $index) => [
                'value' => (string) ($index + 1),
                'label' => $row['label'],
                'group' => $row['group'],
                'points' => $row['group'] ? self::number($row['points'] ?? 1) : 0,
            ])->all();
        } else {
            $options = $this->filled('options_text') ? ChoiceList::parseScale($this->input('options_text')) : null;

            // Thang riêng giống hệt thang của bộ thì không lưu riêng (để câu đi theo bộ khi bộ đổi nhãn).
            $inherited = $set->answer_type === $type ? $set->scaleOptions() : $type->defaultScale();
            if ($options !== null && $type !== AnswerType::Scale && ChoiceList::same($options, $inherited)) {
                $options = null;
            }
        }

        $config = array_filter([
            'min_select' => $type === AnswerType::Multi ? ((int) $this->input('min_select') ?: null) : null,
            'max_select' => $type === AnswerType::Multi ? ((int) $this->input('max_select') ?: null) : null,
        ]);

        return [
            'text' => $this->input('text'),
            'answer_type' => $type,
            'riasec' => $type->isChoice() ? null : $this->input('riasec'),
            'display' => $this->input('display') ?: null,
            'form' => $this->input('form'),
            'help_text' => $this->input('help_text'),
            'max_score' => $type->isChoice() ? null : $this->input('max_score'),
            'options' => $options ?: null,
            'config' => $config ?: null,
            'is_active' => $this->boolean('is_active'),
            'is_required' => $this->boolean('is_required'),
            'note' => $this->input('note'),
        ];
    }

    private static function number(mixed $value): int|float
    {
        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.required' => 'Vui lòng nhập nội dung câu hỏi.',
            'text.max' => 'Câu hỏi quá dài (tối đa 500 ký tự).',
            'answer_type.required' => 'Vui lòng chọn kiểu câu.',
            'answer_type.in' => 'Kiểu câu không hợp lệ.',
            'riasec.required' => 'Vui lòng chọn nhóm Holland của câu này.',
            'riasec.in' => 'Nhóm Holland không hợp lệ.',
            'choices.required' => 'Vui lòng nhập các lựa chọn.',
            'choices.*.points.numeric' => 'Điểm phải là số.',
            'max_score.numeric' => 'Điểm tối đa phải là số.',
            'max_score.gt' => 'Điểm tối đa phải lớn hơn 0.',
        ];
    }
}
