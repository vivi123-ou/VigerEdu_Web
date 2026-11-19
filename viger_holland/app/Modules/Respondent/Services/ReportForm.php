<?php

namespace App\Modules\Respondent\Services;

use App\Modules\Assessment\Models\TestSession;
use App\Modules\Respondent\Models\FieldValue;
use App\Modules\Respondent\Models\FormField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Làm gì:   Form "Cập nhật thông tin để xem báo cáo đầy đủ" dựng từ bảng form_fields (admin sửa chữ, thứ tự, bắt buộc, ẩn hiện):
 *           ô nào hiện, luật kiểm tra từng ô, chữ của ô theo lựa chọn khác (ví dụ chọn "Phụ huynh" thì ẩn SĐT học sinh,
 *           ô Email đổi thành "Email của học sinh"), chuẩn hóa SĐT, lưu giá trị (mã hóa) vào field_values.
 * Ai gọi:   ReportController (form, gửi form), ReportAccessService (lấy họ tên, email, SĐT theo crm_key).
 * Dữ liệu:  form_fields, field_values. Không ghi giá trị khách điền vào log.
 */
class ReportForm
{
    /**
     * Các ô đang bật trên form, theo thứ tự admin xếp.
     *
     * @return Collection<int, FormField>
     */
    public function fields(): Collection
    {
        return FormField::query()->where('is_visible', true)->ordered()->get();
    }

