<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Config;
use App;

class CorporateController extends Controller
{
    //

	public function PreWeekPredict ($gender, $event, $year) {

		App::setLocale('zh');

		$file = join("/", [Config::get('const.root'), 'store', 'calendar', $year, "*"]);
		$cmd = "awk -F\"\\t\" '$2 == \"$event\"' $file | head -1";
		unset($r); exec($cmd, $r);

		if (!$r) return null;

		$arr = explode("\t", trim($r[0]));

		$start_time = $arr[6];
		$sfc = $arr[8];
		$sfc = str_replace("(I)", "", $sfc);

		$tour = $arr[9];
		$start_date = $arr[5];

		$file = join("/", [Config::get('const.root'), 'store', 'calendar', $year, "GS"]);
		$cmd = "awk -F\"\\t\" '$7 > $start_time' $file | head -1";
		unset($r); exec($cmd, $r);

		if ($r) {
			$arr = explode("\t", trim($r[0]));
			$next_gs_start_time = $arr[6];
			$next_gs = $arr[7];
			$next_gs_eid = $arr[1];
		} else {
			$next_gs = $next_gs_eid = $next_gs_start_time = "";
		}

//		echo join("\t", [$start_time, $sfc, $next_gs_eid, $next_gs_start_time, $next_gs]) . "\n";

		// 读签表
		$file = join("/", [Config::get('const.root'), 'store', 'draw', $year, $event]);
		$cmd = "awk -F\"\\t\" '$1 == \"" . ($gender == "atp" ? "M" : "W") . "S\"' $file";
		unset($r); exec($cmd, $r);
		if (!$r) return null;

		$info = [];

		$match_count = (count($r) + 1) / 2;
		foreach ($r as $row) {
			unset($kvmap); $kvmap = [];
			$arr = explode("\t", trim($row));
			foreach ($arr as $k => $v) {
				$kvmap[Config::get('const.schema_drawsheet.' . $k)] = $v;
			}
			$matchid = intval($kvmap['id']);
			$r1 = floor(($matchid % 1000) / 100);
			$order = $matchid % 100;
			if ($r1 != 1) break;

			if ($order / $match_count <= 0.25) {
				$section = 1;
			} else if ($order / $match_count <= 0.5) {
				$section = 2;
			} else if ($order / $match_count <= 0.75) {
				$section = 3;
			} else {
				$section = 4;
			}

			$kvmap['P1A'] = substr($kvmap['P1A'], 3);
			$kvmap['P2A'] = substr($kvmap['P2A'], 3);
			if ($kvmap['P1A'] != "") $info[$kvmap['P1A']] = [replace_letters($kvmap['P1AFirst']), replace_letters($kvmap['P1ALast']), $kvmap['P1ANation'], $section, ($order - 1) * 2 + 1];
			if ($kvmap['P2A'] != "") $info[$kvmap['P2A']] = [replace_letters($kvmap['P2AFirst']), replace_letters($kvmap['P2ALast']), $kvmap['P2ANation'], $section, ($order - 1) * 2 + 2];

			
		}

//		echo json_encode($info);

		// 上年年终排名
		$file = join("/", [Config::get('const.root'), $gender, 'player_ye_ranks', ($year - 1) . "-12-31"]);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$ye_point[$arr[0]] = $arr[3];
		}
		fclose($fp);

		// 排名
		$offi_point = [];
		$offi_rank = [];
		$rank_point = [];
		$file = join("/", [Config::get('const.root'), $gender, 'player_all_ranks', date('Y-m-d', strtotime($start_time))]);
		if (!file_exists($file)) $file = join("/", [Config::get('const.root'), $gender, 'player_rank']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$offi_point[$arr[0]] = $arr[3];
			$offi_rank[$arr[0]] = $arr[2];
			if (!isset($rank_point[$arr[2]])) $rank_point[$arr[2]] = $arr[3];
		}
		fclose($fp);

		// 参赛计划
		$plan = [];
		$file = join("/", [Config::get('const.root'), $gender, 'player_plan_new']);
		$fp = fopen($file, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$id = $arr[1];
			$plan[$id] = [
				isset($arr[7]) ? $arr[7] : "",
				isset($arr[9]) ? $arr[9] : "",
				isset($arr[11]) ? $arr[11] : "",
				isset($arr[13]) ? $arr[13] : "",
				isset($arr[15]) ? $arr[15] : "",
				isset($arr[17]) ? $arr[17] : "",
			];
		}
		fclose($fp);
		
