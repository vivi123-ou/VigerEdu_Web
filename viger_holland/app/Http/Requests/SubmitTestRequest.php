<?php

namespace App\Http\Requests;

use App\Enums\AnswerType;
use App\Models\Question;
use App\Services\TestForm;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Bài nộp: answers[mã câu B] + info[mã câu A]. Quy tắc dựng động từ bảng câu hỏi,
 * nên câu admin thêm mới tự được kiểm tra mà không sửa code.
 */
class SubmitTestRequest extends FormRequest
{
    private ?TestForm $form = null;

    public function authorize(): bool
    {
        return true;
    }

    public function testForm(): TestForm
    {
        return $this->form ??= TestForm::resolve($this->input('bo'));
    }

    protected function prepareForValidation(): void
    {
        $info = collect((array) $this->input('info', []))
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->all();

        foreach ($this->testForm()->infoQuestions as $question) {
            // Câu đang ẩn theo điều kiện: bỏ câu trả lời, không kiểm tra, không lưu.
            if (! $question->isVisibleFor($info)) {
                unset($info[$question->code]);

                continue;
            }

            if ($question->answer_type === AnswerType::Phone && filled($info[$question->code] ?? null)) {
                $info[$question->code] = PhoneNumber::normalize($info[$question->code]);
            }

            if ($question->answer_type === AnswerType::Email && filled($info[$question->code] ?? null)) {
                $info[$question->code] = mb_strtolower($info[$question->code]);
            }
        }

        $this->merge(['info' => $info]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = ['bo' => ['nullable', 'string', 'max:50']];

        foreach ($this->testForm()->hollandQuestions as $question) {
            $key = "answers.{$question->code}";
            $presence = $question->is_required ? 'required' : 'nullable';

            if ($question->answer_type === AnswerType::Multi) {
                $rules[$key] = [$presence, 'array', ...$this->selectCountRules($question)];
                $rules["{$key}.*"] = [Rule::in($this->choiceValues($question))];
            } elseif ($question->answer_type === AnswerType::Single) {
                $rules[$key] = [$presence, Rule::in($this->choiceValues($question))];
            } else {
                $values = array_map('strval', array_column($question->scale($this->testForm()->set), 'value'));
                $rules[$key] = [$presence, Rule::in($values)];
            }
        }

        foreach ($this->testForm()->infoQuestions as $question) {
            if (! $question->isVisibleFor((array) $this->input('info', []))) {
                continue;
            }
            $rules["info.{$question->code}"] = $this->infoRules($question);
        }

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    private function infoRules(Question $question): array
    {
        $presence = $question->is_required ? 'required' : 'nullable';
        $options = $this->choiceValues($question);

        return match ($question->answer_type) {
            AnswerType::Consent => ['accepted'],
            AnswerType::Email => [$presence, 'email:rfc', 'max:255'],
            AnswerType::Phone => [$presence, 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! PhoneNumber::isValid($value)) {
                    $fail('Số điện thoại chưa đúng (cần 10 số, ví dụ 0912 345 678).');
                }
            }],
            AnswerType::Date => [$presence, 'date_format:Y-m-d', 'after:1900-01-01', 'before:today'],
            AnswerType::Number => array_values(array_filter([
                $presence, 'numeric',
                is_numeric($question->configValue('min')) ? 'min:'.$question->configValue('min') : null,
                is_numeric($question->configValue('max')) ? 'max:'.$question->configValue('max') : null,
            ])),
            AnswerType::Single => [$presence, Rule::in($options)],
            AnswerType::Multi => [$presence, 'array', ...$this->selectCountRules($question)],
            AnswerType::Boolean => [$presence, Rule::in(['0', '1'])],
            AnswerType::Rating => [$presence, Rule::in(array_map('strval', array_column($question->scale(), 'value')))],
            AnswerType::Textarea => [$presence, 'string', 'max:2000'],
            default => [$presence, 'string', 'max:255'],
        };
    }

    /**
     * Giá trị hợp lệ của câu chọn (kèm "Khác…" nếu admin bật).
     *
     * @return list<string>
     */
    private function choiceValues(Question $question): array
    {
        $values = array_map('strval', array_column($question->choices(), 'value'));

        return $question->allowsOther() ? [...$values, Question::OTHER] : $values;
    }

    /**
     * Số ô tối thiểu / tối đa của câu chọn nhiều (admin đặt).
     *
     * @return list<string>
     */
    private function selectCountRules(Question $question): array
    {
        return array_values(array_filter([
            (int) $question->configValue('min_select') > 0 ? 'min:'.(int) $question->configValue('min_select') : null,
            (int) $question->configValue('max_select') > 0 ? 'max:'.(int) $question->configValue('max_select') : null,
        ]));
    }

