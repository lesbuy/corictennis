<?php

namespace App\Http\Controllers\History;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use App;

class TopNController extends Controller
{
    //
	public function index($lang) {
		App::setLocale($lang);
		return view('topn.index', [
			'pageTitle' => __('frame.menu.topN'),
			'title' => __('frame.menu.topN'),
			'pagetype1' => 'topn',
			'pagetype2' => 'index',
		]);
	}

	public function query(Request $req, $lang) {
		App::setLocale($lang);
		$ret = [];

		$status = $req->input('status', 'wrongP1');

		if ($status != 'ok') {

			$ret['status'] = -1;

			$ret['errmsg'] = __('h2h.warning.' . $status);

		} else {

			$ret['status'] = -1;
			$ret['errmsg'] = __('h2h.warning.noResult');

			$sd = $req->input('sd', 's');
			$type = $req->input('type', 'atp');
			$id = $req->input('p1id');
			$topN = $req->input('p2id');

			$file = join('/', [env('ROOT'), "data", "rank", $type, $sd, "history", "*"]);

			$ret['ranks'] = [];

			$cmd = "grep '^$id\t' $file";
			unset($r); exec($cmd, $r);
			
			$begin = false;
			$pre_date = 0;
			$start_date = 0;
			$end_date = 0;
			$total_weeks = 0;
			$weeks = 0;

			if ($r) {
				
				for ($i = 0; $i < count($r); ++$i){
					$arr = explode("\t", $r[$i]);
					$name = $arr[1];
					$rank = $arr[2];
					$date = date('Y-m-d', strtotime($arr[5]));
					if (($rank > $topN || strtotime($date) - strtotime($pre_date) > 365 * 86400) && $begin){  // 排名超了，或者200天没排名，并且之前在查询排名范围内，则关闭查询范围，记下结束时间
						$begin = false;
						if ((strtotime($date) - strtotime($pre_date) > 365 * 86400 && strtotime($date) - strtotime("1990-01-01") < 0)
							|| (strtotime($date) - strtotime($pre_date) > 20 * 86400 && strtotime($date) - strtotime("1990-01-01") >= 0)){
							$end_date = date('Y-m-d', strtotime($pre_date . " +6 day"));
						} else {
							$end_date = date('Y-m-d', strtotime($date . " -1 day"));
						}
						if ($start_date){
							$weeks = round((strtotime($end_date) - strtotime($start_date)) / 86400 / 7);
							// 跨越2020/3/23-2020/8 的区段特殊处理，去掉22或20周
							if (strtotime($start_date) <= strtotime("2020-03-16") && strtotime($end_date) >= strtotime("2020-08-09") && $type == "wta") $weeks -= 20;
							if (strtotime($start_date) <= strtotime("2020-03-16") && strtotime($end_date) >= strtotime("2020-08-23") && $type == "atp") $weeks -= 22;
							$total_weeks += $weeks;
							$ret['ranks'][] = [$start_date, $end_date, $weeks];
						}
					}

					if ($rank <= $topN && !$begin){  // 如果排名没超，并且之前不在查询范围内，则开启查询范围，记下起始时间
						$begin = true;
						$start_date = $date;
					}
					$pre_date = $date;
				}

				if (time(NULL) - strtotime($pre_date) > 18 * 86400 && $begin){  // 如果18天还没有排名，并且之前在查询范围内，认为排名已经被取消，则认为最后一次出现的时间为最后一周
					$end_date = date('Y-m-d', strtotime($pre_date . " + 6 days"));
					$weeks = round((strtotime($end_date) - strtotime($start_date)) / 86400 / 7);
					// 跨越2020/3/23-2020/8 的区段特殊处理，去掉22或20周
					if (strtotime($start_date) <= strtotime("2020-03-16") && strtotime($end_date) >= strtotime("2020-08-09") && $type == "wta") $weeks -= 20;
					if (strtotime($start_date) <= strtotime("2020-03-16") && strtotime($end_date) >= strtotime("2020-08-23") && $type == "atp") $weeks -= 22;
					$total_weeks += $weeks;
					$ret['ranks'][] = [$start_date, $end_date, $weeks];
				} else if (time(NULL) - strtotime($pre_date) <= 18 * 86400 && $begin){ // 小于18天认为还在持续
					$end_date = date('Y-m-d', strtotime("next Monday") - 86400);
					$weeks = round((strtotime($end_date) - strtotime($start_date)) / 86400 / 7);
					// 跨越2020/3/23-2020/8 的区段特殊处理，去掉22或20周
					if (strtotime($start_date) <= strtotime("2020-03-16") && strtotime($end_date) >= strtotime("2020-08-09") && $type == "wta") $weeks -= 20;
					if (strtotime($start_date) <= strtotime("2020-03-16") && strtotime($end_date) >= strtotime("2020-08-23") && $type == "atp") $weeks -= 22;
					$total_weeks += $weeks;
					$end_date .= "(" . __('h2h.warning.notEnd') . ")";
					$ret['ranks'][] = [$start_date, $end_date, $weeks];
				}

				if ($total_weeks > 0) {
					$ret['status'] = 0;
					$ret['win'] = $total_weeks;

					$key1 = join('_', [$type, 'profile', $id]);
					$res = Redis::hmget($key1, 'first', 'last', 'ioc', 'hs');

					if ($res) {
						$first = $res[0];
						$last = $res[1];
						$ioc = $res[2];
						$hs = $res[3];
						$ret['p1name'] = translate2long($id, $first, $last, $ioc);
						$ret['p1head'] = get_headshot($type, $hs);
					} else {
						$ret['p1head'] = get_headshot($type, "");
						$ret['p1name'] = "";
					}
				}

			}

		}

		$ret['n'] = $topN;

//		echo json_encode($ret);
		return view('topn.query', ['ret' => $ret]);

	}

}
