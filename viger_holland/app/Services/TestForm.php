<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    public static function resolve(?string $setCode = null): self
    {
        $sets = QuestionSet::active();

        $set = filled($setCode)
            ? (clone $sets)->where('code', $setCode)->first()
            : null;

        $set ??= (clone $sets)->orderByDesc('is_default')->orderBy('sort_order')->firstOrFail();

        return new self(
            set: $set,
            hollandQuestions: $set->questions()->where('is_active', true)->orderBy('sort_order')->orderBy('code')->get(),
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
     * @return array{required: Collection<int, Question>, optional: Collection<int, Question>, consent: Collection<int, Question>}
     */
    public function infoSections(): array
    {
        $consent = $this->infoQuestions->filter(fn (Question $q) => $q->answer_type->value === 'consent');
        $others = $this->infoQuestions->reject(fn (Question $q) => $q->answer_type->value === 'consent');

        return [
            'required' => $others->where('is_required', true)->values(),
            'optional' => $others->where('is_required', false)->values(),
            'consent' => $consent->values(),
        ];
    }
}