		$last_year = date("Ymd", $start_time - 364 * 86400);
		$last_year_gs = date("Ymd", $next_gs_start_time - 364 * 86400);

		// 去年本站
		$drop = [];
		$baofen = [];
		$gs_baofen = [];
		$same_sfc = [];

		$cmd = "cat " . join("/", [Config::get('const.root'), $gender, "points_s_this"]) . "  " . join("/", [Config::get('const.root'), $gender, "points_s_year"]) . "  " . join("/", [Config::get('const.root'), $gender, "points_s_drop"]);
		unset($r); exec($cmd, $r);

		if ($r) {
			foreach ($r as $line) {
				unset($kvmap); $kvmap = [];
				$arr = explode("\t", $line);
				foreach ($arr as $k => $v) {
					$kvmap[Config::get('const.schema_points.' . $k)] = $v;
				}
				$id = $kvmap['id'];
				$point = $kvmap['point'];
				$final_round = $kvmap['final_round'];
				$level = $kvmap['level'];
				$city = $kvmap['city'];
				$date = $kvmap['date'];

				if ($date == $last_year) {
					$drop[$id] = [$point, $city, $final_round];
				} else if ($date == $last_year_gs && $level == "GS") {
					$gs_baofen[$id] = [$point, $city, $final_round];
				} else if ($date > $last_year && $date < $last_year_gs) {
					if (!isset($baofen[$id])) $baofen[$id] = $point; else $baofen[$id] += $point;
				}

				if ($year == $kvmap['year'] && date('Ymd', $start_time) != $kvmap['date'] && $sfc == str_replace("(I)", "", @$kvmap['surface'])) {
					$same_sfc[$id][] = [$point, $city, $final_round, $level];
				}
			}
		}

		$data = [];
		foreach ($info as $pid => $v) {
			$_offi_rank = isset($offi_rank[$pid]) ? $offi_rank[$pid] : 9999;
			$_section = $v[3];
			$_name = rename2short($v[0], $v[1], $v[2]);
			$_engname = $v[0] . " " . $v[1];
			$_offi_point = isset($offi_point[$pid]) ? $offi_point[$pid] : 0;
			$_ye_point = isset($ye_point[$pid]) ? $ye_point[$pid] : 0;
			$_target_rank = $_offi_rank > 128 ? 128 : ($_offi_rank > 32 ? 32 : ($_offi_rank > 20 ? 20 : ($_offi_rank > 8 ? 8 : ($_offi_rank >= 1 ? 1 : ""))));
			$_target_point = isset($rank_point[$_target_rank]) ? $rank_point[$_target_rank] : 0;
			$_this_week_baofen = isset($drop[$pid]) ? $drop[$pid][0] : 0;
			$_this_week_round = isset($drop[$pid]) ? $drop[$pid][2] . " (" . ($drop[$pid][1]) . ")" : "";
			$_baofen = isset($baofen[$pid]) ? $baofen[$pid] : 0;
			$_gs_point = isset($gs_baofen[$pid]) ? $gs_baofen[$pid][0] : 0;
			$_gs_round = isset($gs_baofen[$pid]) ? $gs_baofen[$pid][2] : "";
			$_same_sfc = isset($same_sfc[$pid]) ? join("<br>", array_map(function ($d) {return $d[1] . "(" . $d[3] . ") " . $d[2];}, $same_sfc[$pid])) : "";
			$_plan = isset($plan[$pid]) ? join(", ", $plan[$pid]) : "";

			$data[$pid] = [
				'offi_rank' => $_offi_rank,
				'section' => $_section,
				'name' => $_name,
				'engname' => $_engname,
				'offi_point' => $_offi_point,
				'ye_point' => $_ye_point,
				'ytd_point_change' => $_offi_point - $_ye_point,
				'target_rank' => $_target_rank,
				'target_point' => $_target_point,
				'point_need' => $_target_point - $_offi_point,
				'this_week_defend' => $_this_week_baofen,
				'last_year_record' => $_this_week_round,
				'current_defend' => $_baofen,
				'gs_defend' => $_gs_point,
				'gs_record' => $_gs_round,
				'same_sfc_record' => $_same_sfc,
				'next_plan' => $_plan,
			];
		}

		uasort($data, function ($a, $b) {
			return $a['offi_rank'] > $b['offi_rank'];
		});

		$ret = [
			'data' => $data,
			'tour' => $tour,
			'start_date' => $start_date,
			'next_gs' => $next_gs,
		];

		return view('admin.pre-week-predict', ['ret' => $ret]);
	}
}
