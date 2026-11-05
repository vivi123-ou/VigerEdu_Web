<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Bộ câu khách sẽ làm: bộ Phần B (theo ?bo=…, mặc định bộ is_default) + form Phần A đang bật.
 */
final class TestForm
{
    /**
     * @param  Collection<int, Question>  $hollandQuestions
     * @param  Collection<int, Question>  $infoQuestions
     */
    public function __construct(
        public readonly QuestionSet $set,
        public readonly Collection $hollandQuestions,
        public readonly Collection $infoQuestions,
    ) {}

    /**
     * @throws ModelNotFoundException khi không có bộ câu nào đang bật
     */
    public static function resolve(?string $setCode = null, bool $includeInactive = false): self
    {
        // Người quản trị "Xem như khách" được xem cả bộ đang tắt.
        $sets = $includeInactive && filled($setCode) && QuestionSet::where('code', $setCode)->exists()
            ? QuestionSet::query()
            : QuestionSet::active();

        $set = filled($setCode)
            ? (clone $sets)->where('code', $setCode)->first()
            : null;

        $set ??= (clone $sets)->orderByDesc('is_default')->orderBy('sort_order')->firstOrFail();

        $hollandQuestions = $set->questions()->where('is_active', true)->orderBy('sort_order')->orderBy('code')->get();

        // "Xáo trộn thứ tự câu": mỗi khách một thứ tự, giữ nguyên trong cùng phiên (tải lại trang không bị đảo lại).
        if ($set->shuffle_groups && app()->bound('session')) {
            $randomizer = new Randomizer(new Mt19937(crc32((string) session()->getId())));
            $hollandQuestions = new Collection($randomizer->shuffleArray($hollandQuestions->all()));
        }

        return new self(
            set: $set,
            hollandQuestions: $hollandQuestions,
            infoQuestions: Question::part(Question::PART_INFO)->active()->ordered()->get(),
        );
    }

    /**
     * Chia câu Holland thành các trang theo số câu mỗi trang của bộ.
     *
     * @return list<Collection<int, Question>>
     */
    public function pages(): array
    {
        $perPage = max(1, (int) $this->set->per_page);

        return $this->hollandQuestions->chunk($perPage)->map(fn ($page) => $page->values())->values()->all();
    }

    /**
     * Câu thông tin theo thứ tự admin xếp; ô đồng ý luôn tách ra để ở cuối form.
     *
     * @return array{fields: Collection<int, Question>, consent: Collection<int, Question>}
     */
    public function infoSections(): array
    {
        $isConsent = fn (Question $q) => $q->answer_type->value === 'consent';

        return [
            'fields' => $this->infoQuestions->reject($isConsent)->values(),
            'consent' => $this->infoQuestions->filter($isConsent)->values(),
        ];
    }
}
