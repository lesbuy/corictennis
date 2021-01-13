<?php

namespace App\Http\Controllers\Stat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;

class PbPController extends Controller
{

	protected $fsid;
	protected $id1;
	protected $id2;
	protected $p1;
	protected $p2;
	protected $eid;
	protected $matchid;
	protected $year;

	public function query(Request $req, $lang) {

		App::setLocale($lang);

		$this->fsid = $req->input('fsid', '');
		$this->id1 = $req->input('id1', 'CG80');
		$this->id2 = $req->input('id2', 'N409');
		$this->p1 = urldecode($req->input('p1', 'Coric'));
		$this->p2 = urldecode($req->input('p2', 'Nadal'));
		$this->eid = $req->input('eid', 'M993');
		$this->matchid = $req->input('matchid', 'MS001');
		$this->year = $req->input('year', '2017');

		$ajax = $req->input('ajax', false);

		$ret = ['status' => -1, 'errmsg' => __('pbp.notice.error')];

		if ($ret["status"] < 0 && $this->fsid != '') {

			$ret = self::process_flashscore();

		}

		if ($ret["status"] < 0 && in_array($this->eid, ['UO', 'WC'])) {

			$ret = self::process_grandslam();

		}

		if ($ret["status"] < 0 && $this->eid > 40000 && $this->eid < 200000) {

			$ret = self::process_itf_event();

		}

		$ret['head'] = [];

		$join_id = explode('/', join('/', [$this->id1, $this->id2]));

		foreach ($join_id as $id) {
			if (preg_match('/^[0-9]{5,6}$/', $id)) {
				$gender = "wta";
			} else {
				$gender = "atp";
			}
			$res = fetch_headshot($id, $gender);
			$ret['head'][] = $res[1];
		}

		$ret['player'] = [$this->p1, $this->p2];

		if ($ajax) {
			return $ret;
		} else {
			return view('stat.pbp', ['ret' => $ret]);
		}
	}

	protected function process_flashscore() {

		$pbp = [];
		$param = [];
		$serve = [];

		$url = "http://d.livescore.in/x/feed/d_mh_".$this->fsid."_en_4";
		$headers = [
			'Referer: http://d.livescore.in/x/feed/proxy-local',
			'X-Fsign: SW9D1eZo',
		];
		//初始化
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url);
		$html = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($response_code > 400) return ['status' => -1, 'errmsg' => __('pbp.notice.error')];

//		$html = file_get_contents("/home/ubuntu/web/1.php");
		if (!$html) return ['status' => -1, 'errmsg' => __('pbp.notice.error')];
		$DOM = str_get_html($html);
		if (!$DOM) return ['status' => -1, 'errmsg' => __('pbp.notice.error')];

		$set_begin = false;
		$server = $winner = 0;

		$in_progress = false;

		$smallDot = 5;
		$bigDot = 15;

		$set = 0;