    /**
     * Ô có bị ẩn theo lựa chọn ở ô khác không (conditions.hide_if = {ô: [giá trị…]}).
     *
     * @param  array<string, mixed>  $input
     */
    public function isHidden(FormField $field, array $input): bool
    {
        foreach ($field->conditions['hide_if'] ?? [] as $otherKey => $values) {
            if (in_array((string) ($input[$otherKey] ?? ''), array_map('strval', (array) $values), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Chữ của ô theo lựa chọn ở ô khác (conditions.label_if = {ô: {giá trị: chữ}}).
     *
     * @param  array<string, mixed>  $input
     */
    public function labelFor(FormField $field, array $input): string
    {
        foreach ($field->conditions['label_if'] ?? [] as $otherKey => $labels) {
            $chosen = (string) ($input[$otherKey] ?? '');
            if ($chosen !== '' && isset($labels[$chosen])) {
                return (string) $labels[$chosen];
            }
        }

        return $field->label;
    }

    /**
     * Luật kiểm tra và tên ô (để thông báo lỗi dùng đúng chữ khách thấy). Ô đang ẩn thì bỏ qua.
     *
     * @param  array<string, mixed>  $input
     * @return array{rules: array<string, list<mixed>>, attributes: array<string, string>, messages: array<string, string>}
     */
    public function rules(array $input): array
    {
        $rules = $attributes = $messages = [];
        $year = (int) now()->year;

        foreach ($this->fields() as $field) {
            if ($this->isHidden($field, $input)) {
                continue;
            }

            $base = [$field->is_required ? 'required' : 'nullable'];
            $rules[$field->key] = match ($field->type) {
                'email' => [...$base, 'string', 'max:150', 'email:rfc'],
                'phone' => [...$base, 'string', 'max:20', 'regex:/^0\d{9,10}$/'],
                'year' => [...$base, 'integer', 'between:'.($year - 70).','.($year - 5)],
                'date' => [...$base, 'date_format:Y-m-d', 'after_or_equal:'.self::oldestDate(), 'before_or_equal:'.self::youngestDate()],
                'number' => [...$base, 'numeric', 'between:0,100'],
                'select', 'radio' => [...$base, 'string', Rule::in(array_column($field->options ?? [], 'value'))],
                'textarea' => [...$base, 'string', 'max:1000'],
                default => [...$base, 'string', 'max:150'],
            };
            // Viết thường chữ đầu để câu đọc tự nhiên: "Vui lòng nhập điện thoại / Zalo của phụ huynh." (:Attribute viết hoa lại khi đứng đầu câu).
            $label = $this->labelFor($field, $input);
            $attributes[$field->key] = mb_strtolower(mb_substr($label, 0, 1)).mb_substr($label, 1);
            $messages[$field->key.'.regex'] = ':Attribute chưa đúng. Ví dụ: 0912 345 678.';
            $messages[$field->key.'.between'] = ':Attribute chưa đúng.';
            $messages[$field->key.'.integer'] = ':Attribute chưa đúng.';
            $messages[$field->key.'.email'] = ':Attribute chưa đúng. Ví dụ: ten@gmail.com.';
            $messages[$field->key.'.date_format'] = ':Attribute chưa đúng.';
            $messages[$field->key.'.after_or_equal'] = ':Attribute chưa đúng.';
            $messages[$field->key.'.before_or_equal'] = ':Attribute chưa đúng.';
            if (in_array($field->type, ['select', 'radio', 'year', 'date'], true)) {
                $messages[$field->key.'.required'] = 'Vui lòng chọn :attribute.';
            }
        }

        return ['rules' => $rules, 'attributes' => $attributes, 'messages' => $messages];
    }

    /**
     * Chuẩn hóa trước khi kiểm tra: bỏ khoảng trắng thừa; SĐT bỏ dấu cách / chấm / gạch, +84 hoặc 84 đầu số đổi về 0; email chữ thường.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalize(array $input): array
    {
        foreach ($this->fields() as $field) {
            $value = $input[$field->key] ?? null;
            if (! is_string($value)) {
                continue;
            }
            $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
            $input[$field->key] = match ($field->type) {
                'phone' => self::normalizePhone($value),
                'email' => mb_strtolower($value),
                default => $value,
            };
        }

        return $input;
    }

    /**
     * Khoảng ngày chọn được trên lịch ngày sinh: từ 70 năm trước đến 5 năm trước.
     */
    public static function oldestDate(): string
    {
        return now()->subYears(70)->toDateString();
    }

    public static function youngestDate(): string
    {
        return now()->subYears(5)->toDateString();
    }

    public static function normalizePhone(string $value): string
    {
        $digits = preg_replace('/[\s.\-()]/', '', $value) ?? '';

        return preg_replace('/^(\+84|84)(?=\d{9}$)/', '0', $digits) ?? $digits;
    }

    /**
     * Lưu giá trị đã kiểm tra cho lượt làm bài (ghi đè lần điền trước, ví dụ khi khách bấm "Sửa email"). Ô trống không lưu.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(TestSession $session, array $data): void
    {
        $fields = $this->fields()->keyBy('key');

        DB::transaction(function () use ($session, $data, $fields): void {
            $session->fieldValues()->delete();
            foreach ($data as $key => $value) {
                if ($value === null || $value === '' || ! $fields->has($key)) {
                    continue;
                }
                FieldValue::query()->create([
                    'test_session_id' => $session->id,
                    'form_field_id' => $fields[$key]->id,
                    'key' => $key,
                    'value' => (string) $value,
                ]);
            }
        });
    }

    /**
     * Giá trị khách đã điền cho lượt làm bài: [key => value] (dùng để điền lại form khi "Sửa email").
     *
     * @return array<string, string>
     */
    public function values(TestSession $session): array
    {
        return $session->fieldValues()->get()->mapWithKeys(fn (FieldValue $v) => [$v->key => (string) $v->value])->all();
    }

    /**
     * Giá trị theo tên trường bên CRM (student_name, email, phone, parent_phone, birth_date, role…), dùng để tạo hồ sơ khách.
     *
     * @return array<string, string>
     */
    public function valuesByCrmKey(TestSession $session): array
    {
        return $session->fieldValues()->with('formField')->get()
            ->filter(fn (FieldValue $v) => filled($v->formField?->crm_key))
            ->mapWithKeys(fn (FieldValue $v) => [$v->formField->crm_key => (string) $v->value])
            ->all();
    }
}
