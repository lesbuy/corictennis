<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App\Models\Name;

class NameController extends Controller
{
    //
	public function update_name() {

		$tic = time();
		$count = 0;

		foreach ([1, 2] as $gender) {
			$all = Redis::keys(($gender == 1 ? 'atp' : 'wta') . '_profile_*');
			foreach ($all as $p) {
				$info = Redis::hmget($p, 'l_en', 'first', 'last', 'ioc', 'rank_s', 'rank_d', 'rank_s_hi', 'rank_d_hi');
				
				$pid = preg_replace('/^.*_/', '', $p);
				$name = $info[0];
				$first = $info[1];
				$last = $info[2];
				$ioc = preg_match('/^[A-Z]{3}$/', $info[3]) ? $info[3] : "";
				$rank_s = $info[4] == 9999 || $info[4] == 0 || $info[4] == "-" || $info[4] == "N/A" || $info[4] == "" ? 9999 : intval($info[4]);
				$rank_d = $info[5] == 9999 || $info[5] == 0 || $info[5] == "-" || $info[5] == "N/A" || $info[5] == "" ? 9999 : intval($info[5]);
				$rank_s_hi = $info[6] == 9999 || $info[6] == 0 || $info[6] == "-" || $info[6] == "N/A" || $info[6] == "" ? 9999 : intval($info[6]);
				$rank_d_hi = $info[7] == 9999 || $info[7] == 0 || $info[7] == "-" || $info[7] == "N/A" || $info[7] == "" ? 9999 : intval($info[7]);

				$priority = 2;
				if ($rank_s_hi <= 10 || $rank_s <= 100) $priority = 1;
				if ($rank_d_hi <= 3 || $rank_d <= 30) $priority = 1;

				Name::updateOrCreate(
					['pid' => $pid],
					[
						'name' => $name,
						'highest' => $rank_s_hi,
						'rank' => $rank_s,
						'gender' => $gender,
						'priority' => $priority,
						'first' => $first,
						'last' => $last,
						'ioc' => $ioc
					]
				);
				++$count;
			}
		}
		$toc = time();

		echo "处理了" . $count . "条数据，用时" . ($toc - $tic) . "秒";
	}
}
