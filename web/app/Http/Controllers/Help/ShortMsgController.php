<?php

namespace App\Http\Controllers\Help;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ShortMsg;
use Auth;
use App;

class ShortMsgController extends Controller
{
    //
	public function save(Request $req, $lang) {

		$one = new ShortMsg;

		if (Auth::id()) {
			$userid = Auth::id();
			$username = Auth::user()->oriname;
			$one->read = false;
		} else {
			$userid = 0;
			$username = $req->input('username', '');
			$one->read = true;
		}

		$one->userid = $userid;
		$one->username = $username;
		$one->msg = $req->input('msg', '');
		$one->reply = null;
		$one->ip = getIP();
		$one->ua = $_SERVER['HTTP_USER_AGENT'];
		$one->code = $req->input('uuid', 0);
		$one->save();

		if ($userid == 0 && $one->ip == "106.3.194.229" && $one->ua == "Mozilla/5.0 (iPhone; CPU iPhone OS 11_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.0 Mobile/15E148 Safari/604.1") {
			return __('help.msgboard.not_permitted_without_login');
		}
		return __('help.msgboard.submitted');
	}

	public function show($lang) {

		if (!Auth::check()) {
			return '';
		}

		$ones = ShortMsg::where(['userid' => Auth::id(), 'read' => false])->whereNotNull('reply')->orderBy('created_at', 'desc')->get();
		$msg = [];
		foreach ($ones as $one) {
			$msg[] = [$one->msg, $one->reply];
			$one->read = true;
			$one->save();
		}

		if (count($msg) < 1) {
			return '';
		} else {
			App::setLocale($lang);
			return view('help.msgshow', ['msg' => $msg]);
		}
	}
}
