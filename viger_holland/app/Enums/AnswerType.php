<?php

namespace App\Enums;

/**
 * Kiểu trả lời của câu hỏi (giống Google Form). Giá trị (tiếng Anh) lưu trong database, nhãn tiếng Việt hiện trên admin.
 *
 * Câu Holland (Phần B) dùng: Đúng/Không, các kiểu thang mức, Chọn 1, Chọn nhiều.
 * Câu thông tin khách (Phần A) dùng: chữ, đoạn văn, số, SĐT, email, ngày, chọn 1, chọn nhiều, có/không, đánh giá, đồng ý.
 */
enum AnswerType: string
{
    // Phần A – form thông tin khách
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Phone = 'phone';
    case Email = 'email';
    case Date = 'date';
    case Single = 'single';
    case Multi = 'multi';
    case Boolean = 'boolean';
    case Rating = 'rating';
    case Consent = 'consent';

    // Phần B – câu Holland (Single / Multi dùng chung, mỗi lựa chọn gắn nhóm + điểm)
    case YesNo = 'yes_no';
    case Likert3 = 'likert3';
    case Likert5 = 'likert5';
    case Scale = 'scale';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Chữ',
            self::Textarea => 'Đoạn văn',
            self::Number => 'Số',
            self::Phone => 'Số điện thoại',
            self::Email => 'Email',
            self::Date => 'Ngày',
            self::Single => 'Chọn 1',
            self::Multi => 'Chọn nhiều',
            self::Boolean => 'Có / Không',
            self::Rating => 'Đánh giá (ngôi sao / thang điểm)',
            self::Consent => 'Ô tick đồng ý',
            self::YesNo => 'Đúng / Không',
            self::Likert3 => 'Thang 3 mức',
            self::Likert5 => 'Thang 5 mức',
            self::Scale => 'Thang mức tự đặt',
        };
    }

    /**
     * Kiểu thang: một lựa chọn, mỗi mức là một số điểm (cộng cho nhóm của câu).
     */
    public function isScale(): bool
    {
        return in_array($this, [self::YesNo, self::Likert3, self::Likert5, self::Scale, self::Rating], true);
    }

    /**
     * Kiểu chọn trong danh sách lựa chọn (mỗi lựa chọn có thể gắn nhóm + điểm ở câu Holland).
     */
    public function isChoice(): bool
    {
        return in_array($this, [self::Single, self::Multi], true);
    }

    /**
     * Dùng được cho câu Holland (có chấm điểm).
     */
    public function isHollandType(): bool
    {
        return array_key_exists($this->value, self::hollandOptions());
    }

    /**
     * Thang trả lời mặc định: [{value, label}], điểm tối đa = value lớn nhất.
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
            self::Likert5, self::Scale => [
                ['value' => 0, 'label' => 'Không đúng với tôi'],
                ['value' => 1, 'label' => 'Đúng trong vài trường hợp'],
                ['value' => 2, 'label' => 'Đúng một nửa'],
                ['value' => 3, 'label' => 'Đúng phần lớn'],
                ['value' => 4, 'label' => 'Hoàn toàn đúng'],
            ],
            self::Rating => array_map(fn (int $star) => ['value' => $star, 'label' => (string) $star], range(1, 5)),
            default => [],
        };
    }

    /**
     * Kiểu cho câu thông tin khách.
     *
     * @return array<string, string>
     */
    public static function partAOptions(): array
    {
        return self::labels([self::Text, self::Textarea, self::Number, self::Phone, self::Email, self::Date,
            self::Single, self::Multi, self::Boolean, self::Rating, self::Consent]);
    }

    /**
     * Kiểu cho câu Holland.
     *
     * @return array<string, string>
     */
    public static function hollandOptions(): array
    {
        return self::labels([self::YesNo, self::Likert3, self::Likert5, self::Scale, self::Single, self::Multi]);
    }

    /**
     * Kiểu mặc định của một bộ câu (dùng khi dán nhiều câu).
     *
     * @return array<string, string>
     */
    public static function setDefaultOptions(): array
    {
        return self::labels([self::YesNo, self::Likert3, self::Likert5]);
    }

    /**
     * @param  list<self>  $types
     * @return array<string, string>
     */
    private static function labels(array $types): array
    {
        return collect($types)->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
