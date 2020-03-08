<?php
if (!defined('ROOT')) {$dir_arr = explode('/', __DIR__); define('ROOT', join('/', [$dir_arr[0], $dir_arr[1], $dir_arr[2]]));} require_once(ROOT . '/global.php'); require_once(APP . '/conf/func.php'); 

$schema = require_once(APP . '/conf/schema.php');

class Calc {

	protected $gender;
	protected $sd;
	protected $period;
	protected $max_valid;

	protected $pre_pid = null;
	protected $bonus = [];
	protected $mandantory = [];
	protected $premier5 = [];
	protected $optional = [];

	protected $win = 0;
	protected $loss = 0;
	protected $streak = 0;

	protected $result = [];

	protected $sm = []; // schema_map

	protected $redis = null;

	public function __construct($gender = 'atp', $sd = 's', $period = 'year') {
		global $schema;

		$this->gender = $gender;
		$this->sd = $sd;
		$this->period = $period;

		if ($gender == 'atp') {
			$this->max_valid = 18;
		} else {
			if ($sd == 's') {
				$this->max_valid = 16;
			} else {
				$this->max_valid = 11;
			}
		}

		foreach ($schema['activity'] as $k => $v) {
			$this->sm[$v] = $k;
		}

		$this->redis = new redis_cli('127.0.0.1', 6379);

		// 读取文件
		$cmd = "cat " . DATA . "/calc/$gender/$sd/$period/loaded " . DATA . "/calc/$gender/$sd/$period/unloaded | sort -t\"	\" -k1,1 -k6g,6 -k8gr,8 -k3,3";
		unset($r); exec($cmd, $r);

		if ($r) {
			foreach ($r as $line) {
				$this->input($line);
			}
			$this->sum();
		}

	}

	protected function clear() {
		unset($this->bonus); $this->bonus = [];
		unset($this->mandantory); $this->mandantory = [];
		unset($this->premier5); $this->premier5 = [];
		unset($this->optional); $this->optional = [];
		$this->win = $this->loss = $this->streak = 0;
	}

	public function output_rank_list() {

		usort($this->result, 'self::sortByTotalPoint');
		$rank = 0;
		foreach ($this->result as $aplayer) {

			if (($this->gender == "wta" && ($aplayer['point'] > 10 || count($aplayer['tours']) >= 3)) || $this->gender == "atp") { // atp直接输出，wta只输出10分以上或者3个有效赛事以上
				echo join("\t", ["---------", $aplayer['first'] . ' ' . $aplayer['last'], $aplayer['point'], ++$rank, "----------------"]) . "\n";

				usort($aplayer['tours'], 'self::sortByLevel');
				foreach ($aplayer['tours'] as $atour) {
					if ($atour[$this->sm['point']] == 10000) $atour[$this->sm['point']] = 0;
					echo join("\t", ["", $atour['level'], $atour[$this->sm['city']], $atour[$this->sm['point']], $atour[$this->sm['final_round']]]) . "\n";
				}

				if (count($aplayer['alt_tours']) > 0) {
					echo "\t--ALT------------------------------------\n";
					usort($aplayer['alt_tours'], 'self::sortByLevel');
					foreach ($aplayer['alt_tours'] as $atour) {
						echo join("\t", ["", "|", $atour['level'], $atour[$this->sm['city']], $atour[$this->sm['point']], $atour[$this->sm['final_round']]]) . "\n";
					}
					echo "\t-----------------------------------------\n";
				}
				echo "\n";
			}
		}
	}

	public function sum() {

		if (count($this->premier5) > 1) {
			usort($this->premier5, 'self::sortByPoint');
		}

		$p5_must = 0;
		if ($this->gender == "wta" && $this->sd = "s") {
			$p5_must = $this->redis->cmd('HGET', 'wta_p5_count_' . $this->pre_pid, $this->period)->get();
		}

		for ($i = 0; $i < count($this->premier5); ++$i) {
			if ($i < $p5_must) {
				$this->mandantory[] = $this->premier5[$i];
			} else {
				$this->optional[] = $this->premier5[$i];
			}
		}
		unset($this->premier5); $this->premier5 = [];

		if (count($this->optional) > 1) {
			usort($this->optional, 'self::sortByPoint');
		}

		$non_countable = [];

		if (count($this->mandantory) + count($this->optional) > $this->max_valid) {
			$countable_num = $this->max_valid - count($this->mandantory);
			$non_countable = array_slice($this->optional, $countable_num);
			$this->optional = array_slice($this->optional, 0, $countable_num);
		}

		$point = array_sum(array_map(function ($d) {return $d[$this->sm['point']] == 10000 ? 0 : $d[$this->sm['point']];}, array_merge($this->bonus, $this->mandantory, $this->optional)));
		$alt_point = array_sum(array_map(function ($d) {return $d[$this->sm['point']];}, $non_countable));

		$info = $this->redis->cmd('HMGET', join("_", [$this->gender, 'profile', $this->pre_pid]), 'first', 'last')->get();
		$first = $info[0];
		$last = $info[1];

		$mand0 = array_sum(array_filter(array_merge($this->bonus, $this->mandantory, $this->optional), function ($d) {return $d[$this->sm['point']] == 10000;}));

		$this->result[] = [
			'pid' => $this->pre_pid,
			'first' => $first,
			'last' => $last,
			'point' => $point,
			'alt' => $alt_point,
			'mand0' => $mand0,
			'win' => $this->win,
			'loss' => $this->loss,
			'streak' => $this->streak,
			'tours' => array_merge($this->bonus, $this->mandantory, $this->optional),
			'alt_tours' => $non_countable,
		];
	}
		

