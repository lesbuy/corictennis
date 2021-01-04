<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TopPlayer;
use App;
use Config;

class TopPlayerController extends Controller
{
    //

	public function update_top_player() {

		$cmd = "cat " . Config::get('const.root') . "/atp/player_headshot " . Config::get('const.root') . "/wta/player_headshot";
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$head[$arr[0]] = $arr[2];
			}
		}

		$cmd = "cat " . Config::get('const.root') . "/atp/player_bio " . Config::get('const.root') . "/wta/player_bio | cut -f1,5,7,21,22,30,31,39,40 | awk -F\"\\t\" '$4 <= 100 || $5 <= 20 || $6 <= 30 || $7 <= 10 || ($2 == \"CHN\" && $5 <= 100)'";
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$id = $arr[0];
				if (preg_match('/^[A-Z0-9]{4}$/', $id)) $sex = 1; else $sex = 2;
				$ioc = $arr[1];
				$dob = $arr[2];
				if ($dob == "0000-00-00" || $dob == "1753-01-01") $dob = null;
				$rank_s = $arr[3];
				$rank_s_ch = $arr[4];
				$rank_d = $arr[5];
				$rank_d_ch = $arr[6];
				$first = $arr[7];
				$last = $arr[8];
				$headshot = @$head[$id];

				$one = TopPlayer::updateOrCreate(['id' => $id], [
					'sex' => $sex, 
					'ioc' => $ioc, 
					'dob' => $dob, 
					'first' => $first, 
					'last' => $last, 
					'rank_s' => $rank_s, 
					'rank_d' => $rank_d, 
					'rank_s_ch' => $rank_s_ch, 
					'rank_d_ch' => $rank_d_ch, 
					'headshot' => $headshot, 
					'valid' => 1,
				]);

			}
		} 

	}
}
