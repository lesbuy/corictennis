<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\TopPlayer;
use App\Models\RocketPlayer;
use App\Models\PanelSearch;
use Illuminate\Support\Facades\Redis;
use App;
use DB;
use Config;
use Auth;

class HomeController extends Controller
{
	//
	private $current_year = 2021;
	private $banned_ip = ['316161' => ['139.207.51.29', '240e:d8:207:ebd1:d573:3387:ec3a:819d', '222.210.139.232', '171.210.249.184', '182.144.111.18', '240e:d8:715:590d:e405:eb61:a4ae:8b05', '14.111.54.173', '14.111.48.150', '182.144.121.229', '240e:d8:710:28e8:5dcc:1ed3:2d8e:c7f0', '14.106.179.86', '14.111.51.97']];
	private $banned_pid = [];

	public function home($lang) {
		App::setLocale($lang);

		$ret = [];

		// Rank
		foreach (['atp', 'wta'] as $type) {
			if (!isset($ret['rank'][$type])) {
				$ret['rank'][$type] = [];
			}
			$tbname = "calc_" . $type . "_s_year";
			$rows = DB::table($tbname)->limit(10)->get();
			foreach ($rows as $row) {
				$ret['rank'][$type][] = [$row->c_rank, $row->ioc, translate2short($row->id, $row->first, $row->last, $row->ioc)];
			}
		}

		// coming tour
		$year = $this->current_year;
		$file = join('/', [Config::get('const.root'), 'store', 'calendar', $year, '[GW]*']);
		$cmd = "awk -v time=" . time() . " -F\"\\t\" 'time - $22 * 7 * 86400 < $7' " . $file . " | sort -k6,6 | head -8";
		unset($r); exec($cmd, $r);
		$ret['tour'] = [];

		foreach ($r as $row) {
			$arr = explode("\t", $row);
			$level = explode("/", $arr[0]);
			$eid = $arr[1];
			$city = $arr[9];
			$ret['tour'][] = [$level, $eid, $city];
		}	

		// Wheel Banner
		$today = date('Y-m-d', time());

		$ret['wheel'] = [];

/*
		$ret['wheel'][] = [
			'priority' => 10000,
			'type' => 'S',
			'big' => url('/images/bg/corporate/rg-2019-nadal.jpeg'),
			'small' => url('/images/bg/corporate/rg-2019-nadal.jpeg'),
			'bg_pos' => 'center',
			'link' => 'https://www.bilibili.com/video/av53663124/',
		];
*/			
		$possible_dates = [];
		for ($i = -1; $i < 4; ++$i) {
			$ts = time() + $i * 86400;
			$ts_m = date('m', $ts) + 0;
			$ts_d = date('d', $ts) + 0;
			$possible_dates[$ts_m][] = $ts_d;
		}
		foreach ($possible_dates as $k => $v) {
			foreach ($v as $_v) {
				$ones = DB::table('top_players')->where('valid', 1)->whereMonth('dob', $k)->whereDay('dob', $_v)->get();
				foreach ($ones as $one) {
					$age = round((time() - strtotime($one->dob)) / 86400 / 365.25, 0);
					$date = date('Y-m-d', strtotime(join("-", [date('Y', strtotime($one->dob)) + $age, date('m', strtotime($one->dob)), date('d', strtotime($one->dob))])));
					$priority = 1;
					$itvl = (strtotime($date) - strtotime($today)) / 86400;
					if ($itvl == 0) $priority = 1000;
					else if ($itvl == 1) $priority = 900;
					else if ($itvl == 2 || $itvl == 3) $priority == 400;
					else $priority == 100;
					$ret['wheel'][] = [
						'priority' => $priority,
						'date' => $date,
						'type' => 'B',
						'big' => url('/images/bg/happy_birthday.png'),
						'small' => url('/images/bg/happy_birthday.png'),
						'bg_pos' => 'center',
						'pid' => $one->id, 
						'pname' => rename2long($one->first, $one->last, $one->ioc), 
						'pdob' => $one->dob, 
						'page' => $age, 
					];
				}
			}
		}

		$min_date = date('Y-m-d', time() - 60 * 86400);
		$max_date = date('Y-m-d', time() + 20 * 86400);
		$months = array_unique([
			date('Y-m-*', time() - 90 * 86400),
			date('Y-m-*', time() - 60 * 86400),
			date('Y-m-*', time() - 30 * 86400),
			date('Y-m-*', time() - 0 * 86400),
			date('Y-m-*', time() + 30 * 86400),
		]);
		$cmd = "cd " . join("/", [Config::get('const.root'), 'share', 'completed']) . " && grep \"	[MLW]S[07]01\" " . join(" ", $months) . " | grep -v Challenger | grep -v 125K";
		unset($r); exec($cmd, $r); unset($kvmap);
		foreach ($r as $row) {
			$arr = explode("\t", $row);
			foreach (Config::get('const.schema_completed') as $k => $v) {$kvmap[$v] = @$arr[$k];}
			$eid = $kvmap['eid'];
			$sextip = str_replace("L", "W", substr($kvmap['matchid'], 0, 2));
			if (strpos($kvmap['score'], 'iconfont') === false && strpos($kvmap['score'], 'WINNER') === false) continue;
			$true_date = substr($kvmap['sexid'], 0, 10);
			$j = json_decode($kvmap['score'], true);
			$true_id = $j[0][0] == "" ? $kvmap['p2id'] : $kvmap['p1id'];
			$true_name = $j[0][0] == "" ? rename2long($kvmap['p2first'], $kvmap['p2last'], $kvmap['p2ioc']) : rename2long($kvmap['p1first'], $kvmap['p1last'], $kvmap['p1ioc']);
			$info[$eid][$sextip] = [
				$true_date, 
				$true_id, 
				$true_name, 
				translate('tourtitle', strtolower(trim(@$kvmap['tour']))),
				translate('courtname', strtolower(@$kvmap['courtname'])),
				translate_tour(@$kvmap['city']),
			];
		}

		$years = array_unique([
			date('Y', strtotime($min_date)),
			date('Y', strtotime($max_date)),
		]);
		$cmd = "cd " . join("/", [Config::get('const.root'), 'store', 'calendar']) . " && cat " . join(" ", array_map(function ($d) {return $d . "/WT" . " " . $d . "/GS";}, $years)) . " | cut -f2,6,11";
		unset($r); exec($cmd, $r);
		foreach ($r as $row) {
			$arr = explode("\t", $row);
			if ($arr[1] < $min_date || $arr[1] > $max_date) continue;
			$locs[$arr[0]] = translate('nationname', @$arr[2]);
		}

		$ones = DB::table('tour_winners')->whereBetween('date', [$min_date, $max_date])->get();

		foreach ($ones as $one) {
			$sextip = $one->sextip;
			$eid = $one->eid;
			if (!$one->winid && !isset($info[$eid][$sextip])) continue;

			$true_date = $one->date;
			$loc = @$locs[$eid];
			$true_date = @$info[$eid][$sextip][0];
			$pid = $one->winid ? $one->winid : @$info[$eid][$sextip][1];
			$true_name = @$info[$eid][$sextip][2];

			if (strtotime($true_date) >= time() - 10 * 7 * 86400) {
				$priority = 1;
				$itvl = (strtotime($today) - strtotime($true_date)) / 86400;
				if ($itvl <= 7) $priority = 950;
				else if ($itvl < 22) $priority = 500 - $itvl * 10;
				else $priority = 250 - $itvl * 5;
				$ret['wheel'][] = [
					'priority' => $priority,
					'date' => $true_date, 
					'type' => 'T',
					'big' => $one->big ? url(join("/", ['images', 'trophies', $one->big])) : ($one->ori ? $one->ori : url('/images/bg/default_bg_big.jpg')),
					'small' => $one->small ? url(join("/", ['images', 'trophies', $one->small])) : ($one->ori ? $one->ori : url('/images/bg/default_bg_small.jpg')),
					'bg_pos' => $one->pos == -1 ? "top" : ($one->pos == 0 ? "center" : "bottom"),
					'pid' => $pid, 
					'pname' => $true_name, 
					'tour' => @$info[$eid][$sextip][3],
					'court' => @$info[$eid][$sextip][4],
					'city' => @$info[$eid][$sextip][5],
					'loc' => $loc,
				];
			}
		}

		$ones = DB::table('rocket_players')->whereBetween('date', [$min_date, $max_date])->get();
		foreach ($ones as $one) {
			$priority = 1;
			if ($one->sd == 1) {
				$priority = 1051 - $one->topn * 5;
			} else {
				$priority = 999 - $one->topn * 5;
			}
			$ret['wheel'][] = [
				'priority' => $priority,
				'date' => $one->date,
				'type' => 'R',
				'big' => $one->big ? url(join("/", ['images', 'trophies', $one->big])) : ($one->ori ? $one->ori : url('/images/bg/default_bg_big.jpg')),
				'small' => $one->small ? url(join("/", ['images', 'trophies', $one->small])) : ($one->ori ? $one->ori : url('/images/bg/default_bg_small.jpg')),
				'bg_pos' => 'center',
				'pid' => $one->winid,
				'pname' => rename2long($one->first, $one->last, $one->ioc),
				'sd' => $one->sd == 1 ? __('home.basic.s') : __('home.basic.d'),
				'topn' => $one->topn,
				'city' => translate_tour($one->city),
			];
		}

		usort($ret['wheel'], 'self::sortByPriority');

		// 最近搜索热门
		$min_date = date('Y-m-d', time() - 7 * 86400);

		$hot_player = [];
		$ones = DB::table('panel_searches')->select('pid', 'gender', 'first', 'last', 'ioc', DB::raw('count(pid) as ct'))->whereNotIn('pid', $this->banned_pid)->where('created_at', '>=', $min_date)->groupBy(['pid', 'gender', 'first', 'last', 'ioc'])->orderBy('ct', 'desc')->take(16)->get();
		$i = 0;
		foreach ($ones as $one) {
			$hot_player[] = [++$i, $one->ioc, rename2long($one->first, $one->last, $one->ioc), $one->pid, $one->gender == 1 ? 'atp' : 'wta', $one->first, $one->last];
		}
		$ret['hot'] = $hot_player;

		//return json_encode($ret);

		return view('home.index', [
			'ret' => $ret,
			'pagetype1' => 'home',
		]);

	}

