<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $sets = QuestionSet::query()
            ->withCount(['questions as active_questions_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        return view('admin.dashboard', [
            'partACount' => Question::part(Question::PART_INFO)->active()->count(),
            'partBCount' => Question::part(Question::PART_HOLLAND)->active()->count(),
            'attemptsToday' => Attempt::where('completed_at', '>=', today())->count(),
            'attemptsTotal' => Attempt::count(),
            'sets' => $sets,
        ]);
    }
}
