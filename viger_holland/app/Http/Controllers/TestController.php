<?php

namespace App\Http\Controllers;

use App\Actions\SubmitTest;
use App\Http\Requests\SubmitTestRequest;
use App\Services\TestForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestController extends Controller
{
    /**
     * Trang làm bài: câu Holland trước, form "Nhận kết quả" ở bước cuối.
     */
    public function show(Request $request): View
    {
        $isAdminPreview = $request->boolean('xem_truoc') && $request->user()?->canAccessAdmin() === true;
        $form = TestForm::resolve($request->query('bo'), includeInactive: $isAdminPreview);

        return view('public.test', [
            'form' => $form,
            'isAdminPreview' => $isAdminPreview,
            'pages' => $form->pages(),
            'scale' => $form->set->scaleOptions(),
            'sections' => $form->infoSections(),
        ]);
    }

    public function submit(SubmitTestRequest $request, SubmitTest $submitTest): RedirectResponse
    {
        $attempt = $submitTest->handle(
            form: $request->testForm(),
            hollandAnswers: $request->hollandAnswers(),
            info: $request->infoAnswers(),
            utm: $request->utm(),
        );

        return redirect()->route('result.show', $attempt->result_token)->with('just_submitted', true);
    }
}