    /**
     * Phải có ít nhất SĐT hoặc email (từ điển dữ liệu: "SĐT hoặc email").
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            // Chọn "Khác…" thì phải ghi rõ.
            foreach ($this->testForm()->infoQuestions as $question) {
                if ($question->allowsOther() && $this->choseOther($question) && blank($this->otherText($question))) {
                    $validator->errors()->add("info.{$question->code}", 'Vui lòng ghi rõ ở ô "Khác".');
                }
            }

            $contactQuestions = $this->testForm()->infoQuestions->whereIn('lead_field', ['phone', 'email']);

            if ($contactQuestions->isEmpty()) {
                return;
            }

            $hasContact = $contactQuestions->contains(fn (Question $question) => filled($this->input("info.{$question->code}")));

            if (! $hasContact) {
                foreach ($contactQuestions as $question) {
                    $validator->errors()->add("info.{$question->code}", 'Vui lòng để lại số điện thoại hoặc email để nhận kết quả.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];

        foreach ($this->testForm()->hollandQuestions as $question) {
            $key = "answers.{$question->code}";
            $messages["{$key}.required"] = 'Bạn chưa trả lời câu này.';
            $messages["{$key}.in"] = 'Câu trả lời không hợp lệ, vui lòng chọn lại.';
            $messages["{$key}.*.in"] = 'Câu trả lời không hợp lệ, vui lòng chọn lại.';
            $messages["{$key}.min"] = 'Vui lòng chọn ít nhất :min ô.';
            $messages["{$key}.max"] = 'Chỉ chọn tối đa :max ô.';
        }

        foreach ($this->testForm()->infoQuestions as $question) {
            $key = "info.{$question->code}";
            $isChoice = in_array($question->answer_type, [AnswerType::Single, AnswerType::Multi, AnswerType::Boolean], true);

            $messages["{$key}.required"] = $isChoice ? 'Vui lòng chọn một mục.' : 'Vui lòng điền thông tin này.';
            $messages["{$key}.accepted"] = 'Bạn cần đồng ý để xem kết quả.';
            $messages["{$key}.email"] = 'Email chưa đúng (ví dụ: ten@gmail.com).';
            $messages["{$key}.date_format"] = 'Ngày chưa đúng, vui lòng chọn lại.';
            $messages["{$key}.after"] = 'Ngày chưa đúng, vui lòng chọn lại.';
            $messages["{$key}.before"] = 'Ngày sinh phải trước hôm nay.';
            $messages["{$key}.numeric"] = 'Vui lòng nhập số.';
            $messages["{$key}.between"] = 'Vui lòng nhập số từ 0 đến 10.';
            $messages["{$key}.in"] = 'Lựa chọn không hợp lệ, vui lòng chọn lại.';
            $messages["{$key}.*.in"] = 'Lựa chọn không hợp lệ, vui lòng chọn lại.';

            if ($question->answer_type === AnswerType::Multi) {
                $messages["{$key}.min"] = 'Vui lòng chọn ít nhất :min ô.';
                $messages["{$key}.max"] = 'Chỉ chọn tối đa :max ô.';
            } elseif ($question->answer_type === AnswerType::Number) {
                $messages["{$key}.min"] = 'Số nhỏ nhất là :min.';
                $messages["{$key}.max"] = 'Số lớn nhất là :max.';
            } else {
                $messages["{$key}.max"] = 'Nội dung quá dài.';
            }
        }

        return $messages;
    }

    /**
     * Câu trả lời Phần A đã lọc (câu ẩn đã bị bỏ).
     *
     * @return array<string, mixed>
     */
    public function infoAnswers(): array
    {
        $answers = (array) $this->validated('info', []);

        // "Khác…": thay bằng chữ khách ghi.
        foreach ($this->testForm()->infoQuestions as $question) {
            if (! $question->allowsOther() || ! array_key_exists($question->code, $answers)) {
                continue;
            }

            $other = mb_substr(trim((string) $this->otherText($question)), 0, 255);
            $answers[$question->code] = is_array($answers[$question->code])
                ? array_values(array_map(fn ($value) => $value === Question::OTHER ? $other : $value, $answers[$question->code]))
                : ($answers[$question->code] === Question::OTHER ? $other : $answers[$question->code]);
        }

        return collect($answers)
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== [])
            ->all();
    }

    private function choseOther(Question $question): bool
    {
        return in_array(Question::OTHER, array_map('strval', (array) $this->input("info.{$question->code}")), true);
    }

    private function otherText(Question $question): ?string
    {
        $text = $this->input("info_other.{$question->code}");

        return is_string($text) ? $text : null;
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function hollandAnswers(): array
    {
        return (array) $this->validated('answers', []);
    }

    /**
     * Tham số utm_ từ link quảng cáo (được JS chép vào form).
     *
     * @return array<string, string>
     */
    public function utm(): array
    {
        return collect((array) $this->input('utm', []))
            ->only(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'])
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->map(fn (string $value) => mb_substr($value, 0, 100))
            ->all();
    }
}