		foreach ($DOM->find('.parts-first') as $SET) {

			++$set;

			$game1 = $game2 = 0;
			$point1 = $point2 = 0;
			$x = 0; $y = 0; // x表示第几分，y增大或者减少，表示p1或者p2得分

/*----------------------第一次输出pbp,param,serve---------------------*/
			$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
			$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
			$serve[$set] = [];
/*---------------------------------------------------------------------*/

			$last_x = 0;
			$last_key = count($SET->find('tr')) - 1;

			$tb_begin = false;
			$in_progress = false;
			foreach ($SET->find('tr') as $key => $line) {

				if (strpos($line->innertext, "Point by point") !== false) {
					$set_begin = true;
					continue;
				}

				if (strpos($line->innertext, "Tiebreak") !== false && $set_begin) {
					$tb_begin = true;
					$point1 = $point2 = 0;
					continue;
				}

				$class = $line->class;
				if (!$class || $class == "current-game-empty-row") continue;
				$class = str_replace("odd", "", $class);
				$class = str_replace("even", "", $class);
				$class = trim($class);

				if ($class == "fifteens_available" || strpos($line->innertext, "Current game") !== false) { // 一局总结

					$server = 0;
					if (strpos($line->innertext, "Current game") !== false) {
						if (strpos($line->children(0)->innertext, 'visible') !== false && strpos($line->children(2)->innertext, 'visible') === false) {
							$server = 1;
						} else if (strpos($line->children(2)->innertext, 'visible') !== false && strpos($line->children(0)->innertext, 'visible') === false) {
							$server = 2;
						}
					} else {
						if ($line->children(1)->innertext != "" && $line->children(3)->innertext == "") {
							$server = 1;
						} else if ($line->children(3)->innertext != "" && $line->children(1)->innertext == "") {
							$server = 2;
						}
					}

					// 当前game正在进行时，winner置0，否则置1或2
					if (strpos($line->innertext, "Current game") !== false) {
						$winner = 0;
					} else if (strpos($line->innertext, "LOST SERVE") !== false) {
						$winner = 3 - $server;
					} else {
						$winner = $server;
					}

					// 本局已结束时才记录game1, game2
					if ($winner > 0) {
						$tmp = $line->children(2)->innertext;
						$tmp = preg_replace('/<[^>]*>/', "", $tmp);
						$tmp_arr = explode("-", $tmp);
						$game1 = trim($tmp_arr[0]) + 0;
						$game2 = trim($tmp_arr[1]) + 0;
					}

					$point1 = $point2 = 0;

					if (strpos($line->innertext, "Current game") !== false) {
						$in_progress = true;
					} else {
						$in_progress = false;
					}

				} else if ($class == "fifteen") { // 每发球局得分

					$tmp = $line->children(0)->innertext;
					$tmp_arr = explode(",", $tmp);
					foreach ($tmp_arr as $eachpoint) {
						++$x;
						$bp = $sp = $mp = false;
						if (strpos($eachpoint, "BP") !== false) $bp = true;
						if (strpos($eachpoint, "SP") !== false) $sp = true;
						if (strpos($eachpoint, "MP") !== false) $mp = true;
						$eachpoint = preg_replace('/<[^>]*>/', "", $eachpoint);
						$eachpoint = preg_replace('/[BSM]P/', "", $eachpoint);
						$eachpoint = preg_replace('/A/', "50", $eachpoint);
						if ($eachpoint == '0:0') continue;

						$ep_arr = explode(":", $eachpoint);

						if (trim($ep_arr[0]) == $point1) {
							if (trim($ep_arr[1]) > $point2) { // p2增大，算p2得分
								++$y;
							} else { // p2减少，从ad变成40，算p1得分
								--$y;
							}
						} else if (trim($ep_arr[1]) == $point2) {
							if (trim($ep_arr[0]) > $point1) { // p1增大，算p1得分
								--$y;
							} else {
								++$y;
							}
						}
						if ($y > $param[$set]['max']) $param[$set]['max'] = $y;
						else if ($y < $param[$set]['min']) $param[$set]['min'] = $y;

						$dotSize = $smallDot;
						$dotValue = [];
						if ($bp || $sp || $mp) {
							$dotSize = $bigDot;
							if ($bp) $dotValue[] = 'BP';
							if ($sp) $dotValue[] = 'SP';
							if ($mp) $dotValue[] = 'MP';
						}

						$point1 = trim($ep_arr[0]);
						$point2 = trim($ep_arr[1]);

/*-----------------------------每分都输出pbp----------------------------*/
						$pbp[$set][] = [$x, $y, $dotSize, $dotValue, str_replace("50", "AD", $point1).'-'.str_replace("50", "AD", $point2)];
/*--------------------------------------------------------------------*/

					} // foreach eachpoint

					// winner > 0 表示本局结束，此时在局尾增加一分，并记下色块
					if (!$in_progress) {

						++$x;
						if ($winner == 1) {
							--$y;
						} else if ($winner == 2) {
							++$y;
						}
						if ($y > $param[$set]['max']) $param[$set]['max'] = $y;
						else if ($y < $param[$set]['min']) $param[$set]['min'] = $y;

						if ($winner == 1) {
							$color = Config::get('const.sideColor.home');
						} else {
							$color = Config::get('const.sideColor.away');
						}

/*----------------------每一局结束时输出pbp,输出markArea---------------------*/
						$pbp[$set][] = [$x, $y, $smallDot, [], ''];
						$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $color];  // 表示从last_x到x这段范围的局分，以及底色
/*--------------------------------------------------------------------*/
					}

					if ($server == 1) {
						$color = Config::get('const.sideColor.home'); 
						$servePerson = $this->p1 . ' ' . __('pbp.lines.toServe');
					} else if ($server == 2) {
						$color = Config::get('const.sideColor.away');
						$servePerson = $this->p2 . ' ' . __('pbp.lines.toServe');
					}

					if ($winner == $server && $winner > 0) $holdOrLost = __('pbp.lines.holdServe');
					else if ($winner != $server && $winner > 0) $holdOrLost = __('pbp.lines.lostServe');
					else $holdOrLost = __('pbp.lines.inServe');

/*----------------------不管一局有没有结束都输出serve------------------------------*/
					$serve[$set][] = [floor(($last_x + $x) / 2), $color, $servePerson, $holdOrLost, ($server - 1.5) * 2];
/*----------------------------------------------------------------------------------*/

					if ($winner > 0) {
						$last_x = $x;
					}

					$in_progress =true; // 每局结束把in_progres置true，如果下面局有局分或者有抢七分，则会被重新置false。否则就认为下面一局是进行中

				} else { // 抢七或抢十每分

					$eachpoint = $line->innertext;
					$bp = $sp = $mp = false;
					if (strpos($eachpoint, "BP") !== false) $bp = true;
					if (strpos($eachpoint, "SP") !== false) $sp = true;
					if (strpos($eachpoint, "MP") !== false) $mp = true;

					$tmp = preg_replace('/<[^>]*>/', "", $line->children(2));
//					echo $tmp."\n";
					$ep_arr = explode("-", $tmp);

					// 如果出现 1-0 0-1之类，强制开启tb模式
					if ((trim($ep_arr[0]) == 1 || trim($ep_arr[1]) == 1) && $tb_begin == false) {
						$tb_begin = true;
					}

					if (!$tb_begin) continue;

					++$x;
					if (trim($ep_arr[0]) == $point1) {
						if (trim($ep_arr[1]) > $point2) { // p2增大，算p2得分
							++$y;
						}
					} else if (trim($ep_arr[1]) == $point2) {
						if (trim($ep_arr[0]) > $point1) { // p1增大，算p1得分
							--$y;
						}
					}
//					echo trim($ep_arr[0]) . "\t" . trim($ep_arr[1]) . "\n";
					if ($y > $param[$set]['max']) $param[$set]['max'] = $y;
					else if ($y < $param[$set]['min']) $param[$set]['min'] = $y;

					$point1 = trim($ep_arr[0]);
					$point2 = trim($ep_arr[1]);

					if ($line->children(1)->innertext != "" && $line->children(3)->innertext == "") {
						$server = 1;
					} else if ($line->children(3)->innertext != "" && $line->children(1)->innertext == "") {
						$server = 2;
					}   
					if (strpos($line->innertext, "LOST SERVE") !== false) {
						$winner = 3 - $server;
					} else {
						$winner = $server;
					}

					$dotSize = $smallDot;
					$dotValue = [];
					if ($bp || $sp || $mp) {
						$dotSize = $bigDot;
						if ($bp) $dotValue[] = 'BP';
						if ($sp) $dotValue[] = 'SP';
						if ($mp) $dotValue[] = 'MP';
					}

					// 判断抢七或者抢十是否已经结束,结束之后in_progress置false
					if ($game1 == 0 && $game2 == 0) { //抢十
						$tb = 10;
					} else {
						$tb = 7;
					}
					if (abs($point1 - $point2) >= 2 && ($point1 >= $tb || $point2 >= $tb)) {
						$in_progress = false;
					}

					if ($key == $last_key && !$in_progress) {

/*----------------------抢七确认结束时输出不带具体比分的pbp--------------------*/
						$pbp[$set][] = [$x, $y, $smallDot, [], ''];
/*------------------------------------------------------------------*/

						if ($winner == 1) ++$game1;
						else if ($winner == 2) ++$game2;

						if ($winner == 1) {
							$color = Config::get('const.sideColor.home');
						} else {
							$color = Config::get('const.sideColor.away');
						}

/*----------------------抢七确认结束时输出markArea------------------*/
						$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $color];
/*------------------------------------------------------------------*/
					} else {

/*----------------------抢七每分输出pbp-----------------------------*/
						$pbp[$set][] = [$x, $y, $dotSize, $dotValue, $point1.'-'.$point2];
/*------------------------------------------------------------------*/

					}
				} // if fifteens_available
			} //foreach line

			$m = max(abs($param[$set]['min']), abs($param[$set]['max'])) + 2;
			if ($m < 10) $m = 10;
			$param[$set]['min'] = -$m;
			$param[$set]['max'] = $m;
		} //foreach SET

		return [
			'status' => 0,
			'pbp' => $pbp,
			'param' => $param,
			'serve' => $serve,
		];

	}

	protected function process_itf_event() {

		$pbp = [];
		$param = [];
		$serve = [];

		$json = file_get_contents("https://ls.sportradar.com/ls/feeds/?/itf/en/Europe:Berlin/gismo/match_timeline/" . $this->matchid);
		if (!$json) return ['status' => -1, 'errmsg' => __('pbp.notice.error')];

		$json = json_decode($json, true);
		if (!$json) return ['status' => -1, 'errmsg' => __('pbp.notice.error')];

		$set_begin = false;
		$tb_begin = false;
		$server = $winner = 0;

		$in_progress = false;

		$smallDot = 5;
		$bigDot = 15;

		$set = 1;
		$x = $y = 0;
		$last_x = 0;
		$game1 = $game2 = 0;

/*----------------------第一次输出pbp,param,serve---------------------*/
		$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
		$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
		$serve[$set] = [];
/*---------------------------------------------------------------------*/

		foreach ($json["doc"][0]["data"]["events"] as $ep) {
			$pointtype = $ep["type"];
			$team = @$ep["team"];

			if ($pointtype == "first_server") {

				if ($team == 'home') $server= 1;
				else if ($team == 'away') $server= 2;
				else continue;

			} else if ($pointtype == "score_change_tennis") {

				++$x;
				
				$winner = $ep["team"] == 'home' ? 1 : 2;
				if ($winner == 1) {
					--$y;
				} else if ($winner == 2) {
					++$y;
				}
				if ($y > $param[$set]['max']) $param[$set]['max'] = $y;
				else if ($y < $param[$set]['min']) $param[$set]['min'] = $y;

				$ptrans = $ep["pointflagtranslation"];
				$point1 = $ep["game_points"]['home'] + 0;
				$point2 = $ep["game_points"]['away'] + 0;

				if ($ptrans == "Game won" || $ptrans == "Break won" || $ptrans == "Set won" || $ptrans == "Match won") { // 一局结束

					$in_progress = false; // 表示一局已结束
					$tb_begin = false;

					if ($winner == 1) {
						$color = Config::get('const.sideColor.home');
						++$game1;
					} else {
						$color = Config::get('const.sideColor.away');
						++$game2;
					}

/*----------------------每一局结束时输出pbp,输出markArea---------------------*/
					$pbp[$set][] = [$x, $y, $smallDot, [], ''];
					$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $color];  // 表示从last_x到x这段范围的局分，以及底色
/*--------------------------------------------------------------------*/

					if ($server == 1) {
						$color = Config::get('const.sideColor.home'); 
						$servePerson = $this->p1 . ' ' . __('pbp.lines.toServe');
					} else if ($server == 2) {
						$color = Config::get('const.sideColor.away');
						$servePerson = $this->p2 . ' ' . __('pbp.lines.toServe');
					}

					if ($winner == $server && $winner > 0) $holdOrLost = __('pbp.lines.holdServe');
					else if ($winner != $server && $winner > 0) $holdOrLost = __('pbp.lines.lostServe');
					else $holdOrLost = __('pbp.lines.inServe');

/*------------------------------一局结束输出serve-------------------------------*/
					$serve[$set][] = [floor(($last_x + $x) / 2), $color, $servePerson, $holdOrLost, ($server - 1.5) * 2];
/*----------------------------------------------------------------------------------*/

					// 新开始一盘
					if ($ptrans == "Set won" || $ptrans == "Match won") {

						$m = max(abs($param[$set]['min']), abs($param[$set]['max'])) + 2;
						if ($m < 10) $m = 10;
						$param[$set]['min'] = -$m;
						$param[$set]['max'] = $m;

						$game1 = $game2 = 0;

						if ($ptrans != "Match won") {
							++$set;

							$x = $y = 0;
/*----------------------盘初输出pbp,param,serve---------------------*/
							$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
							$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
							$serve[$set] = [];
/*---------------------------------------------------------------------*/

						}

					}

					$last_x = $x;

				} else { // 一局没有结束

					$in_progress = true;

					if ($point1 == 1 || $point2 == 1) $tb_begin = true;

					if (!$tb_begin) {
						$server = $ep['service'];
					} else {
						$server = 0;
					}

					$bp = false; if ($ptrans == "break point") $bp = true;
					$sp = false; if ($ptrans == "set point") $sp = true;
					$mp = false; if ($ptrans == "match point") $mp = true;

					$dotSize = $smallDot;
					$dotValue = [];
					if ($bp || $sp || $mp) {
						$dotSize = $bigDot;
						if ($bp) $dotValue[] = 'BP';
						if ($sp) $dotValue[] = 'SP';
						if ($mp) $dotValue[] = 'MP';
					}

/*-----------------------------每分都输出pbp----------------------------*/
					$pbp[$set][] = [$x, $y, $dotSize, $dotValue, str_replace("50", "AD", $point1).'-'.str_replace("50", "AD", $point2)];
/*--------------------------------------------------------------------*/

				}

			}
		}

		$m = max(abs($param[$set]['min']), abs($param[$set]['max'])) + 2;
		if ($m < 10) $m = 10;
		$param[$set]['min'] = -$m;
		$param[$set]['max'] = $m;

		if ($in_progress) {

			if ($server == 1) {
				$color = Config::get('const.sideColor.home'); 
				$servePerson = $this->p1 . ' ' . __('pbp.lines.toServe');
			} else if ($server == 2) {
				$color = Config::get('const.sideColor.away');
				$servePerson = $this->p2 . ' ' . __('pbp.lines.toServe');
			}

			if ($winner == $server && $winner > 0) $holdOrLost = __('pbp.lines.holdServe');
			else if ($winner != $server && $winner > 0) $holdOrLost = __('pbp.lines.lostServe');
			else $holdOrLost = __('pbp.lines.inServe');

/*------------------------------一局结束输出serve-------------------------------*/
			$serve[$set][] = [floor(($last_x + $x) / 2), $color, $servePerson, $holdOrLost, ($server - 1.5) * 2];
/*----------------------------------------------------------------------------------*/

		}

		return [
			'status' => 0,
			'pbp' => $pbp,
			'param' => $param,
			'serve' => $serve,
		];

	}

	protected function process_grandslam() {

		$pbp = [];
		$param = [];
		$serve = [];

		if ($this->eid == "M996"){
			$prefix = "http://www.rolandgarros.com/en_FR/";
		} else if ($this->eid == "WC"){
			$prefix = "https://www.wimbledon.com/en_GB/";
		} else if ($this->eid == "UO"){
			$prefix = "https://www.usopen.org/en_US/";
		} else if ($this->eid == "M993"){
			$prefix = "http://www.ausopen.com/en_AU/";
		}       
		$this->matchid = preg_replace('/\/.*$/', "", $this->matchid);
		$matchtype = substr($this->matchid, 0, 2);
		$matchtype = Config::get('const.grandslam.type2id.' . $matchtype);
				
		//初始化
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$url = $prefix . "scores/feeds/slamtracker/history/".$matchtype.substr($this->matchid,2)."C.json" ;
		curl_setopt($ch, CURLOPT_URL, $url);
		$json = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($response_code > 400) return ['status' => -1, 'errmsg' => __('pbp.notice.error')];
//		$json = file_get_contents('/home/ubuntu/1101C.json');

		if (!$json) return ['status' => -1, 'errmsg' => __('pbp.notice.error')];
		$json = json_decode($json, true);
		if (!$json) return ['status' => -1, 'errmsg' => __('pbp.notice.error')];
		
		$set_begin = false;
		$tb_begin = false;
		$server = $winner = 0;
		$servePerson = '';

		$in_progress = false;

		$smallDot = 5;
		$bigDot = 15;

		$set = 1;
		$x = $y = 0;
		$last_x = 0;
		$game1 = $game2 = 0;

/*----------------------第一次输出pbp,param,serve---------------------*/
		$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
		$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
		$serve[$set] = [];
/*---------------------------------------------------------------------*/

		foreach ($json as $ep) {

			if ($ep["PointNumber"] == 0) {

				continue;

			} else {

				++$x;
				
				$winner = $ep["PointWinner"];
				if ($winner == 1) {
					--$y;
				} else if ($winner == 2) {
					++$y;
				}
				if ($y > $param[$set]['max']) $param[$set]['max'] = $y;
				else if ($y < $param[$set]['min']) $param[$set]['min'] = $y;

				$point1 = $ep["P1Score"];
				$point2 = $ep["P2Score"];

				if ($ep["GameWinner"] > 0) { // 一局结束

					$in_progress = false; // 表示一局已结束
					$tb_begin = false;

					if ($winner == 1) {
						$color = Config::get('const.sideColor.home');
						++$game1;
					} else {
						$color = Config::get('const.sideColor.away');
						++$game2;
					}

/*----------------------每一局结束时输出pbp,输出markArea---------------------*/
					$pbp[$set][] = [$x, $y, $smallDot, [], ''];
					$param[$set]['markLines'][] = [$last_x, $x, $game1 . '-' . $game2, $color];  // 表示从last_x到x这段范围的局分，以及底色
/*--------------------------------------------------------------------*/

					if ($server == 1 || $server == 3) {
						$color = Config::get('const.sideColor.home'); 
						$servePerson = $this->p1 . ' ' . __('pbp.lines.toServe');
					} else if ($server == 2 || $server == 4) {
						$color = Config::get('const.sideColor.away');
						$servePerson = $this->p2 . ' ' . __('pbp.lines.toServe');
					}

					if ($winner == $server && $winner > 0) $holdOrLost = __('pbp.lines.holdServe');
					else if ($winner != $server && $winner > 0) $holdOrLost = __('pbp.lines.lostServe');
					else $holdOrLost = __('pbp.lines.inServe');

/*------------------------------一局结束输出serve-------------------------------*/
					$serve[$set][] = [floor(($last_x + $x) / 2), $color, $servePerson, $holdOrLost, (0.5 - $server % 2) * 2];
/*----------------------------------------------------------------------------------*/

					// 新开始一盘
					if ($ep["SetWinner"] > 0) {

						$m = max(abs($param[$set]['min']), abs($param[$set]['max'])) + 2;
						if ($m < 10) $m = 10;
						$param[$set]['min'] = -$m;
						$param[$set]['max'] = $m;

						$game1 = $game2 = 0;

						if ($ep["MatchWinner"] == 0) {
							++$set;

							$x = $y = 0;
/*----------------------盘初输出pbp,param,serve---------------------*/
							$pbp[$set][] = [$x, $y, $smallDot, [], '0-0'];
							$param[$set] = ["min" => 0, "max" => 0, "markLines" => []]; // 记录每盘最大值最小值，每局结束的x值以及对应的局数
							$serve[$set] = [];
/*---------------------------------------------------------------------*/

						}

					}

					$last_x = $x;

				} else { // 一局没有结束

					$in_progress = true;

					if ($point1 == 1 || $point2 == 1) $tb_begin = true;

					if (!$tb_begin) {
						$server = $ep['PointServer'];
					} else {
						$server = 0;
					}

					$bp = false; if ($ep['BreakPointOpportunity'] > 0) $bp = true;
					$sp = false; 
					$mp = false; 

					$dotSize = $smallDot;
					$dotValue = [];
					if ($bp || $sp || $mp) {
						$dotSize = $bigDot;
						if ($bp) $dotValue[] = 'BP';
						if ($sp) $dotValue[] = 'SP';
						if ($mp) $dotValue[] = 'MP';
					}

/*-----------------------------每分都输出pbp----------------------------*/
					$pbp[$set][] = [$x, $y, $dotSize, $dotValue, $point1 . '-' . $point2];
/*--------------------------------------------------------------------*/

				}

			}
		}

		$m = max(abs($param[$set]['min']), abs($param[$set]['max'])) + 2;
		if ($m < 10) $m = 10;
		$param[$set]['min'] = -$m;
		$param[$set]['max'] = $m;

		if ($in_progress) {

			if ($server == 1) {
				$color = Config::get('const.sideColor.home'); 
				$servePerson = $this->p1 . ' ' . __('pbp.lines.toServe');
			} else if ($server == 2) {
				$color = Config::get('const.sideColor.away');
				$servePerson = $this->p2 . ' ' . __('pbp.lines.toServe');
			}

			if ($winner == $server && $winner > 0) $holdOrLost = __('pbp.lines.holdServe');
			else if ($winner != $server && $winner > 0) $holdOrLost = __('pbp.lines.lostServe');
			else $holdOrLost = __('pbp.lines.inServe');

/*------------------------------一局结束输出serve-------------------------------*/
			$serve[$set][] = [floor(($last_x + $x) / 2), $color, $servePerson, $holdOrLost, ($server - 1.5) * 2];
/*----------------------------------------------------------------------------------*/

		}

		return [
			'status' => 0,
			'pbp' => $pbp,
			'param' => $param,
			'serve' => $serve,
		];

	}

}
