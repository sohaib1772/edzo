<?php

namespace App\Services;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\Str;
use function Symfony\Component\String\b;

class UserServices
{
    protected EmailServices $emailServices;
    protected PasswordServices $passwordServices;
    protected UploadFilesServices $uploadFilesServices;


    public function __construct(EmailServices $emailServices, PasswordServices $passwordServices, UploadFilesServices $uploadFilesServices)
    {
        $this->emailServices = $emailServices;
        $this->passwordServices = $passwordServices;
        $this->uploadFilesServices = $uploadFilesServices;
    }
    public function getUser()
    {
        $user = Auth::user();
        $user = User::find($user->id);
        if ($user->isTeacher() || $user->isAdmin()) {
            $user->teacher_info = $user->teacherInfo()->first();
        }
        $user = $user->makeHidden("id");
        return response()->json([
            "data" => $user,
        ]);
    }

    public function get_user_by_name(Request $request)
    {
        $name = $request->name;
        // get users where name like the request and limit 500 and don't get any user with requested user id
        $user = Auth::user();
        $users = User::where('name', 'like', '%' . $name . '%')->limit(500)->where('id', '!=', $user->id)->get();
        return response()->json([
            "data" => $users,
        ]);
    }

    //get all teachers  for admin with teacher info and teacher courses numbers and teacher courses subscribers count and teacher subscribers count every month
    public function get_teachers()
    {
        $users = User::whereIn('role', ['teacher', 'admin'])->get();

        $data = $users->map(function ($user) {
            // بيانات المعلم
            $teacherInfo = $user->teacherInfo()->first();

            // جلب الكورسات الخاصة بالمعلم مع الإحصائيات
            $courses = $user->courses()->select('id', 'title')->get()->map(function ($course) {
                // آخر 3 أشهر من الاشتراكات لهذا الكورس
                $monthlySubscribers = DB::table('subscriptions')
                    ->where('course_id', $course->id)
                    ->select(
                        DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                        DB::raw("COUNT(*) as total_subscribers")
                    )
                    ->groupBy('month')
                    ->orderBy('month', 'desc')
                    ->take(3) // آخر 3 أشهر
                    ->get()
                    ->reverse() // ترتيب من الأقدم للأحدث
                    ->values();

                return [
                    "id" => $course->id,
                    "title" => $course->title,
                    "monthly_subscribers" => $monthlySubscribers
                ];
            });

            // العدد الإجمالي للاشتراكات
            $totalSubscribers = $courses->sum(function ($course) {
                return collect($course['monthly_subscribers'])->sum('total_subscribers');
            });

            return [
                "user" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "role" => $user->role,
                    "email" => $user->email,
                    "teacher_info" => $teacherInfo,
                    "teacher_courses" => $courses,
                    "teacher_courses_count" => $courses->count(),
                    "total_subscribers_count" => $totalSubscribers,
                ]
            ];
        });

