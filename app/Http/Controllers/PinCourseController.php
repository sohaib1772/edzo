<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\CourseServices;
use Illuminate\Http\Request;

class PinCourseController extends Controller
{

    protected CourseServices $courseServices;
    public function __construct(CourseServices $courseServices)
    {
        $this->courseServices = $courseServices;
    }
     // تثبيت الكورس
    public function pin($id)
    {
        $course = Course::findOrFail($id);
        $course->is_pin = true;
        $course->save();
        //clear cache
        $this->courseServices->clear_cache($course->id, null, $course->user_id);
        return response()->json([
            'message' => 'تم تثبيت الكورس بنجاح',
            'data' => $course
        ]);
        
    }

    // إلغاء التثبيت
    public function unpin($id)
    {
        $course = Course::findOrFail($id);
        $course->is_pin = false;
        $course->save();
$this->courseServices->clear_cache($course->id, null, $course->user_id);
        return response()->json([
            'message' => 'تم الغاء تثبيت الكورس بنجاح',
            'data' => $course
        ]);
    }
    
}
