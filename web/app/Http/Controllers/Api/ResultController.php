<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\HighLight;
use App;
use Config;
use Route;

class ResultController extends Controller
{
	protected $path;
	protected $down_path;
	protected $date;
	protected $files;
	protected $year;

	public function __construct() {

		$this->path = Config::get('const.root') . '/share/*completed';
		$this->down_path = Config::get('const.root') . '/share/down_result';

	}

	//只显示当前正常进行的比赛。日期选前后三天
	public function live($lang) {

		App::setLocale($lang);

		$yesterday = date('Y-m-d', time() - 86400);
		$today = date('Y-m-d', time());
		$tomorrow = date('Y-m-d', time() + 86400);
		$this->date = [$yesterday, $today, $tomorrow];
		$this->files = join(' ', [$this->path."/".$yesterday, $this->path."/".$today, $this->path."/".$tomorrow]);
		$this->year = 2019;

		$now = time();

		$all_tours = [];

		self::get_tours($all_tours, true, 0);

		return view('result.index', [
			'ret' => $all_tours,
			'date' => join(' ', $this->date),
			'year' => $this->year,
			'now' => $now,
			'timestamp' => [0, 20000000000],
			'show_status' => 1,
			'pageTitle' => __('frame.menu.live'),
			'title' => __('frame.menu.live'),
			'pagetype1' => 'live',
			'pagetype2' => 'main',
		]);

	}

	// 显示一天的比赛。默认只显示巡回赛
	public function date($lang, $date, $tz = null) {

		$tic = time();
		App::setLocale($lang);

		$unixtime = 0;

		if ($tz === null) {
			$this->date = [$date];
			$this->files = $this->path . "/" . $date;
			$this->year = date('Y', strtotime($date . " +4 days"));
		} else {
			$unixtime = strtotime($date) + (8 - $tz) * 3600;
			$today = date('Y-m-d', $unixtime);
			$yesterday = date('Y-m-d', $unixtime - 86400);
			$tomorrow = date('Y-m-d', $unixtime + 86400);

			$this->files = join(" ", [$this->path . "/" . $yesterday, $this->path . "/" . $today, $this->path . "/" . $tomorrow]);
			$this->year = date('Y', strtotime($date . " +4 days"));
			$this->date = [$yesterday, $today, $tomorrow];
		}

		$now = time();

		$all_tours = [];

		self::get_tours($all_tours, false, $unixtime);

		$min_timestamp = strtotime($date . " 6:0:0");
		$max_timestamp = strtotime($date . " 16:0:0") + 86400;

//		echo json_encode($all_tours, JSON_UNESCAPED_UNICODE);

		return json_encode([
			'tours' => $all_tours,
			'date' => $date,
			'year' => $this->year,
			'now' => $now,
			'timestamp' => [$min_timestamp, $max_timestamp],
			'pageTitle' => __('frame.menu.score') . " " . $date,
			'title' => __('frame.menu.score') . " " . $date,
			'pagetype1' => 'result',
			'pagetype2' => $date,
			'tic' => $tic,
			'toc' => time(),
		]);
	}

	// 显示天单个赛事
	public function eid(Request $req, $lang, $date, $joint_eid = null, $tz = null) {

		App::setLocale($lang);

		$unixtime = 0;

		if ($tz === null) {
			$this->date = [$date];
			$this->files = $this->path . "/" . $date;
			$this->year = date('Y', strtotime($date . " +4 days"));
		} else {
			$unixtime = strtotime($date) + (8 - $tz) * 3600;
			$today = date('Y-m-d', $unixtime);
			$yesterday = date('Y-m-d', $unixtime - 86400);
			$tomorrow = date('Y-m-d', $unixtime + 86400);

			$this->files = join(" ", [$this->path . "/" . $yesterday, $this->path . "/" . $today, $this->path . "/" . $tomorrow]);
			$this->year = date('Y', strtotime($date . " +4 days"));
			$this->date = [$yesterday, $today, $tomorrow];
		}

		$is_tournament = 1;

		$show_status = $req->input('show_status', -1);

		$courts = self::get_init_tours(null, $is_tournament, $show_status, $unixtime, $joint_eid);
		return $courts;
	}

	public function oop_date($lang, $date = NULL) {

		App::setLocale($lang);

		return view('result.oop_index', [
			'date' => $date,
			'pageTitle' => __('frame.menu.score') . ($date ? " " . $date : ""),
			'title' => __('frame.menu.score') . ($date ? " " . $date : ""),
			'pagetype1' => 'oop',
			'pagetype2' => $date,
		]);

	}

	public function unixdate(Request $req, $lang, $date, $unixtime) {

		App::setLocale($lang);
		$today = date('Y-m-d', $unixtime);
		$yesterday = date('Y-m-d', $unixtime - 86400);
		$tomorrow = date('Y-m-d', $unixtime + 86400);

		$this->files = join(" ", [$this->path . "/" . $yesterday, $this->path . "/" . $today, $this->path . "/" . $tomorrow]);
		$this->year = date('Y', strtotime($date . " +4 days"));

		$this->date = [$yesterday, $today, $tomorrow];

		$now = time();

		$all_tours = [];

		self::get_tours($all_tours, false, $unixtime);

		$min_timestamp = strtotime($date . " 6:0:0");
		$max_timestamp = strtotime($date . " 16:0:0") + 86400;

//		echo json_encode($all_tours, JSON_UNESCAPED_UNICODE);

		$route = 'result.oop_matches';

		$date = date('Y-m-d', strtotime($date));

		return view($route, [
			'ret' => $all_tours,
			'date' => $date,
			'year' => $this->year,
			'now' => $now,
			'timestamp' => [$min_timestamp, $max_timestamp],
			'pageTitle' => __('frame.menu.score') . " " . $date,
			'title' => __('frame.menu.score') . " " . $date,
		]);
	
	}


