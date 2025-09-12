<?php

use App\Http\Controllers\UploadCunkedController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

//for test




Route::group(["middleware" => ["throttle:20,1"]], function () {
    //auth
    Route::post("/register", [UserController::class, 'register']);
    Route::post("/login", [UserController::class, 'login']);
    Route::delete("/logout", [UserController::class, 'logout'])->middleware('auth:sanctum');

    //user
    Route::prefix("/user")->group(function () {
        Route::get("", [UserController::class, 'get_user'])->middleware('auth:sanctum', "role:student,teacher,admin,full");
        Route::post("/verify", [UserController::class, 'verifyEmail']);
        Route::post("/resend-verify", [UserController::class, 'resendEmail']);
        Route::post("/forgot-password", [UserController::class, 'forgotPassword']);
        Route::post("/resend-forgot-password", [UserController::class, 'resend_forgot_password_email']);
        Route::post("/reset-password", [UserController::class, 'resetPassword']);
        Route::post("/change-uid", [UserController::class, 'change_uid_request']);
    });
});


Route::group(["middleware" => ["auth:sanctum", "verified", "throttle:100,1"]], function () {

    Route::group(["middleware" => ["role:admin"]], function () {
        Route::get("/user/search", [\App\Http\Controllers\UserController::class, 'get_user_by_name']);
        Route::post("/user/set-role", [\App\Http\Controllers\UserController::class, 'set_user_role']);
        Route::get("/teachers", [\App\Http\Controllers\UserController::class, 'get_teachers']);
    });

    Route::group(["middleware" => ["role:teacher,admin"]], function () {
        //teacher info
        Route::prefix("/teacher-info")->group(function () {
            Route::get("", [\App\Http\Controllers\TeacherInfoController::class, 'get_teacher_info']);
            Route::put("", [\App\Http\Controllers\TeacherInfoController::class, 'update_teacher_info']);
            Route::post("", [\App\Http\Controllers\TeacherInfoController::class, 'add_teacher_info']);
        });


        //courses management
        Route::prefix("/courses")->group(function () {
            Route::post("", [\App\Http\Controllers\CoursesController::class, 'store']);
            Route::post("/videos", [\App\Http\Controllers\CoursesController::class, 'upload_video']);
            Route::delete("/{id}/videos", [\App\Http\Controllers\CoursesController::class, 'delete_video']);
            Route::get("/{id}/codes", action: [\App\Http\Controllers\CoursesController::class, 'get_codes']);
            Route::post("/{id}", [\App\Http\Controllers\CoursesController::class, 'update']);
            Route::delete("/{id}", [\App\Http\Controllers\CoursesController::class, 'destroy']);
            Route::get("/teacher", [\App\Http\Controllers\CoursesController::class, 'get_teacher_courses']);
            Route::post("/add-codes/{id}", [\App\Http\Controllers\CoursesController::class, 'add_codes']);
        });
    });

    //courses public
    Route::prefix("/courses")->group(function () {
        Route::get("", [\App\Http\Controllers\CoursesController::class, 'index']);
        Route::get("/search", [\App\Http\Controllers\CoursesController::class, 'get_by_title']);
        Route::get("/subscribed", [\App\Http\Controllers\CoursesController::class, 'get_my_subscribed_courses']);
        Route::get("/{id}/videos", [\App\Http\Controllers\CoursesController::class, 'get_course_videos']);
        Route::get("/{id}", [\App\Http\Controllers\CoursesController::class, 'get_by_id']);
        Route::get("/teacher/{id}", [\App\Http\Controllers\CoursesController::class, 'get_courses_by_teacher']);
    });

    Route::delete('/user', [UserController::class, 'delete_user']);


    //subscriptions
    Route::post("/subscriptions", [\App\Http\Controllers\SubscriptionController::class, 'add_subscription']);
 });

// Route::get('/courses/videos/{folder}/{course_id}/{filename}', [VideoController::class, 'stream']);

// Route::get('/api/stream-hls/{course_id}/{file}', [VideoController::class, 'streamHls']);

// Route::get('/stream-video-segment/{folder}/{courseId}/{filename}/{resolution}/{segment?}', [VideoController::class, 'streamSegment']);

// Route::get('/stream-proxy/{course}/{video_id}/{video}/{file}', [VideoController::class, 'stream2']);

Route::get('/get-video/{course_id}/{video_id}', [VideoController::class, 'getVideoUrl'])->middleware('auth:sanctum');

Route::get('/public/get-video/{course_id}/{video_id}', [VideoController::class, 'getPublicVideoUrl']);


//for guests
Route::group(["middleware" => ["throttle:50,1"]], function () {
    Route::prefix("/public/courses")->group(function () {
        Route::get("", [\App\Http\Controllers\CoursesController::class, 'index']);
        Route::get("/search", [\App\Http\Controllers\CoursesController::class, 'get_by_title']);
        Route::get("/{id}/videos", [\App\Http\Controllers\CoursesController::class, 'get_course_videos']);
        Route::get("/teacher/{id}", [\App\Http\Controllers\CoursesController::class, 'get_courses_by_teacher']);
    });
});
