<?php

namespace App\Http\Controllers\Result;

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
		$this->year = 2020;

		$now = time();

		$all_matches = [];

		self::get_matches($all_matches, true, 0);

		return view('result.index', [
			'ret' => $all_matches,
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
	public function date($lang, $date) {

		$tic = time();
		App::setLocale($lang);

		$this->date = [$date];
		$this->files = $this->path . "/" . $date;
		$this->year = date('Y', strtotime($date . " +4 days"));

		$now = time();

		$all_matches = [];

		self::get_matches($all_matches, false, 0);

		$min_timestamp = strtotime($date . " 6:0:0");
		$max_timestamp = strtotime($date . " 16:0:0") + 86400;

//		return json_encode($all_matches, JSON_UNESCAPED_UNICODE);

		$route = 'result.index';

		return view($route, [
			'ret' => $all_matches,
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

		$all_matches = [];

		self::get_matches($all_matches, false, $unixtime);

		$min_timestamp = strtotime($date . " 6:0:0");
		$max_timestamp = strtotime($date . " 16:0:0") + 86400;

//		echo json_encode($all_matches, JSON_UNESCAPED_UNICODE);

		$route = 'result.oop_matches';

		$date = date('Y-m-d', strtotime($date));

		return view($route, [
			'ret' => $all_matches,
			'date' => $date,
			'year' => $this->year,
			'now' => $now,
			'timestamp' => [$min_timestamp, $max_timestamp],
			'pageTitle' => __('frame.menu.score') . " " . $date,
			'title' => __('frame.menu.score') . " " . $date,
		]);
	
	}

	// 显示某天单个赛事
	public function eid(Request $req, $lang, $date) {

		App::setLocale($lang);

		$this->date = [$date];
		$this->files = $this->path . "/" . $date;
		$this->year = date('Y', strtotime($date . " 2 days ago"));
		$is_tournament = 1;

		$eid = $req->input('eid');
		$show_status = $req->input('show_status', -1);

		$all_matches = [$eid, '', '', $is_tournament, '', '', self::get_init_tours($eid, $is_tournament, $show_status, 0)];

		return view('result.content', [
			'tour' => $all_matches,
			'date' => $date,
			'year' => $this->year]);
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
		$all_matches = [$eid, '', '', $is_tournament, '', '', self::get_init_tours($eid, $is_tournament, $show_status, $unixtime)];
		return view('result.content', [
			'tour' => $all_matches,
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

	protected function get_matches(&$ret, $only_live, $unixtime, $true_eid = null) {

		/*
			$ret 用于返回结果 
			$only_live 是否为live页
			$unixtime 当地的0点的unixtime
			$true_eid 为draw系统里的eid。若为空，则是分日赛程，不为空则是分站赛程
		*/

		// 获取赛事列表
		if ($only_live) {
			$cmd = "cat $this->files | awk -F \"\\t\" '$30 < 2' | cut -f4-8,22 | sort -uf | sort -k1gr,1";
		} else {
			if ($unixtime > 0) {
				$cmd = "cat $this->files | awk -F\"\\t\" 'substr($14,length($14)-9) >= " . $unixtime . "-5400 && substr($14,length($14)-9) <= " . $unixtime . "+86400' | cut -f4-8,22 | sort -uf | sort -k1gr,1";
			} else {
				$cmd = "cat $this->files | cut -f4-8,22 | sort -uf | sort -k1gr,1";
			}
		}

		if ($true_eid !== null) {
			$cmd .= " | awk -F\"\\t\" '$6 == \"$true_eid\" || $2 ~ / $true_eid-" . $this->year . "/'";
		}
		unset($r); exec($cmd, $r);

		if ($r) {

			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if (count($arr) != 6) {
					continue;
				} else {

					$eid = $arr[5];
					$city = translate_tour(trim(str_replace('.', '', strtolower($arr[2]))));
					$title = $arr[1];
					$prize = $arr[0];
					$sfc = reviseSurfaceWithoutIndoor($arr[3]);
//					$sfc = Config::get('const.groundColor.' . $sfc);
					$levels = explode("/", $arr[4]);
					$logos = [];
					foreach ($levels as $level) {
						$logos[] = get_tour_logo_by_id_type_name($eid, $level, $city, $title);
					}
					$is_tournament = true;

					// 如果是live模式，恒展示
					if (!$only_live) {
						if (strpos($arr[4], "Challenger") !== false || strpos($arr[4], "CH ") !== false) {
							$pr = intval(preg_replace('/[^\d\.]/', '', $arr[4]));
/*
							if ($pr <= 40) $pr = 50;
							else if ($pr <= 60) $pr = 80;
							else if ($pr <= 90) $pr = 90;
							else if ($pr <= 110) $pr = 100;
							else if ($pr <= 136) $pr = 110;
							else $pr = 125;
*/
							$city = 'CH' . $pr . ' ' . $city;
						} else if (strpos($arr[4], "125K") !== false) {
							$city = '125K' . ' ' . $city;
						} else if (strpos($arr[4], "ITF") !== false) {
							$suffix = ""; if (strpos($arr[4], "+H") !== false) $suffix = "+H";
							$pr = intval(preg_replace('/[^\d]/', '', $arr[4]));
							if (strpos($eid, "M-ITF") !== false) $gen = "M"; else $gen = "W";
							$city = $gen . $pr . $suffix . ' ' . $city;
							if ($prize < 40000 && $true_eid === null) {
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

					$ret[] = [$eid, $city, $sfc, (int)$is_tournament, $title, $logos, self::get_init_tours($eid, $is_tournament, $show_status, $unixtime), $eventid, $year];
				}
			}
		}
	}

	protected function get_init_tours($eid, $is_tournament, $show_status = -1, $unixtime) {

		if (!$is_tournament) return [];

		return self::get_tours($eid, $show_status, $unixtime);

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
		if (strpos($result1 . $result2, 'iconfont') !== false || strpos($result1 . $result2, 'WINNER') !== false) {
			$status = 2;
			$pointflag = '';
		} else {
			$status = 1;
		}

		$pointflag = self::revisePointFlag($pointflag);

		self::reviseResultFlag($result1);
		self::reviseResultFlag($result2);

		return [$status, $result1, $result2, $score1, $score2, $point1, $point2, $dura, $pointflag];
	}

	protected function get_tours($eid, $show_status, $unixtime) {

		$file = $this->down_path . '/live_score*';

		$cmd = "awk -F \"\\t\" '$22 == \"$eid\"' $file";
		unset($r); exec($cmd, $r);

		$live_info = [];

		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$matchId = $arr[0];
				@$live_info[$matchId] = [$arr[16], $arr[17], $arr[29], $arr[28]];  // score, time, pointflag, updatetime
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
			$cmd = "awk -F \"\\t\" '$22 == \"$eid\"' $this->files";
		} else {
			$cmd = "awk -F \"\\t\" '$22 == \"$eid\" && substr($14,length($14)-9) >= " . $unixtime . "-5400 && substr($14,length($14)-9) <= " . $unixtime . "+86400' $this->files";
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

				$sex = translate('sexname', $kvmap['sexid']);
				$has_detail = true;
//				if ($kvmap['sexid'] < 5 || $kvmap['sexid'] > 20) $has_detail = true; else $has_detail = false;

				$round = translate('roundname', $kvmap['round']);

/*
				if (in_array(substr($matchId, 0, 2), ['MS', 'QS', 'MD', 'QD', 'M-'])) {
					$sextype = 'atp';
				} else if (in_array(substr($matchId, 0, 2), ['WS', 'LS', 'RS', 'PS', 'WD', 'PD', 'RD', 'W-'])) {
					$sextype = 'wta';
				} else if (strpos($kvmap['tour'], "M-FU-") !== false || strpos($kvmap['tour'], "M-ITF-") !== false) {
					$sextype = 'atp';
				} else if (strpos($kvmap['tour'], "W-WITF-") !== false || strpos($kvmap['tour'], "W-ITF-") !== false) {
					$sextype = 'wta';
				} else if ($eid == "DC") {
					$sextype = 'atp';
				} else if ($eid == "FC") {
					$sextype = 'wta';
				} else {
					$sextype = '';
				}
*/
				if (in_array($kvmap['sexid'], [0, 2])) {
					$sextype = 'atp';
				} else if (in_array($kvmap['sexid'], [1, 3])) {
					$sextype = 'wta';
				} else if (strpos($kvmap['tour'], "M-FU-") !== false || strpos($kvmap['tour'], "M-ITF-") !== false) {
					$sextype = 'atp';
				} else if (strpos($kvmap['tour'], "W-WITF-") !== false || strpos($kvmap['tour'], "W-ITF-") !== false) {
					$sextype = 'wta';
				} else if ($eid == "DC") {
					$sextype = 'atp';
				} else if ($eid == "FC") {
					$sextype = 'wta';
				} else {
					$sextype = '';
				}

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
						if (@$kvmap['p'.$i.'first'] . @$kvmap['p'.$i.'last'] . @$kvmap['p'.$i.'ioc'] == "") continue;
						$first_arr = explode("/", @$kvmap['p'.$i.'first']);
						$last_arr = explode("/", @$kvmap['p'.$i.'last']);
						$ioc_arr = explode("/", @$kvmap['p'.$i.'ioc']);
						$id_arr = explode("/", @$kvmap['p'.$i.'id']);
						$tmpP = [];
						foreach ($first_arr as $k => $v) {
							if (strpos($v, '|') === false) {
//								$tmpP[] = rename2long($v, @$last_arr[$k], @$ioc_arr[$k]);
								$tmpP[] = translate2long(@$id_arr[$k], $v, @$last_arr[$k], @$ioc_arr[$k]);
							} else {
								$tmp = [];
								$f_arr = explode("|", $first_arr[$k]);
								$l_arr = explode("|", $last_arr[$k]);
								$i_arr = explode("|", @$ioc_arr[$k]);
								$p_arr = explode("|", @$id_arr[$k]);
								foreach ($f_arr as $k1 => $v1) {
									if ($k1 == 0) continue;
//									$tmp[] = rename2short($v1, $l_arr[$k1], @$i_arr[$k1]);
									$possiblePid = @$p_arr[$k1];
									if (preg_match('/^[A-Z0-9]{4,6}$/', $possiblePid)) {
										$tmp[] = translate2short($possiblePid, $v1, $l_arr[$k1], @$i_arr[$k1]);
									} else {
										$tmp[] = translate2short(null, $v1, $l_arr[$k1], @$i_arr[$k1]);
									}
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
					$h2hLink = 'open_h2h("' . $eid . '", "' . $sextype . '", "' . $matchId . '", "' . $this->year . '", "' . $id1 . '", "' . $id2 . '", "' . join('/', $p1) . '", "' . join('/', $p2) . '", "' . $sd . '")';
				} else {
					$h2hLink = '';
				}

				$statLink = 'open_stat("' . $eid . '", "' . $sextype . '", "' . $matchId . '", "' . $this->year . '", "' . $id1 . '", "' . $id2 . '", "' . join('/', $p1) . '", "' . join('/', $p2) . '")';

				if ($has_detail) {
					$detailLink = 'open_detail("' . @$kvmap['fsid'] . '", "' . $eid . '", "' . $sextype . '", "' . $matchId . '", "' . $this->year . '", "' . $id1 . '", "' . $id2 . '", "' . join('/', $p1) . '", "' . join('/', $p2) . '")';
				} else {
					$detailLink = "";
				}

				$hlLink = "";
				if (isset($exist_hl[$matchId])) $hlLink = $exist_hl[$matchId];
				$wholeLink = "";
				if (isset($exist_whole[$matchId])) $wholeLink = $exist_whole[$matchId];

				$rank1 = !$kvmap['p1rank'] || $kvmap['p1rank'] == '-' || $kvmap['p1rank'] == 9999 ? '' : '<sub> ' . $kvmap['p1rank'] . '</sub>';
				$rank2 = !$kvmap['p2rank'] || $kvmap['p2rank'] == '-' || $kvmap['p2rank'] == 9999 ? '' : '<sub> ' . $kvmap['p2rank'] . '</sub>';
				$rank1 = [$rank1]; $rank2 = [$rank2];

				$seed1 = @$kvmap['p1seed'] ? '<sub> [' . self::reviseEntry(@$kvmap['p1seed']) . ']</sub>' : '';
				$seed2 = @$kvmap['p2seed'] ? '<sub> [' . self::reviseEntry(@$kvmap['p2seed']) . ']</sub>' : '';
				$seed1 = [$seed1]; $seed2 = [$seed2];

				$h2h = $kvmap['h2h'];

				$last_update = $kvmap['updatetime'];

				$status = $kvmap['mstatus'];

				$result1 = $result2 = '';
				$point1 = $point2 = '';
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

					if (isset($live_info[$matchId])) {
						$score_json = $live_info[$matchId][0];
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
						if ($live_info[$matchId][1] != "") $dura = $live_info[$matchId][1] ? date('H:i', strtotime($live_info[$matchId][1])) : "";
						$pointflag = $live_info[$matchId][2];

						// 如果比赛结束，修正status
						if (strpos($result1 . $result2, 'iconfont') !== false || strpos($result1 . $result2, 'WINNER') !== false) {
							$status = 2;
							$pointflag = '';
						} else {
							$status = 1;
						}
					}
				}

				self::reviseResultFlag($result1);
				self::reviseResultFlag($result2);

				if (count($p1) == 2) {$seed1 = array_merge($seed1, $seed1); $rank1 = array_merge($rank1, $rank1); } 
				if (count($p2) == 2) {$seed2 = array_merge($seed2, $seed2); $rank2 = array_merge($rank2, $rank2); } 
				if (count($p1) == 2 || count($p2) == 2) {
					$is_double = 1;
				} else {
					$is_double = 0;
				}

				$p1 = join('<br>', array_map('self::mergeName', $ioc1, $seed1, $p1, $rank1));
				$p2 = join('<br>', array_map('self::mergeName', $ioc2, $seed2, $p2, $rank2));

				if (in_array($eid, ['M990', 'DC', '7696'])) $bestof = 5;
				else if (in_array($eid, ['AO', 'RG', 'WC', 'UO', 'M993', 'M994', 'M995', 'M996']) && substr($matchId, 0, 2) == 'MS') $bestof = 5;
				else if (in_array($eid, ['WC', 'M995']) && (substr($matchId, 0, 3) == 'QS3' || substr($matchId, 0, 2) == 'MD')) $bestof = 5;
				else $bestof = 3;

				$pointflag = self::revisePointFlag($pointflag);

				$statusClass = '';
				if ($show_status > -1 && $status != $show_status) {
					$statusClass = 'cResultHidden';
				} 

				if (isset($kvmap['matchid_bets'])) {
					$matchid_bets = $kvmap['matchid_bets'];
				} else {
					$matchid_bets = '';
				}

				if (isset($kvmap['odd1_bets'], $kvmap['odd2_bets'])) {
					$odds = join('-', [$kvmap['odd1_bets'], $kvmap['odd2_bets']]);
				} else {
					$odds = '';
				}

				$umpire = null;
				if (isset($kvmap["umpireid"]) && $kvmap["umpireid"] != "") {
					$umpire = [
						'p' => $kvmap["umpireid"],
						'f' => $kvmap["umpirefirst"],
						'l' => $kvmap["umpirelast"],
						'i' => $kvmap["umpireioc"],
					];
				}

				@$courts[$courtname][] = [
					$matchId, //0
					$sex, 
					$round, 
					$id1, 
					$id2, 
					$p1, //5
					$p2, 
					$h2h, 
					$dura, 
					$time, 
					$result1, //10
					$result2, 
					$point1, 
					$point2, 
					$score1,
					$score2, //15
					$status, 
					$bestof, 
					$pointflag, 
					$is_double, 
					$className, //20
					$h2hLink, 
					$statLink, 
					$detailLink,
					$statusClass,
					$has_detail, // 25
					$hlLink, 
					$wholeLink, 
					$matchid_bets,
					$odds,
					$umpire, // 30
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
				$flags[] = __('pointflag.' . $arr[0], ['p1' => @$arr[1], 'p2' => @$arr[2]]);
			}
		} else {
			$flags[] = __('pointflag.' . $pf);
		}
		return join('/', $flags);
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

		return 'cResultPlayer' . $v;

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

			$all_matches = [];
			self::get_matches($all_matches, false, 0, $eid);
			if (count($tour_info) == 0 && count($all_matches) > 0) {
				$tour_info = $all_matches[0];
				$tour_info[6] = null;
			}
			if (count($all_matches) > 0) {
				$result[$date] = $all_matches[0][6];
				$max_timestamp = strtotime($date . " 16:0:0") + 86400;
			}
			if ($min_timestamp === 0 && count($all_matches) > 0) {
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
}
