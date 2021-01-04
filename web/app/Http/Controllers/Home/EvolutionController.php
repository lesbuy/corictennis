<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;

class EvolutionController extends Controller
{
    //

	public function index($lang) {
		App::setLocale($lang);

		return view('evolv.index', [
			'pageTitle' => __('frame.menu.rankEvolution'),
			'title' => __('frame.menu.rankEvolution'),
			'pagetype1' => 'evolv',
		]);

	}

	public function content($lang, $gender, $topn, $start, $end, $freq) {

		App::setLocale($lang);

		$topn = $topn * 1.5;

		if ($end == "now") $end = "2018-12-31";
		$start = date('Y-m-d', strtotime($start));
		$end = date('Y-m-d', strtotime($end));

		if ($freq == "year-end") {
			$is_year_end = true;
			$start = date('Y-01-01', strtotime($start));
			$end = date('Y-12-31', strtotime($end));
		} else {
			$is_year_end = false;
		}

		$cmd = "cd " . join("/", [Config::get('const.root'), $gender]) . " && cut -f1,5,39,40 player_bio";
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $line) {
				$arr = explode("\t", $line);
				$name[$arr[0]] = rename2short(@$arr[2], @$arr[3], @$arr[1]);
				$ioc[$arr[0]] = @$arr[1];
			}
		}

		if (!$is_year_end) {
			$cmd = "cd " . join("/", [Config::get('const.root'), $gender, 'player_all_ranks']) . " && head -$topn `ls | awk -F\"\\t\" '$0 >= \"$start\" && $0 <= \"$end\"'` ";
		} else {
			$cmd = "cd " . join("/", [Config::get('const.root'), $gender, 'player_ye_ranks']) . " && head -$topn `ls | awk -F\"\\t\" '$0 >= \"$start\" && $0 <= \"$end\"'` ";
		}
		unset($r); exec($cmd, $r);

		$ret = ['data' => []];
		$ids = [];
		if ($r) {
			foreach ($r as $line) {
				if (trim($line) == "") continue;
				else if (preg_match('/^==/', $line)) {
					if (isset($data)) {
						$ret['data'][] = $data;
						unset($data);
					}
					$data = [];
					$date = date('Y-m-d', strtotime(trim(preg_replace('/[=<> ]/', "", $line))));
					$data[] = $date;
				} else {
					$arr = explode("\t", $line);
					$pname = $name[$arr[0]];
					$pioc = $ioc[$arr[0]];
					$point = $arr[3];
					if (!isset($data)) {
						$data = [""];
					}
					if (count($data) == 1) {
						if (!in_array($arr[0], $ids)) {
							$ids[] = $arr[0];
						}
					}
					$data[] = [$pname . "\1" . $pioc . "\1" . $arr[0], $point];
				}
			}
		}

		if (isset($data)) {
			$ret['data'][] = $data;
			unset($data);
		}

		$ret['head'] = get_patch_headshots($gender, $ids);

		$tmp_arr = get_patch_infos($gender, $ids);
		$ret['name'] = array_map(function ($d) {
			return rename2long($d[0], $d[1], $d[2]);
		}, $tmp_arr);

		$ret['flag'] = array_map(function ($d) {
			return str_replace("+", "%20", urlencode(get_flag($d[2])));
		}, $tmp_arr);

		$ret['birth'] = array_map(function ($d) {
			return $d[3];
		}, $tmp_arr);

		return json_encode($ret);
	}
}
