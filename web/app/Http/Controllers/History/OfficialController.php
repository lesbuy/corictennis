<?php

namespace App\Http\Controllers\History;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;

class OfficialController extends Controller
{
    //
	public function index($lang) {
		App::setLocale($lang);
		return view('official.index', [
			'pageTitle' => __('frame.menu.officialRank'),
			'title' => __('frame.menu.officialRank'),
			'pagetype1' => 'official',
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

			$ret['status'] = 0;
			$sd = $req->input('sd', 's');
			$type = $req->input('type', 'atp');
			$date = $req->input('date');
			$ret['ranks'] = [];
			$has_result = false;
			$d = $date;
			$file = '';
			for ($i = 0; $i < 20; ++$i) {
				$d = date('Y-m-d', strtotime($date . " -$i days"));
				$file = join("/", [env('ROOT'), "data", "rank", $type, $sd, "history", $d]);
				if (file_exists($file)) {
					$has_result = true;
					break;
				}
			}

			if ($has_result) {

				$cmd = "cut -f1,3,5,7 " . join("/", [env('ROOT'), $type, 'player_bio']);
				unset($r); exec($cmd, $r);

				if ($r) {
					foreach ($r as $row) {
						$arr = explode("\t", $row);
						$name[$arr[0]] = $arr[1];
						$nation[$arr[0]] = $arr[2];
						$birth[$arr[0]] = $arr[3];
					}
				}

				$fp = fopen($file, "r");
				if ($fp) {
					$current_unix = strtotime($date);
					$current_year = date('Y', $current_unix);
					$current_month = date('m', $current_unix);
					$current_day = date('d', $current_unix);

					while ($line = trim(fgets($fp))) {
						$arr = explode("\t", $line);
						$id = $arr[0];
						$rank = $arr[2];
						$point = $arr[3];
						$tours = $arr[4];
						$ioc = @$nation[$id];
						$player = @$name[$id];

						if (isset($birth[$id]) && $birth[$id] != "") {
							$unixtime = strtotime($birth[$id]);
							$year = date('Y', $unixtime);
							$month = date('m', $unixtime);
							$day = date('d', $unixtime);
							$use_thisyear = true;
							if (strtotime("$current_year-$month-$day") > $current_unix) {
								$use_thisyear = false;
							};
							if ($use_thisyear) {
								$age_year = $current_year - $year;
								$year = $current_year;
							} else {
								$age_year = $current_year - 1 - $year;
								$year = $current_year - 1;
							}
							if ($month == 2 && $day == 29) {
								if (strtotime("$year-2-29") == strtotime("$year-3-1")) {
									$day = 28;
								}
							}
							$age_day = ($current_unix - strtotime("$year-$month-$day")) / 86400;
						} else {
							$age_year = $age_day = 0;
						}

						$ret['ranks'][] = [$id, $player, $rank, $point, $tours, $ioc, $age_year, $age_day];
					}
				}
				fclose($fp);
			}

		}

//		echo json_encode($ret);
		return view('official.query', ['ret' => $ret]);
	}
}
