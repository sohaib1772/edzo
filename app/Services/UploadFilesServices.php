<?php


namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadFilesServices
{
    public function upload_image(Request $request, $folder)
    {
        $image = $request->file('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();

        $path = $image->storeAs("uploads/{$folder}", $imageName, 'public');

        return $path;
    }

    public function update_image(Request $request, $folder, $oldPath)
    {
        $image = $request->file('image');

        if ($image) {
            $this->delete_image($oldPath);

            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs("uploads/{$folder}", $imageName, 'public');

            return $path;
        }

        return $oldPath;
    }

    public function delete_image($path)
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function delete_user_images($user_id)
    {
        $user = User::find($user_id);
        if ($user) {
            if($user->image){
            $this->delete_image($user->teacherInfo()->first()->image);
            }
        }
    }
    public function delete_user_courses($user_id)
    {
        $user = User::find($user_id);
        if ($user) {
            // get the courses of the user
            $courses = $user->courses()->get();

            // delete the videos of the courses from the storage/private/uploads/videos/courses_videos/course_id
            foreach ($courses as $course) {               
                Storage::disk('storagebox')->deleteDirectory( 'uploads/videos/courses_videos/' . $course->id);
                Storage::disk('public')->delete(  $course->image);
            }
        }
    }

    public function delete_course_videos($course_id)
    {
        $course = Course::find($course_id);
        if ($course) {
            $videos = $course->videos()->get();
            foreach ($videos as $video) {
                $this->delete_video($video->path);
            }
            //delete the folder from the storage/private/uploads/videos/courses_videos/course_id

            Storage::disk('storagebox')->deleteDirectory( 'uploads/videos/courses_videos/' . $course_id);
        }
    }
    public function delete_video($path)
    {
        // delete the video from the storage/private/uploads/videos/courses_videos/course_id

        if ($path && Storage::disk('storagebox')->exists($path)) {
            Storage::disk('storagebox')->delete($path);
        }
    }
}
