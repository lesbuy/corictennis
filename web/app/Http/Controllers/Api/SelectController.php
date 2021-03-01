<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;                                  
use App\Models\Name;
use App;

class SelectController extends Controller
{
    //
	public function byname(Request $req, $lang, $gender, $str) {

		App::setLocale($lang);

		if ($gender == "atp") {
			$_gender = 1;
		} else if ($gender == "wta") {
			$_gender = 2;
		} else {
			return "[]";
		}

		if ($str == "-") {
			$ones = Name::where('gender', $_gender)->orderBy('priority')->orderBy('rank')->orderBy('highest')->limit(30)->get();
		} else {
			$regexp = "(^" . $str . ")|([ -]" . $str . ")";
			$ones = Name::where('gender', $_gender)->where('name', 'regexp', $regexp)->orderBy('priority')->orderBy('rank')->orderBy('highest')->limit(30)->get();
		}

		$ret = [];
		foreach ($ones as $one) {
			$pid = $one->pid;
			$key = join('_', [$gender, 'profile', $pid]);
			$res = Redis::hmget($key, "hs", "pt", 'l_' . $lang, 's_' . $lang, 'l_en', 's_en', 'rank_s', 'rank_s_hi', 'first', 'last');
			$long = $res[2];
			$short = $res[3];
			if (!$long) $long = $res[4];
			if (!$short) $short = $res[5];
			$rank = $res[6];
			$rank_hi = $res[7];
			$first = $res[8];
			$last = $res[9];

			$res1 = fetch_portrait($pid, $gender);
			$res2 = fetch_headshot($pid, $gender);

			$ret[] = [
				'pid' => $pid,
				'name' => $one->name,
				'shortname' => $res[5],
				'ioc' => $one->ioc,
				'hs' => $res2[1],
				'pt' => $res1[1],
				'has_hs' => $res2[0],
				'has_pt' => $res1[0],
				'rank' => $rank,
				'rank_highest' => $rank_hi,
				'long' => $long,
				'short' => $short,
				'first' => $first,
				'last' => $last,
				'hl' => 0,
			];
		}

		return json_encode($ret);
	}
}
