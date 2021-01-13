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
	protected $mandatory = [];
	protected $premier5 = [];
	protected $optional = [];

	protected $ranks = [];
	protected $ch = []; // career high

	protected $win = 0;
	protected $loss = 0;
	protected $streak = 0;
	protected $prize = 0;

	protected $current_start_date = "";
	protected $current_end_date = "";
	protected $current_prediction = "";
	protected $current_city = "";
	protected $current_round = "";
	protected $current_oppo_pid = "";
	protected $current_partner_pid = "";
	protected $current_status = "";
	protected $current_point = 0;

	protected $redis_info = []; // 记录从redis查出的基本信息，作为缓存
	

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

		$this->redis = new_redis();

		// 读取配置
		$configs = [];
		$file = join("/", [APP, 'conf', 'calc']);
		$cmd = "cat " . $file;
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $line) {
				if (trim($line) == "") continue;
				$arr = explode("=", $line);
				$configs[$arr[0]] = $arr[1];
			}
		}
		$liveranking_end = $configs[$gender . "_liveranking_end"];
		$weeks = $configs[$gender . "_this_weeks"];
		$this->current_start_date = date('Ymd', strtotime($liveranking_end) - $weeks * 7 * 86400);
		$this->current_end_date = date('Ymd', strtotime($liveranking_end));   // 不包含

		// 读取排名
		$file = join("/", [DATA, 'rank', $gender, $sd, 'current']);
		$cmd = "cat " . $file;
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $line) {
				$arr = explode("\t", $line);
				$this->rank[$arr[0]] = $arr[2];
			}
		}
		// 读取最高排名
		$file = join("/", [DATA, 'rank', $gender, $sd, 'highest']);
		$cmd = "cat " . $file;
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $line) {
				$arr = explode("\t", $line);
				$this->ch[$arr[0]] = $arr[1];
			}
		}


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
		unset($this->mandatory); $this->mandatory = [];
		unset($this->premier5); $this->premier5 = [];
		unset($this->optional); $this->optional = [];
		$this->win = $this->loss = $this->streak = $this->prize = $this->current_point = 0;
		$this->current_prediction = $this->current_city = $this->current_round = $this->current_oppo_pid = $this->current_partner_pid = $this->current_status = "";
	}

	public function output_rank_list() {

		usort($this->result, 'self::sortByTotalPoint');
		$rank = 0;

		$output_compose = "";
		$output_rank = "";
		$output_rank_for_db = [];
		$rank = 0;
		$total = 0;
		$pre_point = 0;
		$pre_point4compare = ""; // 同分时候用这个字段比
		foreach ($this->result as $aplayer) {

			if (($this->gender == "wta" && ($aplayer['point'] > 10 || count($aplayer['tours']) >= 3)) || ($this->gender == "atp" && $aplayer['point'] > 0)) { // atp 1分就输出，wta只输出10分以上或者3个有效赛事以上

				++$total;
				if ($aplayer['point'] != $pre_point || $aplayer['point4compare'] != $pre_point4compare) {
					$rank = $total;
				}

				$oppo_pid = explode("/", $aplayer['oppo']);
				$oppo_first = $oppo_last = $oppo_ioc = [];
				foreach ($oppo_pid as $pid) {
					if ($pid == "") continue;
					$info = $this->redis->cmd('HMGET', join("_", [$this->gender, 'profile', $pid]), 'first', 'last', 'ioc')->get();
					$oppo_first[] = $info[0];
					$oppo_last[] = $info[1];
					$oppo_ioc[] = $info[2];
				}

				$partner_first = $partner_last = $partner_ioc = "";
				if ($aplayer['partner']) {
					$info = $this->redis->cmd('HMGET', join("_", [$this->gender, 'profile', $aplayer['partner']]), 'first', 'last', 'ioc')->get();
					$partner_first = $info[0];
					$partner_last = $info[1];
					$partner_ioc = $info[2];
				}
				$h2h = "";
				if ($aplayer['partner']) {
					$me = $aplayer['pid'] . "/" . $aplayer['partner'];
				} else {
					$me = $aplayer['pid'];
				}
				if ($aplayer['oppo'] && $aplayer['oppo'] != "COMEUP" && $aplayer['oppo'] != "LIVE" && $aplayer['oppo'] != "QUAL" && $aplayer['oppo'] != "TBD") {
					$h2h = "0:0";
					$info = $this->redis->cmd('HGET', 'h2h', $me . "\t" . $aplayer['oppo'])->get();
					if ($info) {
						$h2h = $info;
					}
				}

				$arecord = [
					"id" => $aplayer['pid'],
					"f_rank" => $aplayer['official_rank'], // official rank
					"c_rank" => $rank,
					"change" => $aplayer['official_rank'] - $rank, // change
					"highest" => $aplayer['career_high'],
					"point" => $aplayer['point'],
					"alt_point" => $aplayer['alt'],
					"flop" => 0, // flop分
					"first" => $aplayer['first'],
					"last" => $aplayer['last'],
					"ioc" => $aplayer['ioc'],
					"engname" => $aplayer['first'] . " " . $aplayer['last'],
					"age" => $aplayer['birth'] ? floor((time() - strtotime($aplayer['birth'])) / 86400 / 365.25 * 10) / 10 : 0, // age
					"birth" => $aplayer['birth'], // birth
					"tour_c" => count($aplayer['tours']) + count($aplayer['alt_tours']), // plays
					"mand_0" => $aplayer['mand0'],
					"win" => $aplayer['win'],
					"lose" => $aplayer['loss'],
					"win_r" => $aplayer['win'] + $aplayer['loss'] == 0 ? 0 : $aplayer['win'] / ($aplayer['win'] + $aplayer['loss']),
					"streak" => $aplayer['streak'],
					"prize" => $aplayer['prize'],
					"q_tour" => "", // 起计分赛事
					"q_point" => 0, // 起计分
					"titles" => 0, // 冠军数
					"partner_id" => $aplayer['partner'],
					"partner_first" => $partner_first,
					"partner_last" => $partner_last,
					"partner_ioc" => $partner_ioc,
					"oppo_id" => $aplayer['oppo'],
					"oppo_first" => join("/", $oppo_first),
					"oppo_last" => join("/", $oppo_last),
					"oppo_ioc" => join("/", $oppo_ioc),
					"next_h2h" => $h2h,
					"w_tour" => $aplayer['city'],
					"w_round" => $aplayer['round'],
					"w_point" => $aplayer['this_week_point'],
					"w_in" => $aplayer['status'],
					"predict" => $aplayer['prediction'],
				];

				$output_rank_for_db[] = $arecord;
				$output_rank .= join("\t", array_values($arecord)) . "\n";
					

				// 计分赛事按级别排序
				$cnt = 0;
				usort($aplayer['tours'], 'self::sortByLevel');
				foreach ($aplayer['tours'] as $atour) {
					if ($atour[$this->sm['point']] == 10000) $atour[$this->sm['point']] = 0;
					$output_compose .= join("\t", [
						$aplayer['pid'],
						++$cnt,
						$atour[$this->sm['city']], 
						$atour[$this->sm['point']], 
						$atour[$this->sm['final_round']], 
						date('Ymd', strtotime($atour[$this->sm['record_date']]) + 364 * 86400),
						$atour['seq'],
						$atour[$this->sm['surface']],
						$atour['level'], 
						$atour[$this->sm['year']]
					]) . "\n";
				}

				// 非计分赛事按级别排序
				if (count($aplayer['alt_tours']) > 0) {
					$cnt = -$cnt;
					usort($aplayer['alt_tours'], 'self::sortByLevel');
					foreach ($aplayer['alt_tours'] as $atour) {
						$output_compose .= join("\t", [
							$aplayer['pid'],
							--$cnt,
							$atour[$this->sm['city']], 
							$atour[$this->sm['point']], 
							$atour[$this->sm['final_round']], 
							date('Ymd', strtotime($atour[$this->sm['record_date']]) + 364 * 86400),
							$atour['seq'],
							$atour[$this->sm['surface']],
							$atour['level'], 
							$atour[$this->sm['year']]
						]) . "\n";
					}
				}


				$pre_point = $aplayer['point'];
				$pre_point4compare = $aplayer['point4compare'];
			}
		}


		$fp = fopen(join("/", [DATA, 'calc', $this->gender, $this->sd, $this->period, 'compose']), 'w');
		fputs($fp, $output_compose);
		fclose($fp);

		$fp = fopen(join("/", [DATA, 'calc', $this->gender, $this->sd, $this->period, 'rank']), 'w');
		fputs($fp, $output_rank);
		fclose($fp);

		$tbname = join("_", ['calc', $this->gender, $this->sd, $this->period]);
		$db = new_db("test");
		$sql = "delete from " . $tbname;
		if (!$db->query($sql)) {
			print_err("===========================上行删除sql ERROR==============================");
			return;
		} else {
			if (!$db->multi_insert($tbname, $output_rank_for_db, array_keys($output_rank_for_db[0]))) {
				print_err("===========================上行插入ERROR==============================");
				return;
			}
			$sql = "update info set `value_time` = '" . date('Y-m-d H:i:s') . "' where `key` = '" . join("_", ['calc', $this->gender, $this->sd, $this->period, 'update_time']) . "';";
			print_err($sql);
			if (!$db->query($sql)) {
				print_err("===========================上行修改时间 sql ERROR=============================="); 
				return;
			}
		}

	}

	public function sum() {

		if (!preg_match('/^([A-Z0-9]{4}|[0-9]{5,6})$/', $this->pre_pid)) return;
		if (in_array($this->pre_pid, ["310137", "313402", "312894", "313381", "312580", "314672", "311593"])) return;

		// 把超5分数排序，根据强记超五的个数，把强记部分扔到mandatory里去，非强记部分扔到optional里去
		if (count($this->premier5) > 1) {
			usort($this->premier5, 'self::sortByPoint');
		}

		// 特殊策略，相同赛事只记高的
		self::removeDupliByIdx($this->premier5, $this->sm["eid"]);

		$p5_must = 0;
		if ($this->gender == "wta" && $this->sd == "s") {
			$p5_must = $this->redis->cmd('HGET', 'wta_p5_count_' . $this->pre_pid, $this->period)->get();
		}

		for ($i = 0; $i < count($this->premier5); ++$i) {
			if ($i < $p5_must) {
				$this->mandatory[] = $this->premier5[$i];
			} else {
				$this->optional[] = $this->premier5[$i];
			}
		}
		unset($this->premier5); $this->premier5 = [];

		// optional和mandatory都重排序
		if (count($this->optional) > 1) {
			usort($this->optional, 'self::sortByPoint');
		}
		if (count($this->mandatory) > 1) {
			usort($this->mandatory, 'self::sortByPoint');
		}
		if (count($this->bonus) > 1) {
			usort($this->bonus, 'self::sortByPoint');
		}

		// 特殊策略，相同赛事只记高的(靠前的)
		self::removeDupliByIdx($this->optional, $this->sm["eid"]);
		self::removeDupliByIdx($this->mandatory, $this->sm["eid"]);
		self::removeDupliByIdx($this->bonus, $this->sm["eid"]);

		// --------------------------特殊处理，把20200501之后的比赛全部算作非强制项
		$_mandatory = [];
		foreach ($this->mandatory as $item) {
			if ($item[$this->sm["start_date"]] > 20200501) {
				$this->optional[] = $item;
			} else {
				$_mandatory[] = $item;
			}
		}
		$this->mandatory = $_mandatory;
		if (count($this->optional) > 1) {
			usort($this->optional, 'self::sortByPoint');
		}
		// --------------------------结束特殊处理

		// 超出数量的扔到non-countable里
		$non_countable = [];
		if (count($this->mandatory) + count($this->optional) > $this->max_valid) {
			$countable_num = $this->max_valid - count($this->mandatory);
			$non_countable = array_slice($this->optional, $countable_num);
			$this->optional = array_slice($this->optional, 0, $countable_num);
		}

		// 总分与替补分
		$point = array_sum(array_map(function ($d) {return $d[$this->sm['point']] == 10000 ? 0 : $d[$this->sm['point']];}, array_merge($this->bonus, $this->mandatory, $this->optional)));
		$alt_point = array_sum(array_map(function ($d) {return $d[$this->sm['point']];}, $non_countable));

		// 大师赛分或者一级赛分
		if ($this->gender == "atp") {
			$master_point = array_sum(
				array_map(
					function ($d) {
						return in_array($d["level"], ["GS", "WC", "1000"]) && $d[$this->sm["eid"]] != "0410" && !preg_match('/^Q[0-9]/', $d[$this->sm["final_round"]]) && $d[$this->sm["point"]] != 10000 ? $d[$this->sm["point"]] : 0;
					}, 
					array_merge($this->bonus, $this->mandatory, $this->optional)
				)
			);
		} else {
			$master_point = array_sum(
				array_map(
					function ($d) {
						return in_array($d["level"], ["GS", "YEC", "PM", "P5"]) && !preg_match('/^Q[0-9]/', $d[$this->sm["final_round"]]) && $d[$this->sm["point"]] != 10000 ? $d[$this->sm["point"]] : 0;
					}, 
					array_merge($this->bonus, $this->mandatory, $this->optional)
				)
			);
		}

		// 巡回赛分
		if ($this->gender == "atp") {
			$tour_point = 0;
		} else {
			$tour_point = array_sum(
				array_map(
					function ($d) {
						return in_array($d["level"], ["GS", "YEC", "PM", "P5", "XXI", "P700", "Int"]) && !preg_match('/^Q[0-9]/', $d[$this->sm["final_round"]]) && $d[$this->sm["point"]] != 10000 ? $d[$this->sm["point"]] : 0;
					}, 
					array_merge($this->bonus, $this->mandatory, $this->optional)
				)
			);
		}

		// 参赛数
		$total_plays = count($this->bonus) + count($this->mandatory) + count($this->optional) + count($non_countable);

		// 计算前三大分数 
		$not0 = array_filter(array_merge($this->bonus, $this->mandatory, $this->optional), function ($d) {return $d[$this->sm['point']] != 10000;});
		usort($not0, 'self::sortByPoint');
		$max_1st = $max_2nd = $max_3rd = 0;
		if (count($not0) > 0) $max_1st = $not0[0][$this->sm['point']];
		if (count($not0) > 1) $max_2nd = $not0[1][$this->sm['point']];
		if (count($not0) > 2) $max_3rd = $not0[2][$this->sm['point']];

		// 计算用于比较同分时先后顺序的量
		$point4compare = sprintf("rank%05u%05u%05u%02u%04u%04u%04u", $point, $master_point, $tour_point, 99 - $total_plays, $max_1st, $max_2nd, $max_3rd);

		if (!isset($this->redis_info[$this->pre_pid])) {
			$info = $this->redis->cmd('HMGET', join("_", [$this->gender, 'profile', $this->pre_pid]), 'first', 'last', 'ioc', 'birthday')->get();
		} else {
			$info = $this->redis_info[$this->pre_pid];
		}
		$first = $info[0];
		$last = $info[1];
		$ioc = $info[2];
		$birth = $info[3];

		$mand0 = count(array_filter(array_merge($this->bonus, $this->mandatory, $this->optional), function ($d) {return $d[$this->sm['point']] == 10000;}));

		$this->result[] = [
			'pid' => $this->pre_pid,
			'official_rank' => isset($this->rank[$this->pre_pid]) ? $this->rank[$this->pre_pid] : 9999,
			'career_high' => isset($this->ch[$this->pre_pid]) ? $this->ch[$this->pre_pid] : 9999,
			'first' => $first,
			'last' => $last,
			'ioc' => $ioc,
			'birth' => $birth,
			'point' => $point,
			'alt' => $alt_point,
			'mand0' => $mand0,
			'win' => $this->win,
			'loss' => $this->loss,
			'streak' => $this->streak,
			'prize' => $this->prize,
			'point4compare' => $point4compare,
			'tours' => array_merge($this->bonus, $this->mandatory, $this->optional),
			'alt_tours' => $non_countable,
			'oppo' => $this->current_oppo_pid,
			'partner' => $this->current_partner_pid,
			'city' => $this->current_city,
			'round' => $this->current_round,
			'this_week_point' => $this->current_point,
			'status' => $this->current_status,
			'prediction' => $this->current_prediction,
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
				if ($arr[$this->sm['total_prize']] > 132000) {
					$arr['level'] = "CH125";
				} else if ($arr[$this->sm['total_prize']] > 110000) {
					$arr['level'] = "CH110";
				} else if ($arr[$this->sm['total_prize']] > 88000) {
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
				$arr['level'] = "W" . floor($arr[$this->sm['total_prize']] / 1000);
			}
		} else {
			$arr['level'] = $arr[$this->sm['level']];
		}

		// 这一段只是表明每项赛事在最后展示的时候的位置
		if (in_array($arr[$this->sm['level']], ['GS'])) {
			$arr['seq'] = 1;
		} else if (in_array($arr['level'], ['WC', 'YEC', 'XXI'])) {
			$arr['seq'] = 2;
		} else if (in_array($arr['level'], ['AC', 'PM'])) {
			$arr['seq'] = 3;
		} else if (in_array($arr['level'], ['1000', 'P5', 'WTA1000', 'ATP1000'])) {
			$arr['seq'] = 4;
		} else if (in_array($arr['level'], ['500', 'P700', 'WTA500', 'ATP500'])) {
			$arr['seq'] = 5;
		} else if (in_array($arr['level'], ['250', 'Int', 'WTA250', 'ATP250'])) {
			$arr['seq'] = 6;
		} else if (in_array($arr['level'], ['CH125', '125K', 'WTA125'])) {
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
		} else {
			//print_err($arr['level']);
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
			$this->mandatory[] = $arr;
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

		$this->prize += $arr[$this->sm['prize']];
		$this->win += $arr[$this->sm['win']];
		$this->loss += $arr[$this->sm['loss']];
		if ($this->streak * $arr[$this->sm['streak']] < 0) {
			$this->streak = 0;
		}

		if ($arr[$this->sm['streak']] > 0) {
			if ($arr[$this->sm['streak']] == $arr[$this->sm['win']] && $arr[$this->sm['loss']] == 0 && $this->streak >= 0) {
				$this->streak += $arr[$this->sm['streak']] + 0;
			} else {
				$this->streak = $arr[$this->sm['streak']] + 0;
			}
		} else if ($arr[$this->sm['streak']] < 0) {
			if ($arr[$this->sm['streak']] + $arr[$this->sm['loss']] == 0 && $arr[$this->sm['win']] == 0 && $this->streak <= 0) {
				$this->streak += $arr[$this->sm['streak']] + 0;
			} else {
				$this->streak = $arr[$this->sm['streak']] + 0;
			}
		}

		if ($arr[$this->sm['start_date']] >= $this->current_start_date && $arr[$this->sm['start_date']] < $this->current_end_date) {
			$this->current_city = $arr[$this->sm['city']];
			$this->current_round = $arr[$this->sm['final_round']];
			$this->current_point += $arr[$this->sm['point']];
			$this->current_oppo_pid = $arr[$this->sm['next']];
			$this->current_partner_pid = $arr[$this->sm['partner_id']];
			$this->current_prediction = @$arr[$this->sm['prediction']];
			$this->current_status = @$arr[$this->sm['indraw']];
		}

		$this->pre_pid = $arr[$this->sm['pid']];
	}

	// 分数高的在前，分数一样的，时间早的在前
	protected function sortByPoint($a, $b) {
		return $a[$this->sm['point']] > $b[$this->sm['point']] ? -1 : ($a[$this->sm['point']] < $b[$this->sm['point']] ? 1 : ($a[$this->sm['record_date']] < $b[$this->sm['record_date']] ? -1 : 1));
	}

	protected function sortByTotalPoint($a, $b) {
		return $a['point'] > $b['point'] ? -1 : (
			$a['point'] < $b['point'] ? 1 : (
				strcmp($a['point4compare'], $b['point4compare']) > 0 ? -1 : (
					1
				)
			)
		);
	}

	protected function sortByLevel($a, $b) {
		return $a['seq'] < $b['seq'] ? -1 : (
			$a['seq'] > $b['seq'] ? 1 : (
				$a[$this->sm['point']] > $b[$this->sm['point']] ? -1 : 1
			)
		);
	}

	protected function removeDupliByIdx(&$list, $idx) {
		$dict = [];
		$new_list = [];
		foreach ($list as $v) {
			if (!in_array($v[$idx], $dict)) { // 不在去重字典的才保留
				$new_list[] = $v;
				$dict[] = $v[$idx];
			} else if ($v['seq'] > 6) { // 对于低级别赛事，不管在不在字典都保留
				$new_list[] = $v;
			}
		}
		$list = $new_list;
	}

}
