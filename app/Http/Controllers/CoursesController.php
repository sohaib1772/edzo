<?php

namespace App\Http\Controllers;

use App\Models\Code;
use App\Services\CodeServices;
use App\Services\CourseServices;
use Illuminate\Http\Request;

class CoursesController extends Controller
{
    //
    protected CourseServices $courseServices;
    protected CodeServices $codeServices;


    public function __construct(CourseServices $courseServices, CodeServices $codeServices)
    {
        $this->courseServices = $courseServices;
        $this->codeServices = $codeServices;
    }

    public function index(Request $request){
        return $this->courseServices->get_all();
    }

    public function get_by_id($id,Request $request){
        return $this->courseServices->get_by_id($id);
    }

    public function get_by_title(Request $request){
        return $this->courseServices->get_by_title($request);
    }

    public function get_my_subscribed_courses(Request $request){
        return $this->courseServices->get_my_subscribed_courses();
    }

    public function get_teacher_courses(Request $request){
        return $this->courseServices->get_teacher_courses();
    }
    public function get_course_videos($id,Request $request){
        return $this->courseServices->get_course_videos($id);
    }
    public function get_courses_by_teacher($id,Request $request){
        return $this->courseServices->get_courses_by_teacher($id);
    }
    

    public function get_codes($id){
        return $this->codeServices->get_all_by_course_id($id);
    }

    

    public function store(Request $request){
        return $this->courseServices->store($request);
    }

    public function update($id,Request $request){
        
        return $this->courseServices->update($id, $request);
    }

    public function destroy($id,Request $request){
        return $this->courseServices->delete($id);
    }

    public function upload_video(Request $request){
        return $this->courseServices->upload_video($request);
    }
    public function delete_video($id,Request $request){
        return $this->courseServices->delete_video($id);
    }

    public function add_codes($id){
        return $this->courseServices->add_codes($id);
    }
    public function get_course_code($id){
        return $this->courseServices->get_course_code($id);
    }
    
}
