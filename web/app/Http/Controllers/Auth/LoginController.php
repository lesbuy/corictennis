<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectPath = '/zh';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
//        $this->middleware('guest')->except('logout');
    }

		public function username()
		{
			return 'name';
		}

    /**
     * 重定向用户到 认证页。
     *
     * @return Response
     */
    public function redirectToProvider($method)
    {
        return Socialite::driver($method)->redirect();
    }

    /**
     * 得到用户信息
     *
     * @return Response
     */
    public function handleProviderCallback(Request $req, $method)
    {

		$loginedId = Auth::id();
		if ($loginedId) {
			Auth::logout();
		}

        $user = Socialite::driver($method)->user();
//		print_r($user);
//		return;

		if (!$user) {
			if ($req->headers->get('referer')) {
				return redirect()->back();
			} else {
				return redirect()->intended($this->redirectPath);
			}
		}

		if ($user->getRedirect()) {
			$this->redirectPath = $user->getRedirect();
		}

		if ($user->getType() == 0) {
			$existedUser = \App\User::where('name', '=', $user->getType() . '_' . $user->getOpenid())->first();
		} else {
			$existedUser = \App\User::where('name', '=', $user->getType() . '_' . $user->getId())->first();
		}
		$remember = true;

		if (!$existedUser) {
			$newUser = new User;
			$newUser->method = $user->getType();

			if ($newUser->method == 0) {
				$newUser->name = $user->getType() . '_' . $user->getOpenid();
			} else {
				$newUser->name = $user->getType() . '_' . $user->getId();
			}
			$newUser->uid = $user->getId();
			$newUser->oriname = $user->getName();
			$newUser->password = bcrypt($newUser->oriname);
			$newUser->gender = $user->getGender();
			$newUser->birth = $user->birth;
			$newUser->location = $user->location;
			$newUser->detail = $user->detail;
			$newUser->url = $user->profileUrl;
			$newUser->ip = getIP();
			$newUser->lastIp = getIP();
			$newUser->avatar = $user->getAvatar();
			$newUser->bigavatar = $user->getBigAvatar();
			$newUser->openid = $user->getOpenid();
			$newUser->unionid = $user->getUnionid();
			$newUser->save();
			echo "new id = " . $newUser->id . "<br>";

			Auth::login($newUser, $remember);
			if (Auth::check()) {
				echo "注册并登录成功<br>";
				if ($req->headers->get('referer')) {
					if (strpos($req->headers->get('referer'), "referer=") !== false) {
						if ($query_param = parse_url($req->headers->get('referer'))['query']) {
							parse_str($query_param, $params);
							if (isset($params['referer'])) {
								$this->redirectPath = $params['referer'];
							}
						}
					} else {
						$this->redirectPath = $req->headers->get('referer');
					}
				}
				return redirect()->intended($this->redirectPath);
			}
		} else {

			if (!$existedUser->displayname) {

			}

			if ($existedUser->method != 0) {
				$existedUser->oriname = $user->getName();
			}
			$existedUser->password = bcrypt($existedUser->oriname);
			$existedUser->gender = $user->getGender();
			$existedUser->birth = $user->birth;
			$existedUser->location = $user->location;
			$existedUser->detail = $user->detail;
			$existedUser->url = $user->profileUrl;
			$existedUser->lastIp = getIP();
			$existedUser->unionid = $user->getUnionid();
			if ($user->getAvatar()) $existedUser->avatar = $user->getAvatar();
			if ($user->getBigAvatar()) $existedUser->bigavatar = $user->getBigAvatar();
			$existedUser->save();
			echo "existed id = " . $existedUser->id . "<br>";

			Auth::login($existedUser, $remember);
			if (Auth::check()) {
				if ($req->headers->get('referer')) {
					if (strpos($req->headers->get('referer'), "referer=") !== false) {
						if ($query_param = parse_url($req->headers->get('referer'))['query']) {
							parse_str($query_param, $params);
							if (isset($params['referer'])) {
								$this->redirectPath = $params['referer'];
							}
						}
					} else {
						$this->redirectPath = $req->headers->get('referer');
					}
				}
				return redirect()->intended($this->redirectPath);
			}
		}
    }

	public function special(Request $req, $id) {

		$loginedId = Auth::id();
		if ($loginedId) {
			echo "id = " . $loginedId . " has logined.<br>";
			Auth::logout();
		}

		Auth::loginUsingId($id, false);
//		if (Auth::check()) {
			if ($req->headers->get('referer')) {
				return redirect()->back();
			} else {
				return redirect()->intended($this->redirectPath);
			}
//		}
	}
}
