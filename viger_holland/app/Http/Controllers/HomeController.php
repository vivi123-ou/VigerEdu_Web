<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionSet;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $defaultSet = QuestionSet::active()->where('is_default', true)->first();

        return view('public.home', [
            'questionCount' => $defaultSet
                ? $defaultSet->questions()->where('is_active', true)->count()
                : Question::part(Question::PART_HOLLAND)->active()->count(),
        ]);
    }
}