	public function input($line) {
		if (substr($line, 0, 1) == "#") return;

		$arr = explode("\t", $line);

		$pid = $arr[$this->sm['pid']];
		if ($pid != $this->pre_pid && $this->pre_pid !== null) {
			self::sum();
			self::clear();
		}

		// 修改一下低级别赛事的level
		if (in_array($arr[$this->sm['level']], ['CH', 'ITF'])) {
			if ($this->gender == "atp") {
				if ($arr[$this->sm['total_prize']] > 136000) {
					$arr['level'] = "CH125";
				} else if ($arr[$this->sm['total_prize']] > 110000) {
					$arr['level'] = "CH110";
				} else if ($arr[$this->sm['total_prize']] > 90000) {
					$arr['level'] = "CH100";
				} else if ($arr[$this->sm['total_prize']] > 60000) {
					$arr['level'] = "CH90";
				} else if ($arr[$this->sm['total_prize']] > 40000) {
					$arr['level'] = "CH80";
				} else if ($arr[$this->sm['total_prize']] > 20000) {
					$arr['level'] = "M25";
				} else {
					$arr['level'] = "M15";
				}
			} else {
				$arr['level'] = "W" . ($arr[$this->sm['total_prize']] / 1000);
			}
		} else {
			$arr['level'] = $arr[$this->sm['level']];
		}

		// 这一段只是表明每项赛事在最后展示的时候的位置
		if (in_array($arr[$this->sm['level']], ['GS'])) {
			$arr['seq'] = 1;
		} else if (in_array($arr['level'], ['WC', 'YEC'])) {
			$arr['seq'] = 2;
		} else if (in_array($arr['level'], ['AC', 'PM'])) {
			$arr['seq'] = 3;
		} else if (in_array($arr['level'], ['1000', 'P5'])) {
			$arr['seq'] = 4;
		} else if (in_array($arr['level'], ['500', 'P700'])) {
			$arr['seq'] = 5;
		} else if (in_array($arr['level'], ['250', 'Int'])) {
			$arr['seq'] = 6;
		} else if (in_array($arr['level'], ['CH125', '125K'])) {
			$arr['seq'] = 7;
		} else if (in_array($arr['level'], ['CH110'])) {
			$arr['seq'] = 8;
		} else if (in_array($arr['level'], ['CH100', 'W100'])) {
			$arr['seq'] = 9;
		} else if (in_array($arr['level'], ['CH90', 'W80'])) {
			$arr['seq'] = 10;
		} else if (in_array($arr['level'], ['CH80', 'W60'])) {
			$arr['seq'] = 11;
		} else if (in_array($arr['level'], ['M25', 'W25'])) {
			$arr['seq'] = 12;
		} else if (in_array($arr['level'], ['M15', 'W15'])) {
			$arr['seq'] = 13;
		}

		// 强制0改成10000分
		if ($arr[$this->sm['point']] == -1) {
			$arr[$this->sm['point']] = 10000;
		}

		// 本段把赛事按额外、强制、非强制分开
		// wta双打所有的分数都不是强记
		if (in_array($arr[$this->sm['level']], ['AC', 'WC', 'YEC']) && $arr[$this->sm['eid']] != 1081 && !($arr[$this->sm['sd']] == "d" && $this->gender == "wta")) {
			$this->bonus[] = $arr;
		} else if (!preg_match('/^Q[0-9]/', $arr[$this->sm['final_round']]) && in_array($arr[$this->sm['level']], ['GS', '1000', 'PM']) && $arr[$this->sm['eid']] != "0410" && !($arr[$this->sm['sd']] == "d" && $this->gender == "wta")) {
			$this->mandantory[] = $arr;
		} else if (!preg_match('/^Q[0-9]/', $arr[$this->sm['final_round']]) && in_array($arr[$this->sm['level']], ['P5']) && !($arr[$this->sm['sd']] == "d" && $this->gender == "wta")) {
			$this->premier5[] = $arr;
		} else if ($arr[$this->sm['point']] != 0) { // 有分数的肯定记入，分数为-1的也计入（强制分）
			$this->optional[] = $arr;
		} else { // 分数为0的情况
			if ($this->gender == "atp") {
				if (!in_array($arr[$this->sm['level']], ["DC", "LC"]) && $arr[$this->sm['eid']] != 7696 && !preg_match('/^Q[0-9]/', $arr[$this->sm['final_round']])) { // atp，戴杯、新生代不计入，资格赛不计入
					$this->optional[] = $arr;
				}
			} else if ($this->gender == "wta") { // wta只要为0的都不计入
			}
		}

		$this->win += $arr[$this->sm['win']];
		$this->loss += $arr[$this->sm['loss']];
		if ($this->streak * $arr[$this->sm['streak']] < 0) {
			$this->streak = 0;
		}
		$this->streak += $arr[$this->sm['streak']];


		$this->pre_pid = $arr[$this->sm['pid']];
	}

	protected function sortByPoint($a, $b) {
		return $a[$this->sm['point']] > $b[$this->sm['point']] ? -1 : ($a[$this->sm['point']] < $b[$this->sm['point']] ? 1 : ($a[$this->sm['record_date']] > $b[$this->sm['record_date']] ? -1 : 1));
	}

	protected function sortByTotalPoint($a, $b) {
		return $a['point'] > $b['point'] ? -1 : (
			1
		);
	}

	protected function sortByLevel($a, $b) {
		return $a['seq'] < $b['seq'] ? -1 : (
			$a['seq'] > $b['seq'] ? 1 : (
				$a[$this->sm['point']] > $b[$this->sm['point']] ? -1 : 1
			)
		);
	}
}

$calc = new Calc('wta', 's', 'year');
$calc->output_rank_list();
