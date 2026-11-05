<?php

namespace App\Http\Controllers;

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
            'name.required' => 'Vui lòng nhập tên của bạn.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'message.max' => 'Lời nhắn tối đa 2000 ký tự.',
        ]);

        ContactMessage::create($data);

        return redirect(url('/lien-he').'#dang-ky')
            ->with('status', ContactPage::get('contact_success_message'));
    }
}
