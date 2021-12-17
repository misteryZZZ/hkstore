<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\BaseController;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPasswordController extends BaseController
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;



    public function __construct()
    {
        parent::__construct();
    }



    public function showLinkRequestForm()
    {
        $this->meta_data->url = route('password.request');
        $this->meta_data->title = __('Reset password');

        return view('auth.passwords.email', ['meta_data' => $this->meta_data]);
    }
}
