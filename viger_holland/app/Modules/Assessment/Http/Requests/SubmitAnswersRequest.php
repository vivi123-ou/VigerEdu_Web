<?php

namespace App\Modules\Assessment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Làm gì:   Kiểm tra dữ liệu một trang làm bài: số trang, số sao từng câu (1–5), thời gian trả lời (mili giây).
 *           Bấm "Tiếp tục" thì mọi câu trên trang phải có sao (kiểm tra ở QuizController vì cần biết đề của lượt);
 *           bấm "Phần trước" thì lưu câu nào đã chọn, không bắt buộc đủ.
 * Ai gọi:   QuizController::submit().
 * Dữ liệu:  chỉ dữ liệu khách gửi lên, không có thông tin cá nhân.
 */
class SubmitAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['required', 'integer', 'min:1', 'max:100'],
            'direction' => ['nullable', 'in:next,back'],
            'answers' => ['nullable', 'array', 'max:50'],
            'answers.*' => ['integer', 'between:1,5'],
            'ms' => ['nullable', 'array', 'max:50'],
            'ms.*' => ['nullable', 'integer', 'min:0', 'max:3600000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.*.between' => 'Mỗi câu chọn từ 1 đến 5 sao.',
            'answers.*.integer' => 'Mỗi câu chọn từ 1 đến 5 sao.',
        ];
    }
}