	public function panel(Request $req, $lang, $gender, $id) {

		App::setLocale($lang);

		$ip = getIP();
		$ua = $_SERVER['HTTP_USER_AGENT'];

		if (isset($this->banned_ip[$id]) && in_array($ip, $this->banned_ip[$id])) {
			return view('home.card', [
				'ret' => ['error' => __('home.notice.fault')],
			]);
		}

		$ret = ['id' => $id, 'gender' => $gender];
		$ret['stat']['default'] = $this->current_year;

		//DB::connection()->enableQueryLog();

		if (!Auth::check()) {

			$checkones = PanelSearch::where('ip', $ip)->where('ua', $ua)->where('created_at', '>=', date('Y-m-d H:i:s', strtotime("-3600 seconds")))->get();

		//print_r(DB::getQueryLog());
			if (count($checkones) >= 3) {
				return view('home.card', [
					'ret' => ['error' => __('home.notice.too_frequent')],
				]);
			}

		} else {

			$checkones = PanelSearch::where('pid', $id)->where('ip', $ip)->where('ua', $ua)->where('created_at', '>=', date('Y-m-d H:i:s', strtotime("-40 seconds")))->get();

			if (count($checkones) > 0) {
				return view('home.card', [
					'ret' => ['error' => __('home.notice.wait')],
				]);	

			}
		}

		// 取个人资料
		$info = fetch_player_info($id, $gender);
		if (!$info) {
			return view('home.card', [
				'ret' => ['error' => __('home.notice.notexist')],
			]);
		}


		$this->process_basic_data($ret, $info, $id, $gender, $ip, $ua);
		$this->process_match_data($ret, $info, $id, $gender);
		$this->process_gs($ret, $id, $gender);
		$this->process_rank_data($ret, $id, $gender);
		$this->process_recent_match($ret, $id, $gender);



		// stat data
		if ($gender == "atp") {
			$ret['stat']['career'] = true;
			$ret['stat']['start'] = 1991;
		} else {
			$ret['stat']['career'] = false;
			$ret['stat']['start'] = 2009;
		}



		// win rate of top N
		$win_match = array_fill(0, 501, 0);
		$loss_match = array_fill(0, 501, 0);
		$winloss_match = array_fill(0, 501, [0, 0]);
		$cmd = "awk -F\"\\t\" '$19 != 100 && $11 == \"S\" && $22 != \"0\" && $28 != \"-\" && $28 != \"W/O\"' " . join("/", [Config::get('const.root'), 'store', 'activity', $gender, $id]);
		unset($r); exec($cmd, $r);
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if (isset($kvmap)) {unset($kvmap); $kvmap = [];}
				foreach (Config::get('const.schema_activity_match') as $k => $v) {
					$kvmap[$v] = @$arr[$k];
				}
				if ($kvmap['opporank'] > 500 || $kvmap['opporank'] == "-" || $kvmap['opporank'] == "") continue;
				if ($kvmap['winorlose'] == "W") $win_match[$kvmap['opporank']] += 1;
				if ($kvmap['winorlose'] == "L") $loss_match[$kvmap['opporank']] += 1;
			}
		}

		for ($i = 1; $i <= 500; ++$i) {
			$winloss_match[$i] = [$winloss_match[$i - 1][0] + $win_match[$i], $winloss_match[$i - 1][1] + $loss_match[$i]];
		}

		$ret['winrate'] = $winloss_match;


		// honor
		foreach (['S', 'D'] as $sd) {
			$titles = [
				'W' => ['AO' => [0,[]], 'RG' => [0,[]], 'WC' => [0,[]], 'UO' => [0,[]], 'GS' => [0,[]], 'YEC' => [0,[]], 'OL' => [0,[]], '1000' => [0,[]], '500' => [0,[]], '250' => [0,[]], 'TOUR' => [0,[]], 'NONTOUR' => [0,[]], 'Hard' => [0,[]], 'Clay' => [0,[]], 'Grass' => [0,[]], 'Carpet' => [0,[]], 'Indoor' => [0,[]]],
				'F' => ['AO' => [0,[]], 'RG' => [0,[]], 'WC' => [0,[]], 'UO' => [0,[]], 'GS' => [0,[]], 'YEC' => [0,[]], 'OL' => [0,[]], '1000' => [0,[]], '500' => [0,[]], '250' => [0,[]], 'TOUR' => [0,[]], 'NONTOUR' => [0,[]]],
				'SF' => ['GS' => [0,[]], 'YEC' => [0,[]], 'OL' => [0,[]], '1000' => [0,[]], '500' => [0,[]], '250' => [0,[]], 'TOUR' => [0,[]], 'NONTOUR' => [0,[]]],
				'QF' => ['GS' => [0,[]], 'YEC' => [0,[]], 'OL' => [0,[]], '1000' => [0,[]], '500' => [0,[]], '250' => [0,[]], 'TOUR' => [0,[]], 'NONTOUR' => [0,[]]],
				'Attend' => ['GS' => [0,[]], 'YEC' => [0,[]], 'OL' => [0,[]], '1000' => [0,[]], '500' => [0,[]], '250' => [0,[]], 'TOUR' => [0,[]], 'NONTOUR' => [0,[]]],
			];
				$cmd = "awk -F\"\\t\" '$19 == 100 && $11 == \"$sd\" && $20 != \"\" && $20 !~ /^Q[R1-9]/' " . join("/", [Config::get('const.root'), 'store', 'activity', $gender, $id]) . " | sort -t\"	\" -k4gr,4";
				unset($r); exec($cmd, $r);

				if ($r) {
					foreach ($r as $row) {
						$arr = explode("\t", $row);
						if (isset($kvmap)) {unset($kvmap); $kvmap = [];}
						foreach (Config::get('const.schema_activity_summary') as $k => $v) {
							$kvmap[$v] = @$arr[$k];
						}

						if (in_array($kvmap['level'], ['DC', 'FC'])) continue;

						if ($kvmap['level'] == "YEC" && in_array($kvmap['tourname'], ['bali', 'sofia', 'zhuhai'])) $kvmap['level'] = "TOUR";
						if ($kvmap['level'] == "WC") $kvmap['level'] = "YEC";
						if (in_array($kvmap['level'], ['T1', 'PM', 'P5'])) $kvmap['level'] = "1000";
						if (in_array($kvmap['level'], ['T2', 'P700'])) $kvmap['level'] = "500";
						if (in_array($kvmap['level'], ['T3', 'T4', 'T5', 'Int'])) $kvmap['level'] = "250";
						if (in_array($kvmap['level'], ['500', 'ISG', 'CS', 'CSD'])) $kvmap['level'] = "500";
						if (in_array($kvmap['level'], ['250', 'IS', 'WSD', 'WSF', 'WS'])) $kvmap['level'] = "250";
						if (in_array($kvmap['level'], ['ATP', 'GP', 'WCT', 'WT', 'WTA', 'XXI', 'GC'])) $kvmap['level'] = "TOUR";
						if (in_array($kvmap['level'], ['CH', '125K', 'FU', 'ITF'])) $kvmap['level'] = "NONTOUR";
						$is_indoor = strpos($kvmap['ground'], "(I)") !== false ? 'Indoor' : '';
						$kvmap['ground'] = str_replace("(I)", "", $kvmap['ground']);
						if ($kvmap['tourname'] == "australian open") $kvmap['tourname'] = "AO";
						if ($kvmap['tourname'] == "roland garros" || $kvmap['tourname'] == "french open") $kvmap['tourname'] = "RG";
						if ($kvmap['tourname'] == "wimbledon") $kvmap['tourname'] = "WC";
						if ($kvmap['tourname'] == "us open") $kvmap['tourname'] = "UO";

						if ($kvmap['finalround'] == "OB") $kvmap['finalround'] = "SF";
						if ($kvmap['level'] == "YEC") {
							if ($kvmap['finalround'] == "RR" || $kvmap['finalround'] == "R1") $kvmap['finalround'] = "QF";
						}

						if (isset($titles[$kvmap['finalround']][$kvmap['level']])) {
							++$titles[$kvmap['finalround']][$kvmap['level']][0];
							if (in_array($kvmap['level'], ['YEC', 'OL'])) {
								$titles[$kvmap['finalround']][$kvmap['level']][1][] = $kvmap['year'];
							} else {
								$titles[$kvmap['finalround']][$kvmap['level']][1][] = translate_tour($kvmap['tourname']) . '(' . $kvmap['year'] . ')';
							}
						}
						if (isset($titles[$kvmap['finalround']][$kvmap['ground']]) && $kvmap['level'] != "NONTOUR") {
							++$titles[$kvmap['finalround']][$kvmap['ground']][0];
							$titles[$kvmap['finalround']][$kvmap['ground']][1][] = translate_tour($kvmap['tourname']) . '(' . $kvmap['year'] . ')';
						}
						if (isset($titles[$kvmap['finalround']][$kvmap['tourname']])) {
							++$titles[$kvmap['finalround']][$kvmap['tourname']][0];
							$titles[$kvmap['finalround']][$kvmap['tourname']][1][] = $kvmap['year'];
						}
						if (isset($titles[$kvmap['finalround']][$is_indoor])) {
							++$titles[$kvmap['finalround']][$is_indoor][0];
							$titles[$kvmap['finalround']][$is_indoor][1][] = translate_tour($kvmap['tourname']) . '(' . $kvmap['year'] . ')';
						}

						if (isset($titles['Attend'][$kvmap['level']])) {
							++$titles['Attend'][$kvmap['level']][0];
						}
					}
				}

				$win_titles = ['W' => $titles['W'], 'F' => $titles['F']];

				$tours = [];
				foreach (['GS', '1000', '500', '250', 'OL', 'YEC', 'TOUR', 'NONTOUR'] as $level) {
					if (isset($titles['W'][$level])) $tours['W'][] = $titles['W'][$level];
					if (isset($titles['F'][$level])) $tours['F'][] = $titles['F'][$level];
					if (isset($titles['SF'][$level])) $tours['SF'][] = $titles['SF'][$level];
					if (isset($titles['QF'][$level])) $tours['QF'][] = $titles['QF'][$level];
					if (isset($titles['Attend'][$level])) $tours['Attend'][] = $titles['Attend'][$level];
				}
				$ret['honor'][$sd] = [$win_titles, $tours];
		} 

		//return json_encode($ret);
		return view('home.card', [
			'ret' => $ret,
		]);
	}

	private function process_basic_data(&$ret, &$info, $id, $gender, $ip, $ua) {
		$first = $info["first"];
		$last = $info["last"];
		$ioc = $info["ioc"];
		$birth = $info["birthday"];
		$birthplace = $info["birthplace"];
		$residence = $info["residence"];
		$height = $info["height"][0];
		$height_bri = $info["height"][1];
		$proyear = $info["turnpro"];
		$hand = $info["hand"][0];
		$backhand = $info["hand"][1];

		// 查询记录写入db
		$ps = new PanelSearch;
		$ps->pid = $id;
		$ps->gender = $gender == "atp" ? 1 : 2;
		$ps->first = $first;
		$ps->last = $last;
		$ps->ioc = $ioc;
		$ps->ip = $ip;
		$ps->ua = $ua;
		$ps->userid = Auth::id();
		$ps->save();

		$name = translate2long($id, $first, $last, $ioc);
		if ($birth == "0000-00-00" || $birth == "1753-01-01") {
			$age = __('home.nodata');
			$birth = "";
		} else {
			$year = date('Y', strtotime($birth));
			$age = date('Y', time()) - $year;
			if (strtotime(str_replace($year, date('Y', time()), $birth)) > strtotime(date('Y-m-d', time()))) {
				--$age;
			}
			$birth = date(__('home.basic.format'), strtotime($birth));
		}
		$country = __('nationname.' . $ioc);
		if ($height_bri == "0'0\"") {
			$height_bri = __('home.nodata');
			$height = "";
		} else {
			$height .= "cm";
		}
		if ($proyear == 0) $proyear = __('home.nodata');

		if ($hand == 0) {
			$play = __('home.nodata');
			$bh = "";
		} else {
			if ($hand == 1) {
				$play = __('home.play.right');
			} else if ($hand == 2) {
				$play = __('home.play.left');
			} else {
				$play = __('home.play.avg');
			}

			if ($backhand == 1) {
				$bh = __('home.play.oneback');
			} else if ($backhand == 2) {
				$bh = __('home.play.twoback');
			} else if ($backhand == 3) {
				$bh = __('home.play.twoforetwoback');
			}
		}

		if ($birthplace == "") $birthplace = __('home.nodata');
		if ($residence == "") $residence = __('home.nodata');

		$rank_s = fetch_rank($id, $gender, 's');
		$rank_d = fetch_rank($id, $gender, 'd');

		$head = $info["portrait"];
		if (!$info["hasPortrait"] && $info["hasHeadshot"]) $head = $info["headshot"];
		
		$audio = "";
		$cmd = "grep \"^$id	\" " . join("/", [Config::get('const.root'), $gender, "player_pronoun"]);
		unset($r); exec($cmd, $r);
		if ($r && count($r) >= 1) {
			$arr = explode("\t", $r[0]);
			$audio = $arr[2];
		}

		$ret['basic'] = [
			'name' => $name,
			'country' => $country,
			'rank' => [$rank_s, $rank_d],
			'age' => [$age, $birth],
			'height' => [$height_bri, $height],
			'proyear' => $proyear,
			'play' => [$play, $bh],
			'residence' => $residence,
			'birthplace' => $birthplace,
			'head' => $head,
			'ioc' => $ioc,
			'audio' => $audio,
		];
	}

	private function process_match_data(&$ret, &$info, $id, $gender) {
		// match data
		$prize_c = $info["prize"][0];
		$prize_y = $info["prize"][1];
		$title_s_c = $info["titleS"][0];
		$title_s_y = $info["titleS"][1];
		$title_d_c = $info["titleD"][0];
		$title_d_y = $info["titleD"][1];

		$ret['match']['prize'] = ['career' => $prize_c, 'ytd' => $prize_y];
		$ret['match']['title']['s'] = ['career' => $title_s_c, 'ytd' => $title_s_y];
		$ret['match']['title']['d'] = ['career' => $title_d_c, 'ytd' => $title_d_y];

		$cmd = "awk -F\"\\t\" '$19 != 100 && $11 == \"S\" && $8 != \"FU\" && $8 != \"CH\" && $8 != \"ITF\" && $20 !~ /^Q[1-9]/ && $28 != \"-\" && $28 != \"W/O\" && $28 != \"\"' " . join("/", [Config::get('const.root'), 'store', 'activity', $gender, $id]) . " | cut -f21 | sort | uniq -c";
		unset($r); exec($cmd, $r);
		$win_count = $loss_count = 0;
		if ($r) {
			foreach ($r as $row) {
				$arr = explode(" ", trim($row));
				if (@$arr[1]  == "W") $win_count = $arr[0];
				else if (@$arr[1]  == "L") $loss_count = $arr[0];
			}
		}
		$ret['match']['count']['career'] = [$win_count + $loss_count, $win_count, $loss_count, $win_count];

		$cmd = "awk -F\"\\t\" '$5 == " . $ret['stat']['default'] . " && $19 != 100 && $11 == \"S\" && $8 != \"FU\" && $8 != \"CH\" && $8 != \"ITF\" && $20 !~ /^Q[1-9]/ && $28 != \"-\" && $28 != \"W/O\" && $28 != \"\"' " . join("/", [Config::get('const.root'), 'store', 'activity', $gender, $id]) . " | cut -f21 | sort | uniq -c";
		unset($r); exec($cmd, $r);
		$win_count = $loss_count = 0;
		if ($r) {
			foreach ($r as $row) {
				$arr = explode(" ", trim($row));
				if (@$arr[1]  == "W") $win_count = $arr[0];
				else if (@$arr[1]  == "L") $loss_count = $arr[0];
			}
		}
		$ret['match']['count']['ytd'] = [$win_count + $loss_count, $win_count, $loss_count, $win_count];
	}

	private function process_gs(&$ret, $id, $gender) {
		// GS data
		$cmd = "cd " . join("/", [Config::get('const.root'), 'store', 'draw']) . "; grep \"	$gender$id	\" */[ARWU][OGC]";
		unset($r); exec($cmd, $r);

		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				foreach (Config::get('const.schema_drawsheet') as $k => $v) {$kvmap[$v] = @$arr[$k];}
				$year = substr($kvmap['sextip'], 0, 4);
				$eid = substr($kvmap['sextip'], 5, 2);
				$sextip = substr($kvmap['sextip'], 8, 2);
				$status = $kvmap['mStatus'];
				if (in_array($status, ['F', 'H', 'J', 'L'])) $winner = 1; else if (in_array($status, ['G', 'I', 'K', 'M'])) $winner = 2; else $winner = 0;

				if ($sextip == "MS" || $sextip == "WS") {
					$sd = "S";
				} else if ($sextip == "MD" || $sextip == "WD") {
					$sd = "D";
				} else {
					continue;
				}
				$round = "R" . floor((intval($kvmap['id']) % 1000) / 100);
				if (in_array($kvmap['round'], ['QF', 'SF', 'F'])) {
					$round = $kvmap['round'];
				}
				if ($round == "F" && ((in_array($gender . $id, [$kvmap['P1A'], $kvmap['P1B']]) && $winner == 1) || (in_array($gender . $id, [$kvmap['P2A'], $kvmap['P2B']]) && $winner == 2))) $round = "W";
				$ret['gs']['detail'][$year][$eid][$sd]['round'] = $round;

				if ((in_array($gender . $id, [$kvmap['P1A'], $kvmap['P1B']]) && in_array($status, ['F', 'H', 'J']))
						|| (in_array($gender . $id, [$kvmap['P2A'], $kvmap['P2B']]) && in_array($status, ['G', 'I', 'M']))) {
					$ret['gs']['all'][$eid][$sd]['win'] = @$ret['gs']['all'][$eid][$sd]['win'] + 1;
					$ret['gs']['all']['all'][$sd]['win'] = @$ret['gs']['all']['all'][$sd]['win'] + 1;
				} else if ((in_array($gender . $id, [$kvmap['P2A'], $kvmap['P2B']]) && in_array($status, ['F', 'H', 'J']))
						|| (in_array($gender . $id, [$kvmap['P1A'], $kvmap['P1B']]) && in_array($status, ['G', 'I', 'M']))) {
					$ret['gs']['all'][$eid][$sd]['loss'] = @$ret['gs']['all'][$eid][$sd]['loss'] + 1;
					$ret['gs']['all']['all'][$sd]['loss'] = @$ret['gs']['all']['all'][$sd]['loss'] + 1; 
				}
			}
		}

		if (!isset($ret['gs']['detail'])) {
			$ret['gs']['info'] = [0, 0];
		} else {
			$keys = array_keys($ret['gs']['detail']);
			$ret['gs']['info'] = [min($keys), max($keys)];
		}
	}

	private function process_rank_data(&$ret, $id, $gender) {
		// rank data
		foreach (['S', 'D'] as $sd) {
			$cmd = "cd " . join("/", [Config::get('const.root'), "data", "rank", $gender, strtolower($sd), "history"]) . "; grep \"^$id	\" *";
			unset($r); exec($cmd, $r);
			$maxrank = 9999;
			$maxrankdate = "-";
			$maxrankdura = 0;
			$maxrankdatestart = "-";
			$ytdmaxrank = 9999;
			$ytdmaxrankdate = "-";
			$maxpoint = 0;
			if ($r) {
				foreach ($r as $row) {
					$arr = explode("\t", $row);
					$rank = $arr[2];
					$point = intval($arr[3]);
					$date = date('Y-m-d', strtotime($arr[5]));
					$ret['rank']['dot'][$sd][] = [$date, $rank + 0];
					if (strtotime($arr[5]) < strtotime("2009-01-01")) $point *= 2;

					if ($rank < $maxrank) {
						$maxrank = $rank;
						$maxrankdate = $date;
						$maxrankdatestart = $date;
						$maxrankdura = 0;
					} else {
						if ($maxrankdatestart != "-") {
							$maxrankdura += round((strtotime($date) - strtotime($maxrankdatestart)) / 86400 / 7, 0);
						}
						if ($rank == $maxrank) {
							$maxrankdatestart = $date;
						} else {
							$maxrankdatestart = "-";
						}
					}

					if ($point > $maxpoint) {
						$maxpoint = $point;
					}

					if ($this->current_year == substr($date, 0, 4)) {
						if ($rank < $ytdmaxrank) {
							$ytdmaxrank = $rank;
							$ytdmaxrankdate = $date;
						}
					}
				}
				if ($maxrankdatestart != "-") {
					$maxrankdura += ceil((time() - strtotime($maxrankdatestart)) / 86400 / 7);
				}
			}
			$ret['rank']['ch'][$sd] = $maxrank == 9999 ? "-" : $maxrank;
			$ret['rank']['chdate'][$sd] = $maxrankdate;
			$ret['rank']['chdura'][$sd] = $maxrankdura;
			$ret['rank']['ytdh'][$sd] = $ytdmaxrank == 9999 ? "-" : $ytdmaxrank;
			$ret['rank']['ytdhdate'][$sd] = $ytdmaxrankdate;
			$ret['rank']['maxpoint'][$sd] = $maxpoint;
		}
	}

	private function process_recent_match(&$ret, $id, $gender) {
		// recent data

		$ids = []; // 记录那些需要去数据库查名字的人
		$heads = []; // 需要查头像的人
		foreach (['S', 'D'] as $sd) {
			$cmd = "grep ^$id " . join("/", [Config::get('const.root'), "data", "calc", $gender, strtolower($sd), "year", "unloaded"]);
			unset($r); exec($cmd, $r);

			$matches = [];
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$date = $arr[5];
				$year = $arr[4];
				$eid = $arr[3];
				$level = $arr[10];
				$city = translate_tour($arr[8]);
				$sfc = $arr[11];

				$cmd = "grep \"$gender$id	\" " . join("/", [Config::get('const.root'), 'store', 'draw', $year, $eid]);
				unset($r1); exec($cmd, $r1);
				foreach ($r1 as $row1) {
					$arr1 = explode("\t", $row1);
					if (strpos($row1, "BYE") !== false) continue;
					if (isset($kvmap)) {unset($kvmap); $kvmap = [];}
					foreach (Config::get('const.schema_drawsheet') as $k => $v) {
						$kvmap[$v] = @$arr1[$k];
					}
					if (substr($kvmap['sextip'], 1, 1) != $sd) continue;
					$t = substr($kvmap['sextip'], 0, 1);
					if ($gender == "wta" && $t != "P" && $t != "W") continue;
					if ($gender == "atp" && $t != "Q" && $t != "M") continue;

					$pos = 0; // pos记录这个人是在home还是away
					if ($gender . $id == $kvmap['P1A'] || $gender . $id == $kvmap['P1B']) {
						if (in_array($kvmap['P2A'], ["BYE", "", "0", "-"])) continue;
						$pos = 1;
						$oppo = [$kvmap['Seed2'], []];
						$oppo[1][] = [get_ori_id($kvmap['P2A']), $kvmap['P2ANation'], "", ""];
						if ($sd == "D") {
							$oppo[1][] = [get_ori_id($kvmap['P2B']), $kvmap['P2BNation'], "", ""];
						}
						$me = [$kvmap['Seed1'], []];
						$me[1][] = [get_ori_id($kvmap['P1A']), $kvmap['P1ANation'], "", ""];
						if ($sd == "D") {
							$me[1][] = [get_ori_id($kvmap['P1B']), $kvmap['P1BNation'], "", ""];
						}
					} else if ($gender . $id == $kvmap['P2A'] || $gender . $id == $kvmap['P2B']) {
						if (in_array($kvmap['P1A'], ["BYE", "", "0", "-"])) continue;
						$pos = 2;
						$oppo = [$kvmap['Seed1'], []];
						$oppo[1][] = [get_ori_id($kvmap['P1A']), $kvmap['P1ANation'], "", ""];
						if ($sd == "D") {
							$oppo[1][] = [get_ori_id($kvmap['P1B']), $kvmap['P1BNation'], "", ""];
						}
						$me = [$kvmap['Seed2'], []];
						$me[1][] = [get_ori_id($kvmap['P2A']), $kvmap['P2ANation'], "", ""];
						if ($sd == "D") {
							$me[1][] = [get_ori_id($kvmap['P2B']), $kvmap['P2BNation'], "", ""];
						}
					}

					$wltag = "";
					if ($pos == 1 && in_array($kvmap['mStatus'], ['F', 'H', 'J', 'L'])) $wltag = "W";
					else if ($pos == 2 && in_array($kvmap['mStatus'], ['F', 'H', 'J', 'L'])) $wltag = "L";
					else if ($pos == 1 && in_array($kvmap['mStatus'], ['G', 'I', 'K', 'M'])) $wltag = "L";
					else if ($pos == 2 && in_array($kvmap['mStatus'], ['G', 'I', 'K', 'M'])) $wltag = "W";

					if ($wltag == "") {
						$score = "";
					} else {
						$score = revise_gs_score($kvmap['mStatus'], $kvmap['score1'], $kvmap['score2']);
					}

					$round = $kvmap['round'];
					$roundid = Config::get('const.round2id')[$round];

					$matches[] = [$date, $city, $roundid, $level, $round, $me, $oppo, $score, $wltag];
				}
			}

			$cmd = "awk -F\"\\t\" '$15 == \"" . strtolower($sd) . "\"' " . join("/", [Config::get('const.root'), 'data', 'activity', $gender, $id]) . " | sort -t\"	\" -k8gr,8 | head -30";
			unset($r); exec($cmd, $r);
			if ($r) {
				foreach ($r as $row) {
					$arr = explode("\t", $row);
					if (isset($kvmap)) {unset($kvmap); $kvmap = [];}
					foreach (Config::get('const.schema_activity') as $k => $v) {
						$kvmap[$v] = @$arr[$k];
					}
					$date = $kvmap['start_date'];
					$year = $kvmap['year'];
					$eid = $kvmap['joineid'];
					$city = translate_tour($kvmap['city']);
					$level = $kvmap['level'];
					$sfc = $kvmap['sfc'];

					// [seed, [pid, ioc, name, headshot]]
					$me = [$kvmap['seed'], [[$kvmap['pid'], $kvmap['ioc'], "", ""]]];
					if ($sd == "D") {
						$me[1][] = [$kvmap['partner_id'], $kvmap['partner_ioc'], "", ""];
					}

					$_matches = explode("@", $kvmap["matches"]);
					foreach ($_matches as $amatch) {
						$arr2 = explode("!", $amatch);
						foreach (Config::get('const.schema_activity_matches') as $k => $v) {
							$match_kvmap[$v] = @$arr2[$k + 1];
						}
						if (in_array($match_kvmap['oid'], ["", "BYE", "-", "0"])) continue;
						$oppo = [$match_kvmap['oseed'], []];
						$oppo[1][] = [$match_kvmap['oid'], $match_kvmap['oioc'], "", ""];
						if ($sd == "D") {
							$oppo[1][] = [$match_kvmap['opartner_id'], $match_kvmap['opartner_ioc'], "", ""];
						}

						$wltag = substr($match_kvmap['wl'], 0, 1);
						$score = $match_kvmap['games'];
						if ($score == "-") $score = "W/O";
						$round = $match_kvmap['round'];
						$roundid = Config::get('const.round2id')[$round];

						if ($sd == "D" && $match_kvmap['partner_id']) {
							$me[1][1] = [$kvmap['partner_id'], $kvmap['partner_ioc'], "", ""];
						}

						$matches[] = [$date, $city, $roundid, $level, $round, $me, $oppo, $score, $wltag];
					}
				}
			}

			usort($matches, 'self::match_sort');
			if ($sd == "S")	$matches = array_slice($matches, 0, 30);
			else $matches = array_slice($matches, 0, 10);

			foreach ($matches as $t_match) {
				foreach ($t_match[5][1] as $t_person) {
					if (!in_array($t_person[0], $ids)) $ids[] = $t_person[0];
				}
				foreach ($t_match[6][1] as $t_person) {
					if (!in_array($t_person[0], $ids)) $ids[] = $t_person[0];
				}
			}

			$ret['recent'][$sd] = $matches;
		}

		$id2name = [];
		$id2head = [];
		foreach ($ids as $id) {
			$id2name[$id] = translate2short($id);
			$id2head[$id] = fetch_headshot($id, $gender)[1];
		}

		foreach ($ret['recent'] as $sd => &$matches) {
			foreach ($matches as &$match) {
				foreach ($match[5][1] as &$person) {
					if ($person[0] == "" || $person[0] === "0") continue;
					if ($person[2] == "") $person[2] = @$id2name[$person[0]];
					$person[3] = $id2head[$person[0]];
				}
				foreach ($match[6][1] as &$person) {
					if ($person[0] == "" || $person[0] === "0") continue;
					if ($person[2] == "") $person[2] = @$id2name[$person[0]];
					$person[3] = $id2head[$person[0]];
				}
				if ($match[8] == "L") swap($match[5], $match[6]); // 保持胜者在前，败者在后
			}
		}
	}

	public function stat(Request $req, $lang, $gender, $id, $year) {

		App::setLocale($lang);

		$ret = [];

		if ($gender == "wta") {
			$cmd = "grep \"^$year	$id	\" " . join("/", [Config::get('const.root'), 'store', 'stat', 'wta', 'stat']);
			unset($r); exec($cmd, $r);

			if ($r && count($r) >= 1) {
				$arr = explode("\t", $r[0]);
				$ret = ['base' => array_slice($arr, 3, 3), 'serve' => array_slice($arr, 6, 6), 'return' => array_slice($arr, 12, 5)];
				$ret['max'] = ['ace' => 500, 'df' => 500];
			}
		} else {

			$url = "https://www.atpworldtour.com/en/content/ajax/player-match-facts?year=$year&surfaceType=all&playerId=$id";
			$html = file_get_html($url);
			if ($html) {
				$base = [0,0,0]; $serve = [0,0,0,0,0,0]; $return = [0,0,0,0,0];
				$has_data = false;
				foreach ($html->find('.mega-table tbody tr') as $tr) {
					if (count($tr->children()) < 2) continue;
					$key = trim($tr->children(0)->innertext);
					$value = str_replace(",", "", str_replace("%", "", trim($tr->children(1)->innertext)));
					if ($key == "Aces") $base[1] = $value;
					else if ($key == "Double Faults") $base[2] = $value;
					else if ($key == "1st Serve") $serve[0] = $value;
					else if ($key == "1st Serve Points Won") $serve[1] = $value;
					else if ($key == "2nd Serve Points Won") $serve[2] = $value;
					else if ($key == "Total Service Points Won") $serve[3] = $value;
					else if ($key == "Break Points Saved") $serve[4] = $value;
					else if ($key == "Service Games Won") $serve[5] = $value;
					else if ($key == "1st Serve Return Points Won") $return[0] = $value;
					else if ($key == "2nd Serve Return Points Won") $return[1] = $value;
					else if ($key == "Return Points Won") $return[2] = $value;
					else if ($key == "Break Points Converted") $return[3] = $value;
					else if ($key == "Return Games Won") $return[4] = $value;
					$has_data = true;
				}
				if ($has_data) {
					$ret = ['base' => $base, 'serve' => $serve, 'return' => $return];
					$ret['max'] = ['ace' => 1500, 'df' => 500];
					if ($year == 0) $ret['max'] = ['ace' => 13000, 'df' => 3000];
				}
			}
		}

		return view('home.stat', [
				'ret' => $ret
		]);
	}

	public function match(Request $req, $lang, $gender, $id, $sd, $filter) {

		App::setLocale($lang);
		$ret = [];

		$cmd1 = "awk -F\"\\t\" '$19 != 100 && $11 == \"" . strtoupper($sd) . "\" && $8 != \"FU\" && $8 != \"CH\" && $8 != \"ITF\" && $20 !~ /^Q[1-9]/ && $28 != \"-\" && $28 != \"W/O\" && $28 != \"\"' " . join("/", [Config::get('const.root'), 'store', 'activity', $gender, $id]);
		$cmd2 = "awk -F\"\\t\" '$19 == 100 && $11 == \"" . strtoupper($sd) . "\" && $8 != \"FU\" && $8 != \"CH\" && $8 != \"ITF\" && $20 ==\"W\"' " . join("/", [Config::get('const.root'), 'store', 'activity', $gender, $id]);

		if ($filter == "gs") {
			$chain = " | awk -F\"\\t\" '$8 == \"GS\"'";
		} else if ($filter == "ms") {
			$chain = " | awk -F\"\\t\" '$8 == \"1000\" || $8 == \"T1\" || $8 == \"PM\" || $8 == \"P5\"'";
		} else if ($filter == "ao") {
			$chain = " | awk -F\"\\t\" '$7 == \"australian open\"'";
		} else if ($filter == "rg") {
			$chain = " | awk -F\"\\t\" '$7 == \"roland garros\"'";
		} else if ($filter == "wc") {
			$chain = " | awk -F\"\\t\" '$7 == \"wimbledon\"'";
		} else if ($filter == "uo") {
			$chain = " | awk -F\"\\t\" '$7 == \"us open\"'";
		} else if ($filter == "yec") {
			$chain = " | awk -F\"\\t\" '$8 == \"WC\" || ($8 == \"YEC\" && $7 != \"bali\" && $7 != \"sofia\" && $7 != \"zhuhai\")'";
		} else if ($filter == "ol") {
			$chain = " | awk -F\"\\t\" '$8 == \"OL\"'";
		} else if ($filter == "dc") {
			$chain = " | awk -F\"\\t\" '$8 == \"DC\"'";
		} else if ($filter == "fc") {
			$chain = " | awk -F\"\\t\" '$8 == \"FC\"'";
		} else if ($filter == "hard") {
			$chain = " | awk -F\"\\t\" '$10 ~ \"Hard\"'";
		} else if ($filter == "clay") {
			$chain = " | awk -F\"\\t\" '$10 ~ \"Clay\"'";
		} else if ($filter == "grass") {
			$chain = " | awk -F\"\\t\" '$10 ~ \"Grass\"'";
		} else if ($filter == "carpet") {
			$chain = " | awk -F\"\\t\" '$10 ~ \"Carpet\"'";
		} else {
			$chain = "";
		}

		$cmd1 .= $chain . " | cut -f5,21 | sort | uniq -c";
		$cmd2 .= $chain . " | cut -f5 | sort | uniq -c";

		unset($r1); exec($cmd1, $r1);
		unset($r2); exec($cmd2, $r2);

		$ret['match'] = ['career' => ['T' => 0, 'W' => 0, 'L' => 0], 'ytd' => ['T' => 0, 'W' => 0, 'L' => 0]];
		$ret['title'] = ['career' => 0, 'ytd' => 0];

		if ($r1) {
			foreach ($r1 as $row) {
				$row = str_replace(" ", "\t", trim($row));
				$arr = explode("\t", $row);
				$year = $arr[1];
				if ($year == $this->current_year) {
					$ret['match']['ytd']['T'] += $arr[0];
					$ret['match']['ytd'][$arr[2]] += $arr[0];
				}
				$ret['match']['career']['T'] += $arr[0];
				$ret['match']['career'][$arr[2]] += $arr[0];
			}
		}

		if ($r2) {
			foreach ($r2 as $row) {
				$arr = explode(" ", trim($row));
				$year = $arr[1];
				if ($year == $this->current_year) {
					$ret['title']['ytd'] += $arr[0];
				}
				$ret['title']['career'] += $arr[0];
			}
		}

		return json_encode($ret);
	}

	private function match_sort($a, $b) {
		if ($a[0] > $b[0]) return -1;
		else if ($a[0] < $b[0]) return 1;
		else if ($a[1] != $b[1]) return strcmp($a[1], $b[1]);
		else if ($a[2] < $b[2]) return 1;
		else if ($a[2] > $b[2]) return -1;
		else return 0;
	}

	public function add_bt() {

		App::setLocale('zh');

		$milestones = [1, 3, 5, 10, 20, 50, 100];

		$persons = [];

		foreach (['s', 'd'] as $sd) {
			if ($sd == "s") {
				$milestones = [1, 3, 5, 10, 20, 50, 100];
			} else {
				$milestones = [1, 10];
			}
			foreach ($milestones as $ms) {

				$cmd = "awk -F\"\\t\" '$28 > $ms && $3 <= $ms' " . join("/", [Config::get('const.root'), "*", "all_results", "rank_*_" . $sd . "_year_en"]);
				unset($r); exec($cmd, $r);

				foreach ($r as $row) {
					$arr = explode("\t", $row);
					$pid = $arr[0];
					$name = $arr[5];
					$n_arr = explode(",", $name);
					$first = trim($n_arr[1]);
					$last = trim($n_arr[0]);
					$ioc = $arr[21];

					$city = $arr[17];

					if (preg_match('/^[A-Z0-9]{4}$/', $pid)) $gender = "atp"; else $gender = "wta";

					$persons[] = [$sd, $ms, $pid, $first, $last, $ioc, $city, $gender];
				}
			}
		}

		$lists = [];
		$ones = RocketPlayer::orderBy('updated_at', 'desc')->take(200)->get();
		foreach ($ones as $row) {
			$id = $row->id;
			$gender = $row->gender == 1 ? "atp" : "wta";
			$sd = $row->sd == 1 ? 's' : 'd';
			$topn = $row->topn;
			$date = $row->date;
			$city = $row->city;
			$pid = $row->winid;
			$pname = $row->first . " " . $row->last;
			$ioc = $row->ioc;
			$img = $row->ori;

			$lists[] = [$id, $gender, $sd, $topn, $date, $city, $pname, $ioc, $img, $pid];
		}

		return view('home.add_breakthrough', [
			'possible' => $persons,
			'list' => $lists,
		]);
	}

	public function add_bt_post(Request $req) {

		$arr = $req->all();

		$row = DB::table('profile_' . $arr['gender'])->where('longid', $arr['pid'])->first();
		$first = $row->first_name;
		$last = $row->last_name;
		$ioc = $row->nation3;

		if (!isset($arr['method']) || $arr['method'] == "add") {
			$one = new RocketPlayer;
			$one->date = date('Y-m-d', strtotime($arr['date']));
			$one->gender = $arr['gender'] == "atp" ? 1 : 2;
			$one->sd = $arr['sd'] == "s" ? 1 : 2;
			$one->winid = $arr['pid'];
			$one->topn = $arr['topn'];
			$one->city = $arr['city'];
			$one->ori = $arr['imgsrc'];
			$one->first = $first;
			$one->last = $last;
			$one->ioc = $ioc;

			$one->save();
			return $first . " " . $last . " Added";
		} else if ($arr['method'] == "update") {

			$id = $arr['id'];
			$one = RocketPlayer::find($id);
			$one->topn = $arr['topn'];
			$one->city = $arr['city'];
			if ($arr['imgsrc'] != $one->ori) {
				$one->big = NULL;
				$one->small = NULL;
			}
			$one->ori = $arr['imgsrc'];
			$one->date = $arr['date'];

			$one->save();
			return $first . " " . $last . " Updated";
		} else if ($arr['method'] == "delete"){
			$id = $arr['id'];
			RocketPlayer::destroy($id);

			return $first . " " . $last . " Deleted";
		}

	}

	private function sortByPriority($a, $b) {
		return $a['priority'] > $b['priority'] ? -1 : (
			$a['priority'] < $b['priority'] ? 1 : (
				$a['date'] > $b['date'] ? -1 : (
					$a['date'] < $b['date'] ? 1 : 0
				)
			)
		);
	}
}
