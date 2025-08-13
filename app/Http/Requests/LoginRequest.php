<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        return true; // تأكد من السماح بهذا الطلب
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            "uid" => "required",
        ];
    }

    public function messages(): array
    {
        return [
           
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'password.required' => 'كلمة المرور مطلوبة.',
            "uid.required" => "رقم الجهاز مطلوب.",
        ];
    }
}
