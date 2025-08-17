<?php

namespace App\Services;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailServices
{


    public function send_verification_email($to, $subject)
    {
        //check if there's an old code and deleted
        $cachedCode = Cache::get("email_verification_{$to}");
        if ($cachedCode) {
            Cache::forget("email_verification_{$to}");
            
        }

        $code = Str::random(8);
        Cache::put("email_verification_{$to}", $code, now()->addMinutes(30));
        Mail::to($to)->send(new EmailVerificationMail($code, $subject));
    }

    public function resend_verification_email($to, $subject)
    {
        //if the user is already verified
        $user = User::where('email', $to)->first();
        if ($user->email_verified_at) {
            throw new \Exception("البريد الإلكتروني موثق");
        }

        $cachedCode = Cache::get("email_verification_{$to}");
        if ($cachedCode) {
            Mail::to($to)->send(new EmailVerificationMail($cachedCode, $subject));
        } else {
            $code = Str::random(8);
            Cache::put("email_verification_{$to}", $code, now()->addMinutes(30));
            Mail::to($to)->send(new EmailVerificationMail($code, $subject));
        }
    }

    public function verify_email(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string'
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'code.required' => 'رمز التفعيل مطلوب'
        ]);
        $cachedCode = Cache::get("email_verification_{$request->email}");

        if (!$cachedCode || $cachedCode !== $request->code) {
            throw new \Exception("رمز التفعيل غير صحيح");
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw new \Exception("المستخدم غير موجود");
        }

        $user->email_verified_at = now();
        $user->save();

        Cache::forget("email_verification_{$request->email}");
    }


}
