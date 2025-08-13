<?php


namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache as FacadesCache;

class TeacherInfoServices
{

    protected UploadFilesServices $uploadFilesServices;

    public function __construct(UploadFilesServices $uploadFilesServices)
    {
        $this->uploadFilesServices = $uploadFilesServices;
    }

    public function get_teacher_info()
    {
        $user = Auth::user();
        $user = User::find($user->id);
        $teacher_info = $user->teacherInfo()->first();
        return  response()->json(["data" => $teacher_info], 200);
    }

    public function add_teacher_info($request)
    {
        $user = Auth::user();
        $user = User::find($user->id);
        $teacher_info = $user->teacherInfo()->first();
        if ($teacher_info) {
            $updated =   $this->update_teacher_info($request);

            if ($updated) {
                $user->save();
                $user = User::find($user->id);
                $teacher_info = $user->teacherInfo()->first();



                $user->image = $teacher_info->image;
                $user->bio = $teacher_info->bio;




                return response()->json($user, 200);
            }
            FacadesCache::forget('courses_by_teacher_' . $user->id);
            return response()->json(["message" => "لم يتم تحديث الملف الشخصي "], 400);
        }
        $data = $request->validate([
            'bio' => 'string|nullable',
            'image' => 'image|mimes:jpeg,png,jpg',
        ], [
            "bio.string" => "وصف المدرب يجب ان يكون نص",
            "image.image" => "صورة المدرب يجب ان تكون صورة",
            "image.mimes" => "صورة المدرب يجب ان تكون jpeg,png,jpg",
        ]);

        $image_path = $this->uploadFilesServices->upload_image($request, 'teachers_images');
        $user->teacherInfo()->create([
            'bio' => $data['bio'],
            'image' => $image_path,
        ]);
        $user->save();
        FacadesCache::forget('courses_by_teacher_' . $user->id);
        return response()->json(["message" => "تم تحديث الملف الشخصي بنجاح", "data" => $user], 200);
    }

    function update_teacher_info($request)
    {
        $user = Auth::user();
        $user = User::find($user->id);
        $teacher_info = $user->teacherInfo()->first();
        $data = $request->validate([
            'bio' => 'string|nullable',
            'image' => 'image|mimes:jpeg,png,jpg',
        ], [
            "bio.string" => "وصف المدرب يجب ان يكون نص",
            "image.image" => "صورة المدرب يجب ان تكون صورة",
            "image.mimes" => "صورة المدرب يجب ان تكون jpeg,png,jpg",
        ]);
        if ($request->hasFile('image')) {
            $image_path = $this->uploadFilesServices->update_image($request, 'teachers_images', $teacher_info->image);
            $teacher_info->update(['image' => $image_path]);
        }
        $teacher_info->update(['bio' => $data['bio']]);
        $user->save();
        return true;
    }
}
