<?php

namespace App\Http\Controllers\Rank;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;

class CustomController extends Controller
{
    //
	protected $mandatory_points;
	protected $yec_points;
	protected $p5_points;
	protected $other_points;
	protected $ceil;
	protected $top20_players;
	protected $all_results;

	public function index($lang) {

		App::setLocale($lang);

		$next_monday = date('Y-m-d', strtotime("next Monday"));

		return view('rank.custom', [
			'next_monday' => $next_monday,
			'title' => __('frame.menu.custom'),
			'pageTitle' => __('frame.menu.custom'),
			'pagetype1' => 'rank',
			'pagetype2' => 'custom', 
		]);

	}

	public function query(Request $req, $lang, $sex, $sd, $st, $et) {

		App::setLocale($lang);

		$this->ceil = 16;
		if ($sex == "atp") $this->ceil = 18;
		else if ($sex == "wta") {
			if ($sd == "s") $this->ceil = 16;
			else if ($sd == "d") $this->ceil = 11;
		}
		$this->top20_players = [];
		$this->all_results = [];

		if ($sex == "wta" && $sd == "s") {
			$cmd = "cat " . join("/", [Config::get('const.root'), $sex, "player_profile"]);
			unset($r);
			exec($cmd, $r);
			if ($r) {
				foreach ($r as $row) {
					$arr = explode("\t", $row);
					$this->top20_players[$arr[0]] = $arr[3];
				}
			}
		}

		$cmd = "head -200 " . join("/", [Config::get('const.root'), $sex, "player_rank" . ($sd == "s" ? "" : "_d")]) . " | cut -f1";
		unset($avail_players);
		exec($cmd, $avail_players);

		$cmd = "cd " . Config::get('const.root') . "/" . $sex . " && cat points_" . $sd . "_year points_" . $sd . "_this points_" . $sd . "_last | sort -t\"	\" -k1,1 -k3g,3";
		unset($lines);
		exec($cmd, $lines);

		$st = date('Ymd', strtotime($st));
		$et = date('Ymd', strtotime($et));
		$itf_st = date('Ymd', strtotime($st) - 7 * 86400);

		$preid = "0";
		$name = "";
		$this->mandatory_points = [];
		$this->yec_points = [];
		$this->p5_points = [];
		$this->other_points = [];

		foreach ($lines as $line) {
			$arr = explode("\t", $line);
			if (!in_array($arr[0], $avail_players)) continue;
			$prize = preg_replace('/^.*[\$€£]/', "", @$arr[14]);
			if (preg_match('/K$/', $prize)) {
				$prize = ($prize + 0) * 1000;
			}
			if ($arr[2] < $itf_st) continue;
			if ($arr[2] >= $itf_st && $arr[2] < $st) {
				if ($sex == "atp" && $arr[5] != "FU") continue;
				if ($sex == "wta" && ($arr[5] != "ITF" || $prize > 30000)) continue;
			}
			if ($arr[5] == "FC" || $arr[5] == "CD" || $arr[5] == "DC" || $arr[5] == "OL") continue;

			$id = $arr[0];
			if ($preid != $id && $preid != "0") {
				self::print_result($preid, $name);
				self::clean_result($this->mandatory_points);
				self::clean_result($this->yec_points);
				self::clean_result($this->p5_points);
				self::clean_result($this->other_points);
			}

			$name = $arr[1];
			$date = $arr[2];
			$tourid = $arr[4];
			$tourtype = $arr[5];
			$point = (int) $arr[7];
			if ($point == -1) $point = 10000;
			$round = $arr[10];
			$tour = translate_tour($arr[11]);

			if (preg_match('/^Q[-R1-9]/', $round)) {
				if ($point > 0) {
					$this->other_points[] = [$tour, $point];
				}
			} else {
				if ($tourtype == "GS") {
					if ($sex == "wta" && $sd == "d") {   //wta双打不强制
						$this->other_points[] = [$tour, $point];
					} else {
						$this->mandatory_points[] = [$tour, $point];
					}
				} else if ($tourtype == "PM") {
					if ($sex == "wta" && $sd == "d") {   //wta双打不强制
						$this->other_points[] = [$tour, $point];
					} else {
						$this->mandatory_points[] = [$tour, $point];
					}
				} else if ($arr[11] == "wta finals" || $tourid == "0605") {
					if ($sd == "s") {    //双打不额外计
						$this->yec_points[] = [$tour, $point];
					} else {
						$this->other_points[] = [$tour, $point];
					}
				} else if ($tourtype == "1000" && $tourid != "0410") {
					$this->mandatory_points[] = [$tour, $point];
				} else if ($tourtype == "P5" && isset($this->top20_players[$id])) {
					if ($sd == "s") {    //单打才计P5
						$this->p5_points[] = [$tour, $point];
					} else {
						$this->other_points[] = [$tour, $point];
					}
				} else {
					$this->other_points[] = [$tour, $point];
				}
			}
			
			$preid = $id;
			
		}

		self::print_result($preid, $name);

		usort($this->all_results, "self::arr_compare");
		$data = [];
		for ($i = 0; $i < count($this->all_results); ++$i) {
			$data[] = [
				$i + 1,
				$this->all_results[$i][0],
				$this->all_results[$i][1],
				$this->all_results[$i][2],
				$this->all_results[$i][3],
			];
		}

		$ret = [
			'data' => $data,
			'draw' => 1,
			'recordsFiltered' => count($data),
			'recordsTotal' => count($data),
		];
		echo json_encode($ret);

	}