        return response()->json([
            "data" => $data
        ]);
    }

    public function set_user_role(Request $request)
    {

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:student,teacher',
        ], [
            'user_id.required' => 'المستخدم مطلوب',
            'user_id.exists' => 'المستخدم غير موجود',
            'role.required' => 'الصلاحية مطلوبة',
            'role.in' => 'الصلاحية غير صحيحة',
        ]);
        $user_id = $data['user_id'];
        $role = $data['role'];
        $user = User::find($user_id);
        if ($role != "student" && $role != "teacher") {
            return response()->json([
                "message" => "خطأ في اختيار الصلاحية",
            ]);
        }
        if (!$user) {
            return response()->json([
                "message" => "هذا المستخدم غير موجود",
            ]);
        }

        $user->role = $role;
        $user->save();
        return response()->json([
            "data" => $user,
        ]);
    }

    public function register(RegisterRequest $request)
    {
        try {
            $data = $request->validated();

            $user = User::where('email', $data['email'])->first();

            if ($user && $user->email_verified_at) {
                return response()->json([
                    "message" => "البريد الإلكتروني مستخدم من قبل",
                    "email_verified" => "true",
                ], 401);
            }else if ($user && !$user->email_verified_at) {
                $user->delete();
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'uid' => $data['uid'],
            ]);

            
            $this->emailServices->send_verification_email($user->email, "تفعيل الحساب");
            return response()->json([
                "message" => "تم انشاء المستخدم بنجاح يرجى التحقق من البريد الالكتروني لتأكيد الحساب",
            ]);
        } catch (Exception $e) {
            FacadesLog::error("message");
            return response()->json([
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function verifyEmail(Request $request)
    {

        try {
            $this->emailServices->verify_email($request);
            return response()->json([
                "message" => "تم التحقق بنجاح",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function resendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',

        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.exists' => 'البريد الإلكتروني غير مسجل',
        ]);
        try {
            $this->emailServices->resend_verification_email($request->email, "تفعيل الحساب");
            return response()->json([
                "message" => "تم ارسال رمز التفعيل بنجاح يرجى التحقق من البريد الالكتروني",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function login(LoginRequest $data)
    {
        $data = $data->validated();

        $user = User::where('email', $data['email'])->first();



        if (!$user) {
            return response()->json([
                "message" => "البريد الإلكتروني غير صحيح",
            ], 401);
        }

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json([
                "message" => "كلمة المرور غير صحيحة",
            ], 401);
        }

        if ($data['uid'] != $user->uid && $user->role == "student") {
            return response()->json([
                "message" => "لايمكن الدخول بهذا الجهاز من هذا الحساب",
            ], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                "message" => "البريد الإلكتروني غير مفعل",
                "email_status" => $user->email_verified_at ? "true" : "false",
            ], 401);
        }

        //check if the account have a token and response can't login with tow devices
        // if ($user->tokens()->first() && $user->isAdmin() == false && $user->isTeacher() == false) {
        //     return response()->json([
        //         "message" => "الحساب مفتوح بجهاز اخر يرجى تسجيل الخروج ثم المحاولة مرة اخرى",
        //     ], 401);
        // }




        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            "message" => "تم تسجيل الدخول بنجاح",
            "data" => $user,
            "token" => $token,
        ]);
    }

    public function change_uid_request(LoginRequest $data)
    {

        $data = $data->validated();

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json([
                "message" => "البريد الإلكتروني غير صحيح",
            ], 401);
        }

        if (!Hash::check($data['password'], $user->password)) {
            return response()->json([
                "message" => "كلمة المرور غير صحيحة",
            ], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                "message" => "البريد الإلكتروني غير مفعل",
                "email_status" => $user->email_verified_at ? "true" : "false",
            ], 401);
        }
       
        $user->tokens()->delete();

        $user->uid = $data['uid'];
        
        $user->save();
        return response()->json([
            "message" => "تم تغيير الجهاز بنجاح",
            "data" => $user,
        ]);
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                "message" => "لم يتم تسجيل الدخول",
            ], 401);
        }

        try {

            $request->user()->tokens()->delete();

            return response()->json([
                "message" => "تم تسجيل الخروج بنجاح",
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.exists' => 'البريد الإلكتروني غير مسجل',
        ]);
        try {
            $this->passwordServices->forgotPassword($request->email);
            return response()->json([
                "message" => "تم ارسال رمز استعادة كلمة المرور بنجاح يرجى التحقق من البريد الالكتروني",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function resend_forgot_password_email(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.exists' => 'البريد الإلكتروني غير مسجل',
        ]);
        try {
            $this->passwordServices->resend_password_email($request->email, "استعادة كلمة المرور");
            return response()->json([
                "message" => "تم ارسال رمز استعادة كلمة المرور بنجاح يرجى التحقق من البريد الالكتروني",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|confirmed',
            'code' => 'required|string',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.exists' => 'البريد الإلكتروني غير مسجل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.string' => 'كلمة المرور يجب ان تكون نصية',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
            'code.required' => 'رمز التفعيل مطلوب',
        ]);
        try {
            $this->passwordServices->reset_password($request->email, $request->password, $request->code);
            return response()->json([
                "message" => "تم تغيير كلمة المرور بنجاح",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }


    public function delete_user(Request $request)
    {
        $user = Auth::user();
        $user = User::find($user->id);
        $data = $request->validate([
            "confirmed" => "required|string",
        ], [
            "confirmed.required" => "تأكيد مطلوب",
            "confirmed.string" => "تأكيد يجب ان تكون نصية",
        ]);

        if ($data["confirmed"] != "نعم") {
            return response()->json([
                "message" => "تأكيد غير صحيح",
            ]);
        }

        if (!$user) {
            return response()->json([
                "message" => "هذا المستخدم غير موجود",
            ]);
        }

        //delete user images and courses from storage
        $this->uploadFilesServices->delete_user_images($user->id);
        $this->uploadFilesServices->delete_user_courses($user->id);

        Cache::flush();

        $user->delete();

        return response()->json([
            "message" => "تم حذف المستخدم بنجاح",
        ]);
    }
}
