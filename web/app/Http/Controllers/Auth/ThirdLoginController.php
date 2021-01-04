<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Socialite;
use Config;

class ThirdLoginController extends Controller
{
    //
	public function index(Request $req, $loginmethod) {
		$user = Socialite::driver($loginmethod)->user();
		echo "token = " . $user->token . "<br>";
		echo "id = " . $user->getId() . "<br>";
		echo "nickname = " . $user->getNickname() . "<br>";
		echo "name = " . $user->getName() . "<br>";
		echo "email = " . $user->getEmail() . "<br>";
		echo "avatar = " . $user->getBigAvatar() . "<br>";
		echo "usertype = " . $user->getType() . "<br>";
		echo "user = \n";
		print_r($user->getRaw());
		#, JSON_UNESCAPED_UNICODE) . 
		echo "<br>";
		var_dump($user);
	}
}
