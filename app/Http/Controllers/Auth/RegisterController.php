<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\BaseController;
use App\Providers\RouteServiceProvider;
use App\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\{ Hash, Validator };
use Illuminate\Support\Str;

class RegisterController extends BaseController
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;


    /**
    * Show the application registration form.
    *
    * @return \Illuminate\Http\Response
    */
    public function showRegistrationForm()
    {
        $this->meta_data->url = route('register');
        $this->meta_data->title = __('Register');

        return view('auth.register', ['meta_data' => $this->meta_data]);
    }

    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        $rules = [
            'name'      => ['required', 'string', 'max:25'],
            'email'     => ['required', 'string', 'email', 'max:50', 'unique:users'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            'firstname' => ['required', 'string', 'max:25'],
            'lastname'  => ['required', 'string', 'max:25']
        ];

        if(captcha_is_enabled('register'))
        {
          if(captcha_is('mewebstudio'))
          {
            $rules['captcha'] = 'required|captcha';
          }
          elseif(captcha_is('google'))
          {
            $rules['g-recaptcha-response'] = 'required';
          }
        }

        return Validator::make($data, $rules, [
            'g-recaptcha-response.required' => __('Please verify that you are not a robot.'),
            'captcha.required' => __('Please verify that you are not a robot.'),
            'captcha.captcha' => __('Wrong captcha, please try again.'),
        ]);
    }


    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'name'      => Str::slug($data['name']),
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'firstname' => $data['firstname'],
            'lastname'  => $data['lastname']
        ]);
    }
}
