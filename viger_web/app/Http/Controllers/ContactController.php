<?php

namespace App\Http\Controllers;

use App\Jobs\SendContactToCrm;
use App\Models\ContactMessage;
use App\Support\ContactPage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => __('contact.validation.name_required'),
            'email.required' => __('contact.validation.email_required'),
            'email.email' => __('contact.validation.email_invalid'),
            'phone.required' => __('contact.validation.phone_required'),
            'message.max' => __('contact.validation.message_max'),
        ]);

        // Lưu đúng câu đồng ý khách thấy dưới nút gửi (gửi kèm sang CRM — HOP_DONG_KET_NOI mục 2.1)
        $data['consent_text'] = self::consentText();

        $message = ContactMessage::create($data);

        // Gửi sang CRM qua hàng đợi: CRM lỗi thì tự thử lại, form vẫn được lưu ở web chính
        SendContactToCrm::dispatch($message);

        return redirect(localized_url('/lien-he').'#dang-ky')
            ->with('status', ContactPage::get('contact_success_message'));
    }

    /** Câu đồng ý hiển thị dưới nút gửi (theo ngôn ngữ đang xem và chữ trên nút). */
    public static function consentText(): string
    {
        return __('contact.consent_text', ['button' => ContactPage::get('contact_form_button')]);
    }
}
