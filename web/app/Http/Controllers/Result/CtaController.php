<?php

namespace App\Http\Controllers\Result;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;
use Route;

class CtaController extends Controller
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
		$this->date = join(' ', [$yesterday, $today, $tomorrow]);
		$this->files = join(' ', [$this->path."/".$yesterday, $this->path."/".$today, $this->path."/".$tomorrow]);
		$this->year = 2019;

		$now = time();

		$all_matches = [];

		self::get_matches($all_matches, true);

		return view('result.cta', [
			'ret' => $all_matches,
			'date' => $this->date,
			'year' => $this->year,
			'now' => $now,
			'timestamp' => [0, 20000000000],
			'show_status' => 1,
			'pageTitle' => __('frame.menu.live'),
			'title' => __('frame.menu.live'),
			'pagetype1' => 'live',
			'pagetype2' => 'cta',
		]);

	}

	// 显示一天的比赛。默认只显示巡回赛
	public function date($lang, $date) {

		App::setLocale($lang);

		$this->date = $date;
		$this->files = $this->path . "/" . $date;
		$this->year = date('Y', strtotime($date . " +3 days"));

		$now = time();

		$all_matches = [];

		self::get_matches($all_matches);

		$min_timestamp = strtotime($date . " 6:0:0");
		$max_timestamp = strtotime($date . " 16:0:0") + 86400;

//		return json_encode($all_matches, JSON_UNESCAPED_UNICODE);

		$route = 'result.cta';

		return view($route, [
			'ret' => $all_matches,
			'date' => $date,
			'year' => $this->year,
			'now' => $now,
			'timestamp' => [$min_timestamp, $max_timestamp],
			'pageTitle' => __('frame.menu.score') . " " . $date,
			'title' => __('frame.menu.score') . " " . $date,
			'pagetype1' => 'ctaresult',
			'pagetype2' => $date,
		]);
	}

	// 显示某天单个赛事
	public function eid(Request $req, $lang, $date) {

		App::setLocale($lang);

		$this->date = $date;
		$this->files = $this->path . "/" . $date;
		$this->year = date('Y', strtotime($date . " 2 days ago"));
		$is_tournament = 1;

		$eid = $req->input('eid');
		$show_status = $req->input('show_status', -1);

		$all_matches = [$eid, '', '', $is_tournament, '', '', self::get_init_tours($eid, $is_tournament, $show_status)];

		return view('result.ctacontent', [
			'tour' => $all_matches,
			'date' => $date,
			'year' => $this->year]);
	}

	// 即时比分ajax
	public function get_live($lang, $ts = NULL) {

		if ($ts && ($ts - time() < -30 || $ts - time() > 15)) {
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

	protected function get_matches(&$ret, $only_live = false) {

		if ($only_live) {
			$cmd = "cat $this->files | awk -F \"\\t\" '$30 < 2' | cut -f4-8,22 | sort -uf | sort -k1gr,1";
		} else {
			$cmd = "cat $this->files | cut -f4-8,22 | sort -uf | sort -k1gr,1";
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
					$sfc = Config::get('const.groundColor.' . $sfc);
					$levels = explode("/", $arr[4]);
					$logos = [];
					foreach ($levels as $level) {
						$logos[] = get_tour_logo_by_id_type_name($eid, $level, $city, $title);
					}

					$is_tournament = true;

					// 如果是live模式，恒展示
					if (!$only_live) {
						if (strpos($arr[4], "Challenger") !== false) {
							$city .= '(' . str_replace("ATP Challengers ", "", $arr[4]) . ')';
							$is_tournament = false;
						} else if (strpos($arr[4], "WTA 125K") !== false) {
							$city .= '($' . ($prize / 1000) . 'K)';
							$is_tournament = false;
						} else if (strpos($arr[4], "ITF") !== false) {
							$city .= '(' . str_replace("ITF ", "", $arr[4]) . ')';
							$is_tournament = false;
						}
						$show_status = -1;
					} else {
						$show_status = 1;
					}

					$ret[] = [$eid, $city, $sfc, (int)$is_tournament, $title, $logos, self::get_init_tours($eid, $is_tournament, $show_status)];
				}
			}
		}
	}

	protected function get_init_tours($eid, $is_tournament, $show_status = -1) {

		if (!$is_tournament) return [];

		return self::get_tours($eid, $show_status);

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

	protected function get_tours($eid, $show_status) {

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

		$cmd = "awk -F \"\\t\" '$22 == \"$eid\"' $this->files";
		unset($r); exec($cmd, $r);

		$courts = [];

		if ($r) {

			foreach ($r as $row) {

				$arr = explode("\t", $row);
				$schema = Config::get('const.schema_completed');
				foreach ($arr as $k => $v) $kvmap[Config::get('const.schema_completed.' . $k)] = $v;

				$courtname = $kvmap['courtseq'] . "\t" . translate('courtname', str_replace('.', '', strtolower($kvmap['courtname'])), true);

				$matchId = $kvmap['matchid'];

				$sex = translate('sexname', $kvmap['sexid']);
				$has_detail = true;
//				if ($arr[0] < 5 || $arr[0] > 20) $has_detail = true; else $has_detail = false;

				$round = translate('roundname', $arr[12]);

				if (in_array(substr($matchId, 0, 2), ['MS', 'QS', 'MD', 'QD', 'M-'])) {
					$sextype = 'atp';
				} else if (in_array(substr($matchId, 0, 2), ['WS', 'LS', 'RS', 'PS', 'WD', 'LD', 'PD', 'RD', 'W-'])) {
					$sextype = 'wta';
				} else if (strpos($arr[4], "M-FU-") !== false) {
					$sextype = 'atp';
				} else if (strpos($arr[4], "W-WITF-") !== false) {
					$sextype = 'wta';
				} else if ($eid == "DC") {
					$sextype = 'atp';
				} else if ($eid == "FC") {
					$sextype = 'wta';
				} else {
					$sextype = '';
				}

				$time = "";
				if ($arr[13] != "") {
					if (strpos($arr[13], "[") === false) {
						$schedule_json = explode(",", $arr[13]);
					} else {
						$schedule_json = json_decode($arr[13], true);
					}
					if (count($schedule_json) == 1 && $schedule_json[0] == "Followed By") {
						$time = __('result.notice.followby');
					} else if (count($schedule_json) == 5) {
						$time = $schedule_json[1] . "|" . $schedule_json[4];
					} else if (count($schedule_json) == 6) {
						$time = $schedule_json[5];
					}
				}

				if (App::isLocale('zh')) {
					$p1 = explode("/", $arr[18]);
					$p2 = explode("/", $arr[19]);
				} else {
					$p1 = explode("/", $arr[14]);
					$p2 = explode("/", $arr[15]);
				}

				$ioc1 = explode("/", $arr[16]);
				$ioc2 = explode("/", $arr[17]);

				$dura = $arr[20] ? date('H:i', strtotime($arr[20])) : "";

				$id1 = explode("/", $arr[22]);
				$id2 = explode("/", $arr[23]);
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
					$detailLink = 'open_detail("' . @$arr[32] . '", "' . $eid . '", "' . $sextype . '", "' . $matchId . '", "' . $this->year . '", "' . $id1 . '", "' . $id2 . '", "' . join('/', $p1) . '", "' . join('/', $p2) . '")';
				} else {
					$detailLink = "";
				}

				$rank1 = !$arr[24] || $arr[24] == '-' || $arr[24] == 9999 ? '' : '<sub> ' . $arr[24] . '</sub>';
				$rank2 = !$arr[25] || $arr[25] == '-' || $arr[25] == 9999 ? '' : '<sub> ' . $arr[25] . '</sub>';
				$rank1 = [$rank1]; $rank2 = [$rank2];

				$seed1 = @$arr[30] ? '<sub> [' . self::reviseEntry(@$arr[30]) . ']</sub>' : '';
				$seed2 = @$arr[31] ? '<sub> [' . self::reviseEntry(@$arr[31]) . ']</sub>' : '';
				$seed1 = [$seed1]; $seed2 = [$seed2];

				$h2h = $arr[27];

				$last_update = $arr[28];

				$status = $arr[29];

				$result1 = $result2 = '';
				$point1 = $point2 = '';
				$score1 = $score2 = ['', '', '', '', ''];
				$pointflag = '';

				$score_json = $arr[26];

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
				if (count($p1) == 2) {
					$is_double = 1;
				} else {
					$is_double = 0;
				}

				foreach ([1, 2] as $i) {
					if (isset($kvmap['p'.$i.'first']) || isset($kvmap['p'.$i.'last']) || isset($kvmap['p'.$i.'ioc'])) {
						$first_arr = explode("/", @$kvmap['p'.$i.'first']);
						$last_arr = explode("/", @$kvmap['p'.$i.'last']);
						$ioc_arr = explode("/", @$kvmap['p'.$i.'ioc']);
						$tmpP = [];
						foreach ($first_arr as $k => $v) {
							if (strpos($v, '|') === false) {
								$tmpP[] = rename2long($v, $last_arr[$k], @$ioc_arr[$k]);
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
						${'p'.$i} = $tmpP;
					}
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
					"",
					"",
				];

			}

		}

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
}
