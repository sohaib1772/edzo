<?php

namespace App\Services;

use App\Models\Code;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CodeServices
{
    public function get_all_by_course_id($course_id)
    {
        $course = Course::find($course_id);
        if (!$course) {
            return response()->json([
                "message" => "هذه الدورة غير موجود"
            ], 404);
        }
        $codes = $course->codes()->get();

        return response()->json([
            "data" => $codes
        ]);
    }
    public function generate_code(Course $course)
    {
        if (!$course) {
            return response()->json([
                "message" => "هذا الدورة غير موجود"
            ], 404);
        }
        for ($i = 0; $i < 5; $i++) {
                do {
                    $randomCode = \Illuminate\Support\Str::random(16);
                } while (\App\Models\Code::where('code', $randomCode)->exists());

                $course->codes()->create([
                    'code' => $randomCode,
                ]);
            }
    }
    public function delete($id)
    {
        $code = Code::find($id);
        if (!$code) {
            return response()->json([
                "message" => "هذا الكود غير موجود"
            ], 404);
        }
        try {
            $code->delete();
            return response()->json([
                "message" => "تم حذف الكود بنجاح"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

}

?>