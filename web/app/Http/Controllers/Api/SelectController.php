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
			$hs = Redis::hget($key, 'hs');
			$pt = Redis::hget($key, 'pt');
			$long = Redis::hget($key, 'l_' . $lang);
			$short = Redis::hget($key, 's_' . $lang);
			if (!$long) $long = Redis::hget($key, 'l_en');
			if (!$short) $short = Redis::hget($key, 'l_en');
			$has_hs = 1;
			$has_pt = 1;
			$rank = Redis::hget($key, 'rank_s');
			if (!$hs) {$hs = $gender . "player.jpg"; $has_hs = 0;}
			if (!$pt) {$pt = $gender == "atp" ? "gladiator-ghost.png" : "wtaplayer.png"; $has_pt = 0;}
			$hs = join('/', ['images', join('_', [$gender, 'headshot']), $hs]);
			$pt = join('/', ['images', join('_', [$gender, 'portrait']), $pt]);
			$ret[] = [
				'pid' => $pid,
				'name' => $one->name,
				'ioc' => $one->ioc,
				'hs' => $hs,
				'pt' => $pt,
				'has_hs' => $has_hs,
				'has_pt' => $has_pt,
				'rank' => $rank,
				'long' => $long,
				'short' => $short,
				'hl' => 0,
			];
		}

		return json_encode($ret);
	}
}