	protected function arr_compare($x, $y) {
		if ($x[1] > $y[1]) return -1; 
		else if ($x[1] < $y[1]) return 1;
		else return 0;
	}

	protected function clean_result(&$points_arr) {
		$points_arr = [];
	}

	protected function print_result($preid, $name) {

		$quota = 0;
		$sum = 0;
		$avail_points = [];
		$alt_points = [];
		if (count($this->yec_points) > 0) {
			for ($i = 0; $i < count($this->yec_points); ++$i) {
				if ($this->yec_points[$i][1] == 10000) $this->yec_points[$i][1] = 0;
				$sum += $this->yec_points[$i][1];
				$avail_points[] = $this->yec_points[$i];
			}
		}
		if (count($this->mandatory_points) > 0) {
			for ($i = 0; $i < count($this->mandatory_points); ++$i) {
				if ($this->mandatory_points[$i][1] == 10000) $this->mandatory_points[$i][1] = 0;
				$sum += $this->mandatory_points[$i][1];
				$avail_points[] = $this->mandatory_points[$i];
				++$quota;
			}
		}

		if (count($this->p5_points) > 0) {
			usort($this->p5_points, "self::arr_compare");
			for ($i = 0; $i < min($this->top20_players[$preid], count($this->p5_points)); ++$i) {
				if ($this->p5_points[$i][1] == 10000) $this->p5_points[$i][1] = 0;
				$sum += $this->p5_points[$i][1];
				$avail_points[] = $this->p5_points[$i];
				++$quota;
			}
			for (; $i < count($this->p5_points); ++$i) {
				$this->other_points[] = $this->p5_points[$i];
			}
		}
		usort($this->other_points, "self::arr_compare");
		for ($i = 0; $i < count($this->other_points); ++$i) {
			if ($this->other_points[$i][1] == 10000) $this->other_points[$i][1] = 0;
			$sum += $this->other_points[$i][1];
			$avail_points[] = $this->other_points[$i];
			++$quota;
			if ($quota == $this->ceil) {
				break;
			}
		}
		for ($i = $i + 1; $i < count($this->other_points); ++$i) {
			$alt_points[] = $this->other_points[$i];
		}

		$this->all_results[] = [$name, $sum, json_encode($avail_points), json_encode($alt_points)];
	}

}
