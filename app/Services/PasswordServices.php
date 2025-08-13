<?php 

namespace App\Services;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordServices{


    public function forgotPassword($email){
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new \Exception("هذا المستخدم غير موجود");
        }


        $cachedCode = Cache::get("forgot_password_verification_{$email}");
        if ($cachedCode) {
            Cache::forget("forgot_password_verification_{$email}");
        }

        $code = Str::random(8);
        Cache::put("forgot_password_verification_{$email}", $code, now()->addMinutes(30));
        Mail::to($email)->send(new ForgotPasswordMail($code, "استعادة كلمة المرور"));


    }

    public function resend_password_email($to, $subject){
        $user = User::where('email', $to)->first();
        if (!$user) {
            throw new \Exception("هذا المستخدم غير موجود");
        }

        // if the code is not expired
        $cachedCode = Cache::get("forgot_password_verification_{$to}");
        if ($cachedCode) {
            Mail::to($to)->send(new ForgotPasswordMail($cachedCode, $subject));
        } else {
            $code = Str::random(8);
            Cache::put("forgot_password_verification_{$to}", $code, now()->addMinutes(30));
            Mail::to($to)->send(new ForgotPasswordMail($code, $subject));
        }
    }

    public function reset_password($email, $password,$code){
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new \Exception("هذا المستخدم غير موجود");
        }

        $cachedCode = Cache::get("forgot_password_verification_{$email}");
        if (!$cachedCode || $cachedCode !== $code) {
            throw new \Exception("رمز التفعيل غير صحيح");
        }

        Cache::forget("forgot_password_verification_{$email}");

        $user->password = bcrypt($password);
        $user->save();
    }
}

?>