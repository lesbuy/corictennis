<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;
use DB;

class DetailController extends Controller
{
    //
	public function index($lang, $date, $eid, $matchid) {
		App::setLocale($lang);

		$file = join('/', [Config::get('const.root'), 'share', '*completed', $date]);
		$idx_matchid = array_search('matchid', Config::get('const.schema_completed')) + 1;
		$idx_eid = array_search('eid', Config::get('const.schema_completed')) + 1;
		$cmd = "cat $file | awk -F\"\\t\" '$$idx_eid == \"$eid\" && $$idx_matchid ~ /^$matchid/' | head -1";
		unset($r); exec($cmd, $r);

		$ret = [
			'profile' => [],
		];

		if ($r) {
			$kvmap = explode("\t", $r[0]);
			$p1id = $kvmap[array_search('p1id', Config::get('const.schema_completed'))];
			$p2id = $kvmap[array_search('p2id', Config::get('const.schema_completed'))];

			$p1 = explode('/', $p1id);
			$p2 = explode('/', $p2id);

			if (count($p1) == count($p2) && count($p1) == 1) {
				$is_double = 0;
			} else {
				$is_double = 1;
			}

			self::get_profile($ret, $p1, $p2);
		}
	}

	private function get_profile(&$ret, $p1, $p2) {

		$atp_ids = [];
		$wta_ids = [];
		foreach (array_merge($p1, $p2) as $p) {
			if (preg_match('/^[A-Za-z][A-Z0-9a-z]{3}$/', $p)) {
				$atp_ids[] = $p;
			} else if (preg_match('/^[1-9][0-9]{4,5}$/', $p)) {
				$wta_ids[] = $p;
			}
		}
		
		foreach (['atp', 'wta'] as $gender) {
			if (count(${$gender . '_ids'}) == 0) continue;

			// 从profile表取资料
			$tbname = 'profile_' . $gender;
			$rows = DB::table($tbname)->whereIn('longid', ${$gender . '_ids'})->get();
			foreach ($rows as $row) {
				$pid = $row->longid;

				$first = $row->first_name;
				$last = $row->last_name;
				$ioc = $row->nation3;
				$displayname = rename2short($first, $last, $ioc);
				$birthday = $row->birthday;
				$birthplace = $row->birthplace;
				$residence = $row->residence;
				$hand = $row->hand;
				$backhand = $row->backhand;
				$height = ['metric' => $row->height, 'imperial' => $row->height_bri];
				$weight = ['metric' => $row->weight, 'imperial' => $row->weight_bri];
				$pro = $row->proyear;
				$prize_c = $row->prize_c;
				$rank_s = $row->rank_s;
				$title_s_c = $row->title_s_c;
			}
		}
	}
}
