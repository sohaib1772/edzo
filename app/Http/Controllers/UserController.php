<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\UserServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as FacadesRequest;

class UserController extends Controller
{
    protected  UserServices $userServices;

    public function __construct(UserServices $userServices)
    {
        $this->userServices = $userServices;
    }
    //
    public function get_user(Request $request)
    {
        return $this->userServices->getUser();
    }
    public function get_user_by_name(Request $request)
    {
        return $this->userServices->get_user_by_name($request);
    }
    public function set_user_role(Request $request)
    {
        return $this->userServices->set_user_role($request);
    

    }
    public function get_teachers(){
        return $this->userServices->get_teachers();
    }

    public function register(RegisterRequest $request)
    {
        return $this->userServices->register($request);
    }

    public function verifyEmail(Request $request){
        return $this->userServices->verifyEmail($request);
    }
    public function resendEmail(Request $request){
        return $this->userServices->resendEmail($request);
    }

    public function forgotPassword(Request $request)
    {
        return $this->userServices->forgotPassword($request);
    }
    public function resend_forgot_password_email(Request $request){
        return $this->userServices->resend_forgot_password_email($request);
    }
    public function resetPassword(Request $request)
    {
        return $this->userServices->resetPassword($request);
    }

    public function change_uid_request(LoginRequest $request){
        return $this->userServices->change_uid_request($request);
    }
    public function login(LoginRequest $request)
    {
        return $this->userServices->login($request);
    }
    public function logout(Request $request)
    {
        return $this->userServices->logout($request);
    }
    public function delete_user(Request $request){
        return $this->userServices->delete_user($request);
    }
}
