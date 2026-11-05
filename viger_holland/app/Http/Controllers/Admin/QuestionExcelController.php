<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivity;
use App\Models\QuestionSet;
use App\Services\QuestionWorkbook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Xuất / nhập bộ câu hỏi bằng Excel: tải file → sửa → nhập lại (có bước xem trước).
 */
class QuestionExcelController extends Controller
{
    private const IMPORT_DIR = 'imports';

    public function __construct(private readonly QuestionWorkbook $workbook) {}

    public function index(): View
    {
        return view('admin.excel.index', [
            'sets' => QuestionSet::query()->orderByDesc('is_default')->orderBy('sort_order')->get(),
            'history' => AdminActivity::query()->with('user')->where('action', AdminActivity::ACTION_IMPORT)->latest('id')->limit(10)->get(),
        ]);
    }

    public function export(QuestionSet $questionSet): BinaryFileResponse
    {
        $path = $this->workbook->export($questionSet);

        return response()->download($path, 'cau-hoi-'.$questionSet->code.'-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * Bước 1: tải file lên → xem trước. Chưa ghi gì vào database.
     */
    public function preview(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'question_set_id' => ['required', 'integer', 'exists:question_sets,id'],
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [
            'question_set_id.required' => 'Vui lòng chọn bộ câu.',
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải là Excel dạng .xlsx (trong Excel: Lưu thành → Excel Workbook).',
            'file.max' => 'File quá lớn (tối đa 5 MB).',
        ]);

        $set = QuestionSet::findOrFail($validated['question_set_id']);
        $token = (string) Str::uuid();
        $request->file('file')->storeAs(self::IMPORT_DIR, $token.'.xlsx', 'local');

        return view('admin.excel.preview', [
            'set' => $set,
            'token' => $token,
            'fileName' => $request->file('file')->getClientOriginalName(),
            'preview' => $this->workbook->preview($this->path($token), $set),
        ]);
    }

    /**
     * Bước 2: xác nhận → đọc lại file và áp dụng (chỉ khi không còn lỗi).
     */
    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'uuid'],
            'question_set_id' => ['required', 'integer', 'exists:question_sets,id'],
            'file_name' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $this->path($validated['token']);

        if (! is_file($path)) {
            return redirect()->route('admin.excel.index')->withErrors(['file' => 'File đã hết hạn. Vui lòng tải file lên lại.']);
        }

        $set = QuestionSet::findOrFail($validated['question_set_id']);
        $preview = $this->workbook->preview($path, $set);

        if ($preview['file_errors'] !== [] || $preview['counts']['errors'] > 0) {
            return redirect()->route('admin.excel.index')->withErrors(['file' => 'File còn lỗi nên chưa nhập. Vui lòng sửa rồi tải lên lại.']);
        }

        $summary = $this->workbook->apply($preview, $set, (string) ($validated['file_name'] ?? 'file Excel'));
        Storage::disk('local')->delete(self::IMPORT_DIR.'/'.$validated['token'].'.xlsx');

        return redirect()->route('admin.question-sets.show', $set)
            ->with('status', "Đã nhập xong: thêm {$summary['create']} câu, sửa {$summary['update']} câu, tắt {$summary['deactivate']} câu.");
    }

    private function path(string $token): string
    {
        return Storage::disk('local')->path(self::IMPORT_DIR.'/'.$token.'.xlsx');
    }
}