	public function unixdate_event(Request $req, $lang, $date, $unixtime, $eid) {
		App::setLocale($lang);
		$today = date('Y-m-d', $unixtime);
		$yesterday = date('Y-m-d', $unixtime - 86400);
		$tomorrow = date('Y-m-d', $unixtime + 86400);
		$this->files = join(" ", [$this->path . "/" . $yesterday, $this->path . "/" . $today, $this->path . "/" . $tomorrow]);
		$this->year = date('Y', strtotime($date . " +4 days"));
		$this->date = [$yesterday, $today, $tomorrow];

		$is_tournament = 1;
		$show_status = $req->input('show_status', -1);
		$all_tours = [$eid, '', '', $is_tournament, '', '', self::get_init_tours($eid, $is_tournament, $show_status, $unixtime)];
		return view('result.content', [
			'tour' => $all_tours,
			'date' => $date,
			'year' => $this->year]);
	}

	// 即时比分ajax
	public function get_live($lang, $ts = NULL) {

		if ($ts && ($ts - time() < -30 || $ts - time() > 0)) {
			http_response_code(500);
			exit;
		}

		App::setLocale($lang);

		$file = $this->down_path . '/live_score*';

		$cmd = "cat $file";
		unset($r); exec($cmd, $r);

		$live_event = ['ts' => time()];

		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$tourId = $arr[21];
				$matchId = $arr[0];
				$live_event[@$tourId][@$matchId] = self::get_match_from_live($row);
			}
		}

		return json_encode($live_event, JSON_UNESCAPED_UNICODE);

	}

	protected function get_tours(&$ret, $only_live, $unixtime, $true_eid = null) {

		/*
			$ret 用于返回结果 
			$only_live 是否为live页
			$unixtime 当地的0点的unixtime
			$true_eid 为draw系统里的eid。若为空，则是分日赛程，不为空则是分站赛程
		*/

		$rett = [];

		$favoriteIOC = "CHN";
		$favoriteIOCs = implode("|", explode(",", $favoriteIOC));

		// 获取赛事列表
		if ($only_live) {
			$cmd = "cat $this->files | awk -F \"\\t\" '$30 < 2' | cut -f4-8,22 | sort -uf | sort -k1gr,1";
		} else {
			if ($unixtime > 0) {
				$cmd = "cat $this->files | awk -F\"\\t\" 'substr($14,length($14)-9) >= " . $unixtime . "-5400 && substr($14,length($14)-9) <= " . $unixtime . "+86400' | cut -f4-8,22- | awk -F\"\\t\" '{print $1\"\\t\"$2\"\\t\"$3\"\\t\"$4\"\\t\"$5\"\\t\"$6\"\\t\"($23 ~ /(" . $favoriteIOCs . ")/ || $24 ~ /(" . $favoriteIOCs . ")/);}' | sort -ruf | sort -sru -t\" \" -k1gr,1 -k2,2 -k3,3 -k4,4 -k5,5 -k6,6";
			} else {
				$cmd = "cat $this->files | cut -f4-8,22- | awk -F\"\\t\" '{print $1\"\\t\"$2\"\\t\"$3\"\\t\"$4\"\\t\"$5\"\\t\"$6\"\\t\"($35 ? $35 : $6)\"\\t\"($23 ~ /(" . $favoriteIOCs . ")/ || $24 ~ /(" . $favoriteIOCs . ")/);}' | sort -ruf | sort -sru -t\"	\" -k1gr,1 -k2,2 -k3,3 -k4,4 -k5,5 -k6,6 -k7,7";
			}
		}

		if ($true_eid !== null) {
			$cmd .= " | awk -F\"\\t\" '$6 == \"$true_eid\" || $2 ~ / $true_eid-" . $this->year . "/'";
		}
		unset($r); exec($cmd, $r);

		if ($r) {

			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if (count($arr) != 6 && count($arr) != 7 && count($arr) != 8) {
					continue;
				} else {
					$containsCHN = intval(@$arr[7]);
					$eid = $arr[5];
					$joint_eid = @$arr[6];
					if (!$joint_eid) $joint_eid = $eid;
					$city = translate_tour(trim(str_replace('.', '', strtolower($arr[2]))));
					$title = $arr[1];
					$prize = $arr[0];
					$sfc = reviseSurfaceWithoutIndoor($arr[3]);
					//$sfc = Config::get('const.groundColor.' . $sfc);
					$levels = explode("/", $arr[4]);
					$logos = [];
					foreach ($levels as $level) {
						$logos[] = parse_url(get_tour_logo_by_id_type_name($eid, $level, $city, $title), PHP_URL_PATH);
					}
					$is_tournament = true;

					// 如果是live模式，恒展示
					if (!$only_live) {
						if (strpos($arr[4], "Challenger") !== false || strpos($arr[4], "CH") !== false) {
							$city .= '(' . str_replace("ATP Challengers ", "", $arr[4]) . ')';
						} else if (strpos($arr[4], "WTA 125K") !== false) {
							$city .= '($' . ($prize / 1000) . 'K)';
						} else if (strpos($arr[4], "ITF") !== false) {
							$city .= '(' . str_replace("ITF ", "", $arr[4]) . ')';
							if ($prize < 30000 && $true_eid === null && !$containsCHN) {
								$is_tournament = false;
							}
						}
						$show_status = -1;
					} else {
						$show_status = 1;
					}

					// 取出真正的eid
					if (strpos($arr[4], 'ITF') === false) {
						$eventid = $arr[5];
						$year = $this->year;
					} else {
						unset($m);
						preg_match('/ ([^ ]+)-([0-9]{4})$/', $title, $m);
						if ($m) {
							$eventid = $m[1];
							$year = $m[2];
						} else {
							$eventid = $arr[5];
							$year = $this->year;
						}
					}

					$courts = self::get_init_tours($eid, $is_tournament, $show_status, $unixtime, $joint_eid);

					if (!isset($rett[$joint_eid])) {
						$rett[$joint_eid] = [
							'joint_eid' => $joint_eid,
							'sub_tour' => [[
								'eid' => $eid, 
								'title' => $title, 
								'logos' => $logos[0], 
							]],
							'city' => $city, 
							'sfc' => strtolower($sfc), 
							'open' => (int)$is_tournament, 
							'loaded' => (int)$is_tournament, 
							'courts' => $courts, 
							'year' => $year,
						];
					} else {
						$rett[$joint_eid]['sub_tour'][] = [
							'eid' => $eid, 
							'title' => $title, 
							'logos' => $logos[0], 
						];
						if (count($rett[$joint_eid]['courts']) == 0 && count($courts) > 0) { // 如果之前没有球场信息，现在有了，就加进去
							$rett[$joint_eid]['courts'] = $courts;
							$rett[$joint_eid]['open'] = $rett[$joint_eid]['loaded'] = 1;
						}
					}
				}
			}
		}
		$ret = array_values($rett);
	}

	protected function get_init_tours($eid, $is_tournament, $show_status = -1, $unixtime, $joint_eid = NULL) {

		if (!$is_tournament) return [];

		return self::get_matches($eid, $show_status, $unixtime, $joint_eid);

	}

	protected function get_match_from_live($row) {

		$arr = explode("\t", $row);

		$dura = $arr[17] ? date('H:i', strtotime($arr[17])) : "";

		$pointflag = @$arr[29];

		$score_json = $arr[16];

		if ($score_json) {
			$score = json_decode($score_json);
			if (count($score) != 2) {
				$score_json = '';
			} else {
				$score1 = $score[0];
				$result1 = array_shift($score1);
				$point1 = array_shift($score1);

				$score2 = $score[1];
				$result2 = array_shift($score2);
				$point2 = array_shift($score2);
			}
		}

		// 如果比赛结束，修正status
		$winner = 0;
		if (strpos($result1 . $result2, 'iconfont') !== false || strpos($result1 . $result2, 'WINNER') !== false) {
			$status = 2;
			$pointflag = '';
		} else {
			$status = 1;
		}

		$pointflag = self::revisePointFlag($pointflag);

		self::reviseResultFlag($result1);
		self::reviseResultFlag($result2);

		$winner = $serving = 0;
		if ($result1 == "Winner") {
			$winner = 1;
		} else if ($result2 == "Winner") {
			$winner = 2;
		} else if ($result1 == "Serve") {
			$serving = 1;
		} else if ($result2 == "Serve") {
			$serving = 2;
		}

		if ($point1 == 40 && ($point2 == "A" || $point2 == "AD")) {
			$point1 = "";
			$point2 = "A";
		}
		if ($point2 == 40 && ($point1 == "A" || $point1 == "AD")) {
			$point2 = "";
			$point1 = "A";
		}

		return [
			'status' => $status, 
			't1Score' => $this->reviewScore($score1), 
			't2Score' => $this->reviewScore($score2), 
			't1Point' => $point1, 
			't2Point' => $point2, 
			'dura' => $dura, 
			'flag' => $pointflag,
			'serving' => $serving,
			'winner' => $winner,
		];
	}

	protected function get_matches($eid, $show_status, $unixtime, $joint_eid) {

		$file = $this->down_path . '/live_score*';

		if (!$eid) $eid = $joint_eid;

		$cmd = "awk -F \"\\t\" '($32 != \"\" && $32 == \"$joint_eid\") || $22 == \"$eid\"' $file";
		unset($r); exec($cmd, $r);

		$live_info = [];

		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$matchId = $arr[0];
				$true_eid = @$arr[31];
				@$live_info[$true_eid][$matchId] = [$arr[16], $arr[17], $arr[29], $arr[28]];  // score, time, pointflag, updatetime
			}
		}

		$exist_hl = [];
		$exist_whole = [];
		$ones = HighLight::whereIn('matchdate', $this->date)->where('eid', $eid)->get();
		foreach ($ones as $one) {
			$exist_hl[$one->matchid] = $one->hl;
			$exist_whole[$one->matchid] = $one->whole;
		}

		if ($unixtime == 0) {
			$cmd = "awk -F \"\\t\" '($51 != \"\" && $51 == \"$joint_eid\") || $22 == \"$eid\"' $this->files | sort -t\"	\" -k10g,10 -k12g,12";
		} else {
			$cmd = "awk -F \"\\t\" '($51 != \"\" && $51 == \"$joint_eid\") || $22 == \"$eid\" && substr($14,length($14)-9) >= " . $unixtime . "-5400 && substr($14,length($14)-9) <= " . $unixtime . "+86400' $this->files | sort -t\"	\" -k10g,10 -k12g,12";
		}
		unset($r); exec($cmd, $r);

		$courts = [];

		if ($r) {

			foreach ($r as $row) {

				$arr = explode("\t", $row);
				$schema = Config::get('const.schema_completed');
				unset($kvmap);
				foreach ($arr as $k => $v) $kvmap[Config::get('const.schema_completed.' . $k)] = $v;

				$courtname = $kvmap['courtseq'] . "\t" . translate('courtname', str_replace('.', '', strtolower($kvmap['courtname'])), true);

				$matchId = $kvmap['matchid'];

				$true_eid = @$kvmap["eid"];

				$sex = translate('sexname', $kvmap['sexid']);
				$has_detail = true;
				//if ($kvmap['sexid'] < 5 || $kvmap['sexid'] > 20) $has_detail = true; else $has_detail = false;

				$round = translate('roundname', $kvmap['round']);

				if (in_array(substr($matchId, 0, 2), ['MS', 'QS', 'MD', 'QD', 'M-']) || in_array($kvmap['sexid'], [0, 2, 5, 7])) {
					$sextype = 'atp';
				} else if (in_array(substr($matchId, 0, 2), ['WS', 'LS', 'RS', 'PS', 'WD', 'LD', 'PD', 'RD', 'W-']) || in_array($kvmap['sexid'], [1, 3, 6, 8])) {
					$sextype = 'wta';
				} else if (strpos($kvmap['tour'], "M-FU-") !== false || strpos($kvmap['tour'], "M-ITF-") !== false) {
					$sextype = 'atp';
				} else if (strpos($kvmap['tour'], "W-WITF-") !== false || strpos($kvmap['tour'], "W-ITF-") !== false) {
					$sextype = 'wta';
				} else if ($joint_eid == "DC") {
					$sextype = 'atp';
				} else if ($joint_eid == "FC") {
					$sextype = 'wta';
				} else {
					$sextype = '';
				}

				$mStatus = @$kvmap["mStatus"];

				$time = "";
				if ($kvmap['schedule'] != "") {
					if (strpos($kvmap['schedule'], "[") === false) {
						$schedule_json = explode(",", $kvmap['schedule']);
					} else {
						$schedule_json = json_decode($kvmap['schedule'], true);
					}
					if (count($schedule_json) == 1 && $schedule_json[0] == "Followed By") {
						$time = __('result.notice.followby');
					} else if (count($schedule_json) == 5) {
						$nbtext = $schedule_json[0];
						if ($nbtext == "Not Before") {
							$nbtext = __('result.notice.nb') . " ";
						} else if ($nbtext == "Starts At") {
							$nbtext = "";
						} else if ($nbtext == "Estimated") {
							$nbtext = __('result.notice.est') . " ";
						} else {
							$nbtext = "";
						}
						$time = $schedule_json[1] . "|" . $schedule_json[4] . "|" . $nbtext;
					} else if (count($schedule_json) == 6) {
						$time = $schedule_json[5];
					}
				}

				if (App::isLocale('zh')) {
					$p1 = explode("/", $kvmap['p1chn']);
					$p2 = explode("/", $kvmap['p2chn']);
				} else {
					$p1 = explode("/", $kvmap['p1eng']);
					$p2 = explode("/", $kvmap['p2eng']);
				}
				if (trim($p1[0]) == "") $p1 = [__('result.notice.tbd')];
				if (trim($p2[0]) == "") $p2 = [__('result.notice.tbd')];

				foreach ([1, 2] as $i) {
					if (isset($kvmap['p'.$i.'first']) || isset($kvmap['p'.$i.'last']) || isset($kvmap['p'.$i.'ioc'])) {
						$first_arr = explode("/", @$kvmap['p'.$i.'first']);
						$last_arr = explode("/", @$kvmap['p'.$i.'last']);
						$ioc_arr = explode("/", @$kvmap['p'.$i.'ioc']);
						$tmpP = [];
						foreach ($first_arr as $k => $v) {
							if (strpos($v, '|') === false) {
								$tmpP[] = rename2long($v, @$last_arr[$k], @$ioc_arr[$k]);
							} else {
								$tmp = [];
								$f_arr = explode("|", $first_arr[$k]);
								$l_arr = explode("|", $last_arr[$k]);
								$i_arr = explode("|", @$ioc_arr[$k]);
								foreach ($f_arr as $k1 => $v1) {
									if ($k1 == 0) continue;
									$tmp[] = rename2short($v1, $l_arr[$k1], @$i_arr[$k1]);
								}
								$tmpP[] = join(__('result.notice.or'), $tmp);
							}
						}
						if (trim($tmpP[0]) != "") {
							${'p'.$i} = $tmpP;
						}
					}
				}

				$ioc1 = explode("/", @$kvmap['p1ioc']);
				$ioc2 = explode("/", @$kvmap['p2ioc']);

				$dura = $kvmap['dura'] ? date('H:i', strtotime($kvmap['dura'])) : "";

				$id1 = explode("/", $kvmap['p1id']);
				$id2 = explode("/", $kvmap['p2id']);
				// $id有两个，则认为是双打
				if (count($id1) > 1 || count($id2) > 1) {
					$sd = "d";
				} else {
					$sd = "s";
				}

				$id1 = join('/', $id1);
				$id2 = join('/', $id2);

				$join_id = explode('/', join('/', [$id1, $id2]));

				$className = join(" ", array_map('self::addPlayerSelect', $join_id));

				if ($sextype) {
					$h2hLink = 'open_h2h("' . $true_eid . '", "' . $sextype . '", "' . $matchId . '", "' . $this->year . '", "' . $id1 . '", "' . $id2 . '", "' . join('/', $p1) . '", "' . join('/', $p2) . '", "' . $sd . '")';
				} else {
					$h2hLink = '';
				}

				$statLink = 'open_stat("' . $true_eid . '", "' . $sextype . '", "' . $matchId . '", "' . $this->year . '", "' . $id1 . '", "' . $id2 . '", "' . join('/', $p1) . '", "' . join('/', $p2) . '")';

				if ($has_detail) {
					$detailLink = 'open_detail("' . @$kvmap['fsid'] . '", "' . $true_eid . '", "' . $sextype . '", "' . $matchId . '", "' . $this->year . '", "' . $id1 . '", "' . $id2 . '", "' . join('/', $p1) . '", "' . join('/', $p2) . '")';
				} else {
					$detailLink = "";
				}

				$hlLink = "";
				if (isset($exist_hl[$matchId])) $hlLink = $exist_hl[$matchId];
				$wholeLink = "";
				if (isset($exist_whole[$matchId])) $wholeLink = $exist_whole[$matchId];

				$rank1 = !$kvmap['p1rank'] || $kvmap['p1rank'] == '-' || $kvmap['p1rank'] == 9999 ? null : intval($kvmap['p1rank']);
				$rank2 = !$kvmap['p2rank'] || $kvmap['p2rank'] == '-' || $kvmap['p2rank'] == 9999 ? null : intval($kvmap['p2rank']);

				$seed1 = @$kvmap['p1seed'] ? self::reviseEntry(@$kvmap['p1seed']) : null;
				$seed2 = @$kvmap['p2seed'] ? self::reviseEntry(@$kvmap['p2seed']) : null;

				$h2h = $kvmap['h2h'];

				$last_update = $kvmap['updatetime'];

				$status = $kvmap['mstatus'];

				$result1 = $result2 = '';
				$point1 = $point2 = null;
				$score1 = $score2 = ['', '', '', '', ''];
				$pointflag = '';

				$score_json = $kvmap['score'];

				if ($score_json) {

					$score = json_decode($score_json);
					if (count($score) != 2) {
						$score_json = '';
					} else {
						$score1 = $score[0];
						$result1 = array_shift($score1);
						$score2 = $score[1];
						$result2 = array_shift($score2);
					}

				}

				// status != 2时才会根据live_score进行修正
				if ($status != 2) {

					if (isset($live_info[$true_eid][$matchId])) {
						$score_json = $live_info[$true_eid][$matchId][0];
						if ($score_json) {
							$score = json_decode($score_json);
							if (count($score) != 2) {
								$score_json = '';
							} else {
								$score1 = $score[0];
								$result1 = array_shift($score1);
								$point1 = array_shift($score1);

								$score2 = $score[1];
								$result2 = array_shift($score2);
								$point2 = array_shift($score2);
							}
						}

						// 修正比赛时间
						if ($live_info[$true_eid][$matchId][1] != "") $dura = $live_info[$true_eid][$matchId][1] ? date('H:i', strtotime($live_info[$true_eid][$matchId][1])) : "";
						$pointflag = $live_info[$true_eid][$matchId][2];

						// 如果比赛结束，修正status
						if (strpos($result1 . $result2, 'iconfont') !== false || strpos($result1 . $result2, 'WINNER') !== false) {
							$status = 2;
							$pointflag = '';
						} else {
							$status = 1;
						}
					}
				}

				if ($point1 == 40 && ($point2 == "A" || $point2 == "AD")) {
					$point1 = "";
					$point2 = "A";
				}
				if ($point2 == 40 && ($point1 == "A" || $point1 == "AD")) {
					$point2 = "";
					$point1 = "A";
				}

				self::reviseResultFlag($result1);
				self::reviseResultFlag($result2);

				$winner = $inserve = null;
				if ($result1 == "Winner") {
					$winner = 1;
				} else if ($result2 == "Winner") {
					$winner = 2;
				} else if ($result1 == "Serve") {
					$inserve = 1;
				} else if ($result2 == "Serve") {
					$inserve = 2;
				}
					
				if (count($p1) == 2 || count($p2) == 2) {
					$is_double = 1;
				} else {
					$is_double = 0;
				}

				//$p1 = join('<br>', array_map('self::mergeName', $ioc1, $seed1, $p1, $rank1));
				//$p2 = join('<br>', array_map('self::mergeName', $ioc2, $seed2, $p2, $rank2));

				if (in_array($true_eid, ['M990', 'DC', '7696'])) $bestof = 5;
				else if (in_array($true_eid, ['AO', 'RG', 'WC', 'UO', 'M993', 'M994', 'M995', 'M996']) && substr($matchId, 0, 2) == 'MS') $bestof = 5;
				else if (in_array($true_eid, ['WC', 'M995']) && (substr($matchId, 0, 3) == 'QS3' || substr($matchId, 0, 2) == 'MD')) $bestof = 5;
				else $bestof = 3;

				$pointflag = self::revisePointFlag($pointflag);

				$statusClass = '';
				if ($show_status > -1 && $status != $show_status) {
					$statusClass = 1;
				} 

				if (isset($kvmap['matchid_bets'])) {
					$matchid_bets = $kvmap['matchid_bets'];
				} else {
					$matchid_bets = '';
				}

				$odds1 = @$kvmap['odd1_bets'];
				$odds2 = @$kvmap['odd2_bets'];

				$player1 = [
					'seed' => $seed1,
					'rank' => $rank1,
					'odd' => $odds1,
					'score' => self::reviewScore($score1, $mStatus),
					'point' => $point1,
					'p' => [],
				];
					
				$player2 = [
					'seed' => $seed2,
					'rank' => $rank2,
					'odd' => $odds2,
					'score' => self::reviewScore($score2, $mStatus),
					'point' => $point2,
					'p' => [],
				];

				$first1 = explode("/", @$kvmap['p1first']);
				$first2 = explode("/", @$kvmap['p2first']);
				$last1 = explode("/", @$kvmap['p1last']);
				$last2 = explode("/", @$kvmap['p2last']);

				// 解选手信息。如果id的格式为 |p1|p2 表示该id有p1和p2两个可能
				// 如果为 p1/p2 说明是双打。 |p1|p2/|p3|p4 也是双打，但是或者是p1/p3或者p2/p4
				$tmp_ar = explode("/", $id1);
				foreach ($tmp_ar as $tmp_k => $tmp_v) {
					if (strpos($tmp_v, "|") === false) {
						$namelong = translate2long($tmp_v, @$first1[$tmp_k], @$last1[$tmp_k], @$ioc1[$tmp_k]);
						$engnamelong = translate2long($tmp_v, @$first1[$tmp_k], @$last1[$tmp_k], @$ioc1[$tmp_k], 'en');
						if ($namelong === null) $namelong = $p1[$tmp_k];
						if ($engnamelong === null) $engnamelong = $p1[$tmp_k];
						$nameshort = translate2short($tmp_v, @$first1[$tmp_k], @$last1[$tmp_k], @$ioc1[$tmp_k]);
						$engnameshort = translate2short($tmp_v, @$first1[$tmp_k], @$last1[$tmp_k], @$ioc1[$tmp_k], 'en');
						if ($nameshort === null) $nameshort = $p1[$tmp_k];
						if ($engnameshort === null) $engnameshort = $p1[$tmp_k];
						$player1['p'][] = [
							'id' => $tmp_v,
							'name' => $namelong,
							'eng' => $engnamelong,
							'nameShort' => $nameshort,
							'engShort' => $engnameshort,
							'ioc' => @$ioc1[$tmp_k],
						];
					} else {
						$tmp_players_ids = explode("|", $tmp_v);
						$tmp_players_firsts = explode("|", @$first1[$tmp_k]);
						$tmp_players_lasts = explode("|", @$last1[$tmp_k]);
						$tmp_players_iocs = explode("|", @$ioc1[$tmp_k]);
						foreach ($tmp_players_ids as $tk => $tv) {
							if ($tk == 0) continue;
							$namelong = translate2long($tv, @$tmp_players_firsts[$tk], @$tmp_players_lasts[$tk], @$tmp_players_iocs[$tk]);
							$engnamelong = translate2long($tv, @$tmp_players_firsts[$tk], @$tmp_players_lasts[$tk], @$tmp_players_iocs[$tk], 'en');
							$nameshort = translate2short($tv, @$tmp_players_firsts[$tk], @$tmp_players_lasts[$tk], @$tmp_players_iocs[$tk]);
							$engnameshort = translate2short($tv, @$tmp_players_firsts[$tk], @$tmp_players_lasts[$tk], @$tmp_players_iocs[$tk], 'en');
							$player1['p'][$tmp_k]['possible'][] = [
								'id' => $tv,
								'name' => $namelong,
								'eng' => $engnamelong,
								'nameShort' => $nameshort,
								'engShort' => $engnameshort,
								'ioc' => @$tmp_players_iocs[$tk],
							];
						}
					}
				}

				$tmp_ar = explode("/", $id2);
				foreach ($tmp_ar as $tmp_k => $tmp_v) {
					if (strpos($tmp_v, "|") === false) {
						$namelong = translate2long($tmp_v, @$first2[$tmp_k], @$last2[$tmp_k], @$ioc2[$tmp_k]);
						$engnamelong = translate2long($tmp_v, @$first2[$tmp_k], @$last2[$tmp_k], @$ioc2[$tmp_k], 'en');
						if ($namelong === null) $namelong = $p2[$tmp_k];
						if ($engnamelong === null) $engnamelong = $p2[$tmp_k];
						$nameshort = translate2short($tmp_v, @$first2[$tmp_k], @$last2[$tmp_k], @$ioc2[$tmp_k]);
						$engnameshort = translate2short($tmp_v, @$first2[$tmp_k], @$last2[$tmp_k], @$ioc2[$tmp_k], 'en');
						if ($nameshort === null) $nameshort = $p2[$tmp_k];
						if ($engnameshort === null) $engnameshort = $p2[$tmp_k];
						$player2['p'][] = [
							'id' => $tmp_v,
							'name' => $namelong,
							'eng' => $engnamelong,
							'nameShort' => $nameshort,
							'engShort' => $engnameshort,
							'ioc' => @$ioc2[$tmp_k],
						];
					} else {
						$tmp_players_ids = explode("|", $tmp_v);
						$tmp_players_firsts = explode("|", @$first2[$tmp_k]);
						$tmp_players_lasts = explode("|", @$last2[$tmp_k]);
						$tmp_players_iocs = explode("|", @$ioc2[$tmp_k]);
						foreach ($tmp_players_ids as $tk => $tv) {
							if ($tk == 0) continue;
							$namelong = translate2long($tv, @$tmp_players_firsts[$tk], @$tmp_players_lasts[$tk], @$tmp_players_iocs[$tk]);
							$engnamelong = translate2long($tv, @$tmp_players_firsts[$tk], @$tmp_players_lasts[$tk], @$tmp_players_iocs[$tk], 'en');
							$nameshort = translate2short($tv, @$tmp_players_firsts[$tk], @$tmp_players_lasts[$tk], @$tmp_players_iocs[$tk]);
							$engnameshort = translate2short($tv, @$tmp_players_firsts[$tk], @$tmp_players_lasts[$tk], @$tmp_players_iocs[$tk], 'en');
							$player2['p'][$tmp_k]['possible'][] = [
								'id' => $tv,
								'name' => $namelong,
								'eng' => $engnamelong,
								'nameShort' => $nameshort,
								'engShort' => $engnameshort,
								'ioc' => @$tmp_players_iocs[$tk],
							];
						}
					}
				}

				$umpire = null;
				if (isset($kvmap["umpireid"]) && $kvmap["umpireid"] != "") {
					$umpire = [
						'p' => $kvmap["umpireid"],
						'f' => $kvmap["umpirefirst"],
						'l' => $kvmap["umpirelast"],
						'i' => $kvmap["umpireioc"],
						'name' => translate2long(null, $kvmap["umpirefirst"], $kvmap["umpirelast"], $kvmap["umpireioc"]),
					];
				}

				$result_tag = "";
				if ($mStatus == "H" || $mStatus == "I") {
					$result_tag = "Ret.";
				} else if ($mStatus == "J" || $mStatus == "K") {
					$result_tag = "Def.";
				} else if ($mStatus == "L" || $mStatus == "M") {
					$result_tag = "W/O";
				} else if ($mStatus == "Z") {
					$result_tag = "Abn.";
				} else if ($mStatus == "C") {
					$result_tag = "Interrupted";
				}

				$matchSeq = intval(@$kvmap["matchseq"]);
				@$courts[$courtname][$matchSeq] = [
					'matchid' => $matchId, //0
					'matchseq' => intval(@$kvmap["matchseq"]),
					'sex' => $sex, 
					'round' => $round, 
					'team1' => $player1, //5
					'team2' => $player2, 
					'h2h' => $h2h, 
					'dura' => $dura, 
					'start' => $time, 
					'winner' => $winner,
					'serving' => $inserve,
					'status' => $status, 
					'bestof' => $bestof, 
					'flag' => $pointflag, 
					'is_d' => $is_double, 
					'class_name' => $className, //20
					'h2h_link' => $h2hLink, 
					'stat_link' => $statLink, 
					'pbp_link' => $detailLink,
					'hidden_class' => $statusClass,
					'has_pbp' => $has_detail, // 25
					'hl_link' => $hlLink, 
					'whole_link' => $wholeLink, 
					'bets_id' => $matchid_bets,
					'gender' => $sextype,
					'fs_id' => @$kvmap['fsid'], //30
					'umpire' => $umpire,
					'result_tag' => $result_tag,
					'mStatus' => $mStatus,
					'true_eid' => $true_eid,
				];

			}

			uksort($courts, 'self::sortByCourtId');
		}

		unset($exist_hl);
		unset($exist_whole);

		return $courts;
	}

	protected function get_description($type, $idx) {

		$name = __($type . '.' . $idx);
		if (strpos($name, $type) === 0) {  //没匹配上
			return strtoupper($idx);
		} else {
			return $name;
		}
	}

	protected function reviseResults ($r1, $r2) {


	}

	protected function revisePointFlag ($pf) {

		if (!$pf) return '';

		$flags = [];

		$match_count = preg_match_all('/\{([^\}]+)\}/', $pf, $match);

		if ($match_count > 0) {
			foreach ($match[1] as $pattern) {
				$arr = explode("|", $pattern);
				$pflag = __('pointflag.' . $arr[0], ['p1' => @$arr[1], 'p2' => @$arr[2]]);
				if ($pflag != 'pointflag.' . $arr[0]) {
					$flags[] = $pflag;
				} else {
					$flags[] = $arr[0];
				}
			}
		} else {
			$pflag = __('pointflag.' . $pf);
			if ($pflag != 'pointflag.' . $pf) {
				$flags[] = $pflag;
			} else {
				$flags[] = $pf;
			}
		}
		return join(' | ', $flags);
	}

	protected function reviseResultFlag (&$re) {

		if (strpos($re, 'inserve') !== false || strpos($re, 'SERVE') !== false)
			$re = 'Serve';
		else if (strpos($re, 'iconfont') !== false || strpos($re, 'WINNER') !== false)
			$re = 'Winner';

	}

	protected function reviseEntry ($entry) {

		$entry = str_replace('WC', 'W', $entry);
		$entry = str_replace('LL', 'L', $entry);
		$entry = str_replace('Alt', 'A', $entry);
		$entry = str_replace('SE', 'S', $entry);
		$entry = str_replace('JE', 'J', $entry);
		$entry = str_replace('PR', 'P', $entry);
		$entry = str_replace('-', '/', $entry);
		return $entry;

	}

	protected function mergeName($v1, $v2, $v3, $v4) {

		if (!$v1) return $v3 . $v2 . $v4;
		else return get_flag($v1) . $v3 . $v2 . $v4;

	}

	protected function addPlayerSelect($v) {

		return 'cResultP_' . $v;

	}

	protected function sort_by_matchid($a, $b) {
		return $a['matchid'] < $b['matchid'] ? -1 : 1;
	}

	protected function sortByCourtId($a, $b) {
		return intval(explode("\t", $a)[0]) < intval(explode("\t", $b)[0]) ? -1 : 1;
	}

	public function hl_list($year) {

		$file = join("/", [env('ROOT'), 'store', 'calendar', $year, "[GW]*"]);
		$cmd = "cat $file";
		unset($r); exec($cmd, $r);

		$tours = [];
		foreach ($r as $row) {
			$arr = explode("\t", $row);
			unset($kvmap); $kvmap = [];
			foreach ($arr as $k => $v) $kvmap[Config::get('const.schema_calendar.' . $k)] = $v;

			$tours[] = [
				$kvmap['year'],
				$kvmap['date'],
				$kvmap['eid'],
				$kvmap['city'],
			];
		}

		return view('admin.hl.list', [
			'ret' => $tours,
		]);

	}

	public function hl_detail($year, $date, $eid, $city) {

		$ones = HighLight::where('year', $year)->where('date', $date)->where('eid', $eid)->get();
		foreach ($ones as $one) {
			$hl[join("\t", [$one->round, $one->p1id, $one->p2id])] = $one->hl;
			$whole[join("\t", [$one->round, $one->p1id, $one->p2id])] = $one->whole;
		}

		$file = array_map(function ($d) use ($date) {
			return date('Y-m-d', strtotime($date . " $d days"));
		}, range(-5,17));
			
		$cmd = "cd " . join("/", [env('ROOT'), 'share', 'completed']) . " && awk -F\"\\t\" '$22 == \"$eid\"{print FILENAME\"\\t\"$0}' " . join(" ", $file); 
		unset($r); exec($cmd, $r);

		$matches = [];
		foreach ($r as $row) {
			$arr = explode("\t", $row);
			$real_date = $arr[0];
			
			unset($kvmap); $kvmap = [];
			foreach ($arr as $k => $v) {
				if ($k == 0) continue;
				$kvmap[Config::get('const.schema_completed.' . ($k - 1))] = $v;
			}

			if (!in_array(substr(@$kvmap['matchid'], 0, 2), ['MS', 'MD', 'WS', 'LS', 'WD', 'LD', 'XD', 'PS', 'QS', 'PD', 'QD'])) continue;

			$round = $kvmap['round'];
			$p1id = $kvmap['p1id'];
			$p2id = $kvmap['p2id'];
			$p1name = $kvmap['p1chn'];
			$p2name = $kvmap['p2chn'];

			if (strpos($p1id, "/") !== false) {$ar = explode("/", $p1id); if ($ar[0] > $ar[1]) {swap($ar[0], $ar[1]); $p1id = join("/", $ar);}}
			if (strpos($p2id, "/") !== false) {$ar = explode("/", $p2id); if ($ar[0] > $ar[1]) {swap($ar[0], $ar[1]); $p2id = join("/", $ar);}}

			if ($p1id > $p2id) {
				swap($p1id, $p2id);
				swap($p1name, $p2name);
			}

			$matches[] = [
				'year' => $year,
				'date' => $date,
				'matchdate' => $real_date,
				'eid' => $eid,
				'city' => urldecode($city),
				'round' => $round,
				'p1id' => $p1id,
				'p2id' => $p2id,
				'p1name' => $p1name,
				'p2name' => $p2name,
				'hl' => @$hl[join("\t", [$round, $p1id, $p2id])],
				'whole' => @$whole[join("\t", [$round, $p1id, $p2id])],
				'matchid' => @$kvmap['matchid'],
			];
		}

		usort($matches, "self::sort_by_matchid");

		return view('admin.hl.detail', [
			'ret' => $matches,
		]);
	}

	public function hl_save(Request $req) {

		$param = $req->all();

		$one = HighLight::firstOrNew(
			[
				'year' => $param['year'],
				'date' => $param['date'],
				'matchdate' => $param['matchdate'],
				'matchid' => $param['matchid'],
				'eid' => $param['eid'],
				'city' => $param['city'],
				'round' => $param['round'],
				'p1id' => $param['p1id'],
				'p2id' => $param['p2id'],
			]
		);

		$one->p1name = $param['p1name'];
		$one->p2name = $param['p2name'];
		$one->hl = $param['hl'];
		$one->whole = $param['whole'];

		if (
			(
				$param['hl'] && (
					preg_match('/^https?:\/\//', $param['hl']) || $param['hl'] == "-"
				)
			) 
			|| 
			(
				$param['whole'] && (
					preg_match('/^https?:\/\//', $param['whole']) || $param['whole'] == "-"
				)
			)
		) {
			if ($one->hl == "-") $one->hl = NULL;
			if ($one->whole == "-") $one->whole = NULL;

			$one->save();
			return 1;
		} else {
			return 2;
		}
	}

	public function ByEid($lang, $eid, $year) {

		App::setLocale($lang);

		$cmd = "awk -F\"\\t\" '$2 == \"$eid\"' " . join("/", [env('ROOT'), 'store', 'calendar', $year, '*']) . " | head -1";
		unset($r); exec($cmd, $r);

		if (!$r) return;
		$arr = explode("\t", $r[0]);
		$level = $arr[0];
		$eid = $arr[1];
		$start = $arr[5];
		$city = translate_tour(trim(str_replace('.', '', strtolower($arr[9]))));
		$this->year = $year;

		$result = [];
		$tour_info = [];
		$min_timestamp = $max_timestamp = 0;

		foreach (range(-7, 15) as $offset) {
			$date = date('Y-m-d', strtotime($start . " $offset days"));
			$this->date = [$date];
			$this->files = join("/", [env('ROOT'), 'share', '*completed', $date]);

			$all_tours = [];
			self::get_tours($all_tours, false, 0, $eid);
			if (count($tour_info) == 0 && count($all_tours) > 0) {
				$tour_info = $all_tours[0];
				$tour_info[6] = null;
			}
			if (count($all_tours) > 0) {
				$result[$date] = $all_tours[0][6];
				$max_timestamp = strtotime($date . " 16:0:0") + 86400;
			}
			if ($min_timestamp === 0 && count($all_tours) > 0) {
				$min_timestamp = strtotime($date . " 6:0:0");
			}
		}
		krsort($result);

		$now = time();

		$route = 'result.schedule_index';

		return view($route, [
			'ret' => $result,
			'info' => $tour_info,
			'year' => $this->year,
			'eid' => $eid,
			'now' => $now,
			'timestamp' => [$min_timestamp, $max_timestamp],
			'pageTitle' => __('frame.menu.score') . " - " . $year . " " . $city,
			'title' => __('frame.menu.score') . " - " . $year . " " . $city,
			'pagetype1' => 'schedule',
			'pagetype2' => $city,
		]);

	}

	private function reviewScore($score_arr, $mStatus = "") {
		if ($mStatus == "L" || $mStatus == "M") {
			return [
				[null, null, null],
				[null, null, null],
				[null, null, null],
				[null, null, null],
				[null, null, null],
			];
		}
		$ret = [];
		foreach ($score_arr as $set) {
			if ($set === "") {
				$ret[] = [null, null, null];
			} else {
				$lose = false;
				if (strpos($set, 'loser') !== false) {
					$lose = true;
					$set = preg_replace('/<\/?span[^>]*>/', '', $set);
				}
				if (strpos($set, 'sup') !== false) {
					$set = str_replace("<sup>", "\t", $set);
				}
				$ar = explode("\t", $set);
				$s = intval($ar[0]);
				if (isset($ar[1])) {
					$tb = intval($ar[1]);
				} else {
					$tb = null;
				}
				$ret[] = [$s, $tb, $lose];
			}
		}
		return $ret;
	}
}
