<?php

namespace App\Enums;

/**
 * Kiểu trả lời của câu hỏi. Giá trị (tiếng Anh) lưu trong database, nhãn tiếng Việt hiện trên admin.
 */
enum AnswerType: string
{
    // Phần A – form thông tin khách
    case Text = 'text';
    case Number = 'number';
    case Phone = 'phone';
    case Email = 'email';
    case Date = 'date';
    case Single = 'single';
    case Multi = 'multi';
    case Boolean = 'boolean';
    case Consent = 'consent';

    // Phần B – câu Holland
    case YesNo = 'yes_no';
    case Likert3 = 'likert3';
    case Likert5 = 'likert5';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Chữ',
            self::Number => 'Số',
            self::Phone => 'Số điện thoại',
            self::Email => 'Email',
            self::Date => 'Ngày',
            self::Single => 'Chọn 1',
            self::Multi => 'Chọn nhiều',
            self::Boolean => 'Có / Không',
            self::Consent => 'Ô tick đồng ý',
            self::YesNo => 'Đúng / Không',
            self::Likert3 => 'Thang 3 mức',
            self::Likert5 => 'Thang 5 mức',
        };
    }

    public function isScored(): bool
    {
        return in_array($this, [self::YesNo, self::Likert3, self::Likert5], true);
    }

    /**
     * Thang trả lời mặc định cho câu Phần B: [{value, label}], điểm tối đa = value lớn nhất.
     *
     * @return list<array{value: int, label: string}>
     */
    public function defaultScale(): array
    {
        return match ($this) {
            self::YesNo => [
                ['value' => 1, 'label' => 'Đúng'],
                ['value' => 0, 'label' => 'Không'],
            ],
            self::Likert3 => [
                ['value' => 0, 'label' => 'Không đúng với tôi'],
                ['value' => 1, 'label' => 'Đúng một phần'],
                ['value' => 2, 'label' => 'Hoàn toàn đúng'],
            ],
            self::Likert5 => [
                ['value' => 0, 'label' => 'Không đúng với tôi'],
                ['value' => 1, 'label' => 'Đúng trong vài trường hợp'],
                ['value' => 2, 'label' => 'Đúng một nửa'],
                ['value' => 3, 'label' => 'Đúng phần lớn'],
                ['value' => 4, 'label' => 'Hoàn toàn đúng'],
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function partAOptions(): array
    {
        $cases = [self::Text, self::Number, self::Phone, self::Email, self::Date, self::Single, self::Multi, self::Boolean, self::Consent];

        return collect($cases)->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
