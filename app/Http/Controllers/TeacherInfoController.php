<?php

namespace App\Http\Controllers;

use App\Services\TeacherInfoServices;
use Illuminate\Http\Request;

class TeacherInfoController extends Controller
{
    protected TeacherInfoServices $teacherInfoServices;
    public function __construct(TeacherInfoServices $teacherInfoServices)
    {
        $this->teacherInfoServices = $teacherInfoServices;
    }


    public function get_teacher_info(Request $request){
        return $this->teacherInfoServices->get_teacher_info();
    }

    public function update_teacher_info(Request $request){
        return $this->teacherInfoServices->update_teacher_info($request);
    }

    public function add_teacher_info(Request $request){
        return $this->teacherInfoServices->add_teacher_info($request);
    }
}
