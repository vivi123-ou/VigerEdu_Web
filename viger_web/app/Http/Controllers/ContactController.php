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
            'name.required' => __('contact.validation.name_required'),
            'email.required' => __('contact.validation.email_required'),
            'email.email' => __('contact.validation.email_invalid'),
            'phone.required' => __('contact.validation.phone_required'),
            'message.max' => __('contact.validation.message_max'),
        ]);

        ContactMessage::create($data);

        return redirect(url('/lien-he').'#dang-ky')
            ->with('status', ContactPage::get('contact_success_message'));
    }
}
