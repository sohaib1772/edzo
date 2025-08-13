<?php


namespace App\Services;

use App\Models\Course;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{

    protected CodeServices $codeServices;

    public function __construct(CodeServices $codeServices)
    {
        $this->codeServices = $codeServices;
    }

    public function add_subscription(Request $request)
    {   
        $data = $request->validate([
            'course_id' => 'required',
            'code' => 'required',
        ], [
            "course_id.required" => "الدورة مطلوبة",
            "code.required" => "كود الدورة مطلوب",
        ]);

        $course_id = $data['course_id'];
        $code = $data['code'];
        try{
        $course = $this->check_course_exist($course_id);
        $code = $this->check_code_exist($course, $code);
        $this->check_if_subscribed(Auth::user(), $course);
        $user = Auth::user();

        Subscription::create([
            'user_id' => $user->id,
            'course_id' => $course_id
        ]);
        $this->codeServices->delete($code->id);
        Cache::forget("user_{$user->id}_subscribed_courses");
        Cache::flush();
        return response()->json([
            "message" => "تم الاشتراك بنجاح",

        ]);
    }catch (\Exception $e) {
        return response()->json([
            "message" => $e->getMessage()
        ], 500);
    }
    }

    private function check_course_exist($course_id)
    {
        $course = Course::find($course_id);
        if (!$course) {
            throw new \Exception("هذه الدورة غير موجود");
            
        }
        return $course;
    }

    private function check_code_exist(Course $course, $code)
    {
        $code = $course->codes()->firstWhere('code', $code);
        if (!$code) {
            throw new \Exception("كود تالف او غير صحيح");
        }
        return $code;
    }

    private function check_if_subscribed($user, $course)
    {
        $subscription = $user->subscriptions()->firstWhere('course_id', $course->id);
        if ($subscription) {
            throw new \Exception("لقد قمت بالاشتراك في هذه الدورة من قبل");
        }
    }
}
