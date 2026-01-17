<?php


namespace App\Services;

use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class CourseServices
{
    protected UploadFilesServices $uploadFilesServices;
    protected CodeServices $codeServices;
    protected UploadVideoServices $uploadVideoServices;

    protected PlaylistServices $playlistServices;

    public function __construct(UploadFilesServices $uploadFilesServices, CodeServices $codeServices, UploadVideoServices $uploadVideoServices, PlaylistServices $playlistServices)
    {
        $this->uploadFilesServices = $uploadFilesServices;
        $this->codeServices = $codeServices;
        $this->uploadVideoServices = $uploadVideoServices;
        $this->playlistServices = $playlistServices;
    }


    public function get_all()
    {
        $user = Auth::user();

        if ($user) {
            $user = User::find($user->id);
            // نتحقق من الاشتراكات مباشرة
            $subscribedIds = Cache::remember("subscriptions_user_{$user->id}", now()->addMinutes(10), function () use ($user) {
                return $user->subscriptions()->pluck('course_id')->toArray();
            });
        } else {
            $subscribedIds = [];
        }

        // عرض للتأكد من البيانات
        // dd($user?->id, $subscribedIds);

        $courses = Cache::remember('courses_all', now()->addMinutes(10), function () {
            return Course::with('teacher')
                ->withCount('subscribers')
                ->orderByDesc('is_pin')   // أول شي نرتب حسب pin
                ->orderByDesc('created_at') // بعدها الأحدث
                ->get()
                ->values();
        });

        $courses->transform(function ($course) use ($subscribedIds) {
            $course->is_subscribe = in_array($course->id, $subscribedIds);
            $course->teacher_name = $course->teacher->name ?? null;
            if ($course->teacher->teacherInfo()->exists()) {
                $course->teacher_info = $course->teacher->teacherInfo()->first()
                    ->makeHidden(['id', 'user_id', 'created_at', 'updated_at']);
            }
            return $course->makeHidden(['teacher']);
        });

        return response()->json([
            "data" => $courses
        ]);
    }

    public function get_by_title(Request $request)
    {
        $title = $request->title;
        $user = Auth::user();
        if ($user) {
            $user = User::find($user->id);
        }

        if (!$title) {
            return $this->get_all();
        }

        $cacheKey = "courses_title_" . md5($title);

        $courses = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($title) {
            return Course::with('teacher')
                ->withCount('subscribers')
                ->where('title', 'like', '%' . $title . '%')
                ->get();
        });

        // تصحيح هنا أيضًا
        $subscribedIds = [];
        if ($user) {
            $subscribedIds = $user ? $user->subscriptions()->pluck('course_id')->toArray() : [];
        }

        $courses->transform(function ($course) use ($subscribedIds) {
            $course->is_subscribe = in_array($course->id, $subscribedIds);
            $course->teacher_name = $course->teacher->name ?? null;
            $course->subscribers_count = $course->subscribers_count;
            if ($course->teacher->teacherInfo()->exists()) {
                $course->teacher_info = $course->teacher->teacherInfo()->first()->makeHidden(['id', 'user_id', 'created_at', 'updated_at']);
            }
            return $course->makeHidden(['teacher']);
        });

        return response()->json([
            "sent_title" => $title,
            "courses_found_count" => $courses->count(),
            "data" => $courses
        ]);
    }

    public function get_by_id($id)
    {
        $user = Auth::user();
        if ($user) {
            $user = User::find($user->id);
        }

        $course = Cache::remember("course_$id", now()->addMinutes(10), function () use ($id) {
            return Course::with('teacher')
                ->withCount('subscribers')
                ->find($id);
        });
        $subscribedIds = [];
        if ($course && $user) {
            // تصحيح شرط التحقق من الاشتراك
            $course->is_subscribe = $user->subscriptions()->where('course_id', $course->id)->exists();
        } else {
            $course->is_subscribe = false;
        }

        $course->teacher_name = $course->teacher->name ?? null;
        $course->subscribers_count = $course->subscribers_count;

        return response()->json([
            "data" => $course
        ]);
    }

    public function get_my_subscribed_courses()
    {
        $user = Auth::user();
        if ($user) {
            $user = User::find($user->id);
        }
        $cacheKey = "user_{$user->id}_subscribed_courses";

        $courses = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            return User::find($user->id)->subscriptions()
                ->with('teacher')
                ->withCount('subscribers')
                ->get();
        });

        $subscribedIds = [];
        if ($user) {
            $subscribedIds = $user ? $user->subscriptions()->pluck('course_id')->toArray() : [];
        }



        $courses->transform(function ($course) use ($subscribedIds) {
            $course->is_subscribe = in_array($course->id, $subscribedIds);
            $course->teacher_name = $course->teacher->name ?? null;
            $course->subscribers_count = $course->subscribers_count;
            if ($course->teacher->teacherInfo()->exists()) {
                $course->teacher_info = $course->teacher->teacherInfo()->first()->makeHidden(['id', 'user_id', 'created_at', 'updated_at']);
            }
            return $course->makeHidden(['teacher']);
        });

        return response()->json([
            "data" => $courses
        ]);
    }

    public function get_teacher_courses()
    {
        $user = Auth::user();
        if ($user) {
            $user = User::find($user->id);
        }
        $cacheKey = "teacher_{$user->id}_courses";

        $courses = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            return User::find($user->id)->courses()
                ->with('teacher')
                ->withCount('subscribers')
                ->get();
        });
        $subscribedIds = [];
        if ($user) {
            $subscribedIds = $user ? $user->subscriptions()->pluck('course_id')->toArray() : [];
        }


        $courses->transform(function ($course) use ($subscribedIds) {
            $course->is_subscribe = in_array($course->id, $subscribedIds);
            $course->teacher_name = $course->teacher->name ?? null;
            $course->subscribers_count = $course->subscribers_count;
            if ($course->teacher->teacherInfo()->exists()) {
                $course->teacher_info = $course->teacher->teacherInfo()->first()->makeHidden(['id', 'user_id', 'created_at', 'updated_at']);
            }
            return $course->makeHidden(['teacher']);
        });

        return response()->json([
            "data" => $courses
        ]);
    }

    public function get_courses_by_teacher($id)
    {
        $user = Auth::user();
        if ($user) {
            $user = User::find($user->id);
        }

        // مفتاح الكاش يكون فيه ID المعلم
        $cacheKey = 'courses_by_teacher_' . $id;

        $courses = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($id) {
            $teacher = User::find($id);
            return $teacher->courses()
                ->with('teacher') // حتى نجيب بيانات المدرس
                ->withCount('subscribers')
                ->orderByDesc('created_at')
                ->get();
        });
        $subscribedIds = [];
        if ($user) {
            $subscribedIds = $user ? $user->subscriptions()->pluck('course_id')->toArray() : [];
        }

        $courses->transform(function ($course) use ($subscribedIds) {
            $course->is_subscribe = in_array($course->id, $subscribedIds);
            $course->teacher_name = $course->teacher->name ?? null;
            $course->subscribers_count = $course->subscribers_count;
            if ($course->teacher->teacherInfo()->exists()) {
                $course->teacher_info = $course->teacher->teacherInfo()->first()->makeHidden(['id', 'user_id', 'created_at', 'updated_at']);
            }
            return $course->makeHidden(['teacher']);
        });

        return response()->json([
            "data" => $courses
        ]);
    }

    public function get_course_videos($id)
    {
        $course = Course::with(['playlists.videos'])->find($id);

        if (!$course) {
            return response()->json([
                "message" => "هذه الدورة غير موجودة"
            ], 404);
        }

        // الفيديوهات المباشرة (بدون بلاي ليست)
        $directVideos = $course->videos()
            ->whereNull('playlist_id')
            ->orderBy('order')
            ->get();

        return response()->json([
            "direct_videos" => $directVideos->makeHidden(['url']),
            "playlists"     => $course->playlists()
                ->orderBy('order')   // ✅ ترتيب البلاي ليست
                ->get()
                ->map(function ($playlist) {
                    return [
                        "id"     => $playlist->id,
                        "title"  => $playlist->title,
                        "videos" => $playlist->videos()
                            ->orderBy('order')   // ✅ ترتيب الفيديوهات داخل البلاي ليست
                            ->get()
                            ->makeHidden(['url']),
                    ];
                }),
        ]);
    }

    public  function  store(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'image' => 'required|image|mimes:jpeg,png,jpg',
            'price' => 'sometimes|nullable|numeric',
            'telegramUrl' => 'string|nullable',

        ], [
            "title.required" => "اسم الدورة مطلوب",
            "description.required" => "وصف الدورة مطلوب",
            "teacher_id.required" => "اسم المدرب مطلوب",
            "image.required" => "صورة الدورة مطلوبة",
            "image.image" => "صورة الدورة يجب ان تكون صورة",
            "image.mimes" => "صورة الدورة يجب ان تكون jpeg,png,jpg",
            "price.numeric" => "سعر الدورة يجب ان يكون رقم",


        ]);
        try {
            $image_path = $this->uploadFilesServices->upload_image($request, 'courses_images');
            $course = Course::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'image' => $image_path,
                'price' => $data['price'] ?? 0,
                'user_id' => $user->id,
                'telegram_url' => $data['telegramUrl'] ?? null,
                'is_pin' => false,
            ]);


            //generate 5 code for course
            $this->codeServices->generate_code($course);


            //get course with codes            
            $course->load("codes");
            $this->clear_cache(null, null, $user->id);
            $course->teacher_name = $course->teacher->name ?? null;
            $course->subscribers_count = $course->subscribers_count;
            $course->is_subscribe = false;
            $course->teacher_info = $course->teacher->teacherInfo()->first();

            return response()->json([
                "data" => $course
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function update($id, Request $request)
    {
        $user = Auth::user();
        $course = Course::find($id);
        if (!$course) {
            return response()->json([
                "message" => "هذا الدورة غير موجود"
            ], 404);
        }
        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg',

            'price' => 'sometimes|nullable|numeric',
            'telegramUrl' => 'string|nullable',
        ], [
            "title.required" => "اسم الدورة مطلوب",
            "description.required" => "وصف الدورة مطلوب",
            "teacher_id.required" => "اسم المدرب مطلوب",
            "image.required" => "صورة الدورة مطلوبة",
            "image.image" => "صورة الدورة يجب ان تكون صورة",
            "image.mimes" => "صورة الدورة يجب ان تكون jpeg,png,jpg",
            'price' => 'sometimes|nullable|numeric',
        ]);
        try {
            if ($request->hasFile('image')) {
                $image_path = $this->uploadFilesServices->update_image($request, 'courses_images', $course->image);
            } else {
                $image_path = $course->image;
            }

            $course->update([
                'title' => $data['title'],
                'description' => $data['description'],
                'image' => $image_path,
                'price' => $data['price'] ?? $course->price,
                'user_id' => $user->id,
                'telegram_url' => $data['telegramUrl'] ?? $course->telegram_url,
            ]);
            $this->clear_cache($course->id, null, $user->id);
            $course->load("codes");
            $course->teacher_name = $course->teacher->name ?? null;
            $course->subscribers_count = $course->subscribers_count;
            $course->is_subscribe = false;
            $course->teacher_info = $course->teacher->teacherInfo()->first();

            return response()->json([
                "data" => $course
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return response()->json([
                "message" => "هذا الدورة غير موجود"
            ], 404);
        }
        if ($course->user_id != Auth::user()->id) {
            return response()->json([

                "message" => "لايمكنك حذف هذه الدورة"
            ], 403);
        }
        try {
            // $this->uploadFilesServices->delete_image($course->image);
            //$this->uploadFilesServices->delete_course_videos($course->id);

            $course->delete();
            $this->clear_cache($course->id, null, $course->user_id);
            return response()->json([
                "message" => "تم حذف الدورة بنجاح"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function upload_video(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required',
            "url" => 'required',
            'is_paid' => 'required',
            "duration" => 'nullable|numeric',
            'playlist_id' => 'nullable|exists:playlists,id',
        ], [
            "course_id.required" => "الدورة مطلوبة",
            "course_id.exists" => "الدورة غير موجودة",
            "title.required" => "العنوان مطلوب",
            "is_paid.required" => "نوع الفيديو مطلوب",
            "url.required" => "رابط الفيديو مطلوب",
            "playlist_id.exists" => "القائمة غير موجودة",

            //"file.required" => "فيديو مطلوب",
        ]);
        $course = Course::where(
            "id",
            "=",
            $data['course_id']
        )->firstOrFail();
        if (!$course) {
            return response()->json([
                "message" => "هذه الدورة غير موجود"
            ], 404);
        }

        $youtube_video_id = $this->getYoutubeVideoId($data['url']);
        if (!$youtube_video_id) {
            return response()->json([
                "message" => "رابط الفيديو غير صحيح"
            ], 400);
        }

        try {
            // $video_path = $this->uploadVideoServices->upload_video($request, 'courses_videos');
            // if (!$video_path) {
            //     // لم ينته رفع الفيديو بعد (أو فشل)
            //     return response()->json([
            //         'message' => 'تم رفع جزء من الفيديو، الرجاء إكمال الرفع'
            //     ]);
            // }

            $video = $course->videos()->create([
                'title' => $data['title'],
                'is_paid' => $data['is_paid'] == 'true' ? true : false,
                'url' => $youtube_video_id,
                'duration' => $data['duration'] ?? null,
                'path' => "",
                'playlist_id' => $data['playlist_id'] ?? null
            ]);

            return response()->json([
                "message" => "تم رفع الفيديو بنجاح ",
                "data" => $video,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }
    public function getYoutubeVideoId($url)
    {
        // كل أنواع الروابط الشائعة
        $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1]; // ID الفيديو
        }

        return null; // مو رابط يوتيوب
    }
    public function delete_video($id)
    {
        $video = Video::find($id);
        if (!$video) {
            return response()->json([
                "message" => "هذا الفيديو غير موجود"
            ], 404);
        }
        try {

            Log::info("video path: " . $video->path);
            $course = $video->course;

            if ($course->user_id != Auth::user()->id) {
                return response()->json([
                    "message" => "لايمكنك حذف هذا الفيديو"
                ], 403);
            }

            // $this->uploadVideoServices->delete_video(
            //     $video->course_id,
            //     $video->path
            // );

            $video->delete();
            return response()->json([
                "message" => "تم حذف الفيديو بنجاح"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    // generate new 10 codes for course

    public function get_course_code($id)
    {
        $course = Course::find($id);
        //check if theres code that not deleted for one min and deleted
        // $course->codes()
        //     ->where('created_at', '<=', now()->subHour())
        //     ->delete();


        $randomCode = \Illuminate\Support\Str::random(16);
        $code =   $course->codes()->create([
            'code' => $randomCode,
        ]);
        return response()->json([
            "message" => "تم انشاء الاكواد بنجاح",
            "data" => $code,
        ]);
    }
    public function add_codes($id)
    {
        $course = Course::find($id);
        if (!$course) {
            return response()->json([
                "message" => "هذا الدورة غير موجود"
            ], 404);
        }
        try {
            if ($course->codes()->count() >= 10) {
                return response()->json([
                    "message" => "لا يمكنك اضافة اكثر من 10 كود"
                ], 404);
            }
            // get course codes count
            $existingCount = $course->codes()->count();
            $needed = max(0, 10 - $existingCount);

            for ($i = 0; $i < $needed; $i++) {
                do {
                    $randomCode = \Illuminate\Support\Str::random(16);
                } while (\App\Models\Code::where('code', $randomCode)->exists());

                $course->codes()->create([
                    'code' => $randomCode,
                ]);
            }
            $codes = $course->codes()->get();
            return response()->json([
                "message" => "تم انشاء الاكواد بنجاح",
                "data" => $codes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "message" => $e->getMessage()
            ], 500);
        }
    }

    public function clear_cache($courseId = null, $userId = null, $teacherId = null)
    {
        // مفاتيح ثابتة معروفة
        $keys = [
            'courses_all',
        ];

        if ($userId) {
            $keys[] = "user_{$userId}_subscribed_courses";
        }

        // إذا عندك ID المدرس
        if ($teacherId) {
            $keys[] = "teacher_{$teacherId}_courses";
        }

        // إذا عندك كورس محدد
        if ($courseId) {
            $keys[] = "course_{$courseId}";
        }

        // مسح كل المفاتيح
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
