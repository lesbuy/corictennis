<?php

namespace App\Http\Controllers\Dc;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use DB;
use Auth;
use Config;
use App\Models\DcFill;
use App\Models\DcRank;
use App\Models\DcDdl;
use App\User;

class DcController extends Controller
{

	protected $root = '/home/ubuntu';

	protected $eid;
	protected $year;
	protected $sextip;
	protected $userid;
	protected $round2point = [2 => 1, 3 => 3, 4 => 5, 5 => 10, 6 => 20, 7 => 35, 8 => 50];

	public function index($lang, $eid, $year, $sextip, $userid = NULL) {

		App::setLocale($lang);
		$this->eid = $eid;
		$this->year = $year;
		$this->sextip = $sextip;
		$this->userid = $userid;

		$ret = [];
		self::get_tour_info($ret);
		self::get_draw($ret);
		self::get_rank($ret);
		$title = __('frame.menu.dc');
		$pageTitle = __('frame.menu.dc');

		return view('dc.index', [
			'ret' => $ret, 
			'eid' => $eid, 
			'year' => $year, 
			'sextip' => $sextip, 
			'title' => $title, 
			'pageTitle' => $pageTitle,
			'pagetype1' => 'dc',
			'pagetype2' => join("_", ['fill', $year, $eid, $sextip]),
		]);

	}

	protected function get_draw(&$ret) {

		$is_me = true;

		$user_fill = [];
		if (Auth::check()) {

			if ($this->userid !== NULL && $this->userid != Auth::id()) $is_me = false;

			if ($this->userid !== NULL) {
				$userid = $this->userid;
			} else {
				$userid = Auth::id();
			}
			$one = DcFill::where(['year' => $this->year, 'eid' => $this->eid, 'sextip' => $this->sextip, 'userid' => $userid])->first();
			if ($one) {
				$fills = $one->fill;
				$arr = explode("|", $fills);
				foreach ($arr as $grid) {
					if (!preg_match('/\d_\d{1,3}_\d{1,3}$/', $grid)) continue;
					$grid_arr = explode("_", $grid);
					$x = $grid_arr[0];
					$y = $grid_arr[1];
					$p = $grid_arr[2];
					$user_fill[$x][$y] = $p;
				}
			}
		}

		$file = join("/", [env('ROOT'), "store", "draw", $this->year, $this->eid]);
		$cmd = "grep '^" . $this->sextip . "\t' $file";
		unset($r); exec($cmd, $r);

		$show_qf = false;
		$kvmap = [];
		$fill = [];
		$right = [];
		$status = [];
		$id2player = [];
		$player2id = [];
		if ($r) {
			$totalRound = ceil(log(count($r)) / log(2));
			$blockRound = $totalRound - 3;
			$blockCapacity = 1 << $blockRound;
			
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$kvmap = [];
				foreach (Config::get('const.schema_drawsheet') as $k => $v) $kvmap[$v] = @$arr[$k];
				$matchid = intval($kvmap['id']);
				$round = floor(($matchid % 1000) / 100);
				$seq = $matchid % 100;
				$seq1 = ($seq - 0.5) * (1 << $round);
				$seq2 = $seq * (1 << $round);

				if (strpos($kvmap['P1A'], "QUAL") !== false) $kvmap['P1A'] .= $seq1;
				if (strpos($kvmap['P2A'], "QUAL") !== false) $kvmap['P2A'] .= $seq2;
				
				// 首轮时记下每个id对应的序号
				if ($round == 1) {
					$p1 = translate2short($this->get_pid($kvmap['P1A']), $kvmap['P1AFirst'], $kvmap['P1ALast'], $kvmap['P1ANation']);
					$p2 = translate2short($this->get_pid($kvmap['P2A']), $kvmap['P2AFirst'], $kvmap['P2ALast'], $kvmap['P2ANation']);
					if ($kvmap['Seed1']) $p1 .= " [" . $kvmap['Seed1'] . "]";
					if ($kvmap['Seed2']) $p2 .= " [" . $kvmap['Seed2'] . "]";
					$id2player[$seq1] = $p1;
					$id2player[$seq2] = $p2;

					$player2id[$kvmap['P1A']] = $seq1;
					$player2id[$kvmap['P2A']] = $seq2;
				}

				// 将id映射到序号
				$p1 = $kvmap['P1A'] && strpos($kvmap['P1A'], "LIVE") === false && strpos($kvmap['P1A'], "COMEUP") === false && strpos($kvmap['P1A'], "TBD") === false ? $player2id[$kvmap['P1A']] : 0;
				$p2 = $kvmap['P2A'] && strpos($kvmap['P2A'], "LIVE") === false && strpos($kvmap['P2A'], "COMEUP") === false && strpos($kvmap['P2A'], "TBD") === false ? $player2id[$kvmap['P2A']] : 0;

				// 当8强已经产生时，前端默认展现8强签表
				if ($round == $blockRound + 1 && ($p1 != 0 || $p2 != 0)) $show_qf = true;

				// 查找当前所在block
				$block_num = $round <= $blockRound ? floor($seq1 / $blockCapacity) + 1 : 0;

				// right记录正确结果，fill记录填的结果，status表示正确与否
				// 首轮默认fill的都是正确答案，status都是正确的
				// 其他轮次，若填了就按填的结果来，没填则置0
				// 其他轮次，若填写的与正确答案一样，置正确答案不是0，则置状态为正确，若答案就是0，置等待结果，否则置错误
				$right[$block_num][$round][$seq1] = $p1;
				if ($round == 1) {
					$fill[$block_num][$round][$seq1] = $p1; 
					$status[$block_num][$round][$seq1] = 'right';
				} else {
					$fill[$block_num][$round][$seq1] = isset($user_fill[$round][$seq1]) ? $user_fill[$round][$seq1] : 0;
					$status[$block_num][$round][$seq1] = isset($user_fill[$round][$seq1]) && $user_fill[$round][$seq1] == $p1 && $p1 != 0 ? 'right' : ($p1 == 0 ? 'wait' : 'wrong');
				}

				$right[$block_num][$round][$seq2] = $p2;
				if ($round == 1) {
					$fill[$block_num][$round][$seq2] = $p2; 
					$status[$block_num][$round][$seq2] = 'right';
				} else {
					$fill[$block_num][$round][$seq2] = isset($user_fill[$round][$seq2]) ? $user_fill[$round][$seq2] : 0;
					$status[$block_num][$round][$seq2] = isset($user_fill[$round][$seq2]) && $user_fill[$round][$seq2] == $p2 && $p2 != 0 ? 'right' : ($p2 == 0 ? 'wait' : 'wrong');
				}

				// round为每个block最后一轮，以及8强block最后一轮，则往外再延伸一轮。根据本轮的结果来决定
				if ($round == $blockRound || $round == $totalRound) {
					++$round;
					$seq = $seq2;
					if (in_array($kvmap['mStatus'], ['F', 'H', 'J', 'L'])) {
						$right[$block_num][$round][$seq] = $player2id[$kvmap['P1A']];
					} else  if (in_array($kvmap['mStatus'], ['G', 'I', 'K', 'M'])) {
						$right[$block_num][$round][$seq] = $player2id[$kvmap['P2A']];
					} else {
						$right[$block_num][$round][$seq] = 0;
					}

					$fill[$block_num][$round][$seq] = isset($user_fill[$round][$seq]) ? $user_fill[$round][$seq] : 0;
					$status[$block_num][$round][$seq] = isset($user_fill[$round][$seq]) && $user_fill[$round][$seq] == $right[$block_num][$round][$seq] && $right[$block_num][$round][$seq] != 0 ? 'right' : ($right[$block_num][$round][$seq] == 0 ? 'wait' : 'wrong');
				}

			}
		}

		$ret['right'] = $right;
		$ret['fill'] = $fill;
		$ret['status'] = $status;
		$ret['info'] = $id2player;
		$ret['show_qf'] = $show_qf;

		$ddl = DcDdl::where(['year' => $this->year, 'eid' => $this->eid, 'sextip' => $this->sextip])->first();
		if (!$ddl) {
			$no_match = true;
			$ret['ddl'] = NULL;
		} else {
//			if (Auth::check() && Auth::user()->method == 11) $ddl->ddl = date('Y-m-d H:i:s', strtotime($ddl->ddl) + 86400);
			$no_match = false;
			if (time() >= strtotime($ddl->ddl)) {
				$timeout = true;
			} else {
				$timeout = false;
			}
			$ret['ddl'] = $ddl->ddl;
		}

		if (!Auth::check() || !$is_me || $no_match || $timeout) {
			$ret['permit'] = false;
			if (!Auth::check()) $ret['errcode'] = -1;
			else if ($no_match) $ret['errcode'] = -3;
			else if ($timeout) $ret['errcode'] = -2;
			else if (!$is_me) $ret['errcode'] = -4;
		} else {
			$ret['permit'] = true;
		}

	}

	protected function get_rank(&$ret) {

		$userid = $this->userid ? $this->userid : Auth::id();
		$one = DcRank::where(['year' => $this->year, 'eid' => $this->eid, 'sextip' => $this->sextip, 'userid' => $userid])->first();
		$ret['rank'] = 0;
		$ret['score'] = 0;
		$ret['matches'] = 0;
		$ret['username'] = "";
		$ret['method'] = "";

		if ($one) {
			$ret['rank'] = $one->rank;
			$ret['score'] = $one->score;
			$ret['matches'] = $one->matches;
			$ret['method'] = '<i class=iconfont>' . Config::get('const.TYPE2ICON.' . $one->method) . '</i>';
			$ret['username'] = $one->username;
		} else {
			if ($userid) {
				$ret['username'] = User::find($userid)->oriname;
				$ret['method'] = '<i class=iconfont>' . Config::get('const.TYPE2ICON.' . User::find($userid)->method) . '</i>';
			}
		}

	}
	
	protected function save(Request $req, $lang, $eid, $year, $sextip) {

		App::setLocale($lang);

		$ddl = DcDdl::where(['year' => $year, 'eid' => $eid, 'sextip' => $sextip])->first();
		if (!$ddl) return __('dc.errcode.noMatch');

		$ddl = $ddl->ddl;
//		if (Auth::check() && Auth::user()->method == 11) $ddl = date('Y-m-d H:i:s', strtotime($ddl) + 86400);

		if (!Auth::check()) {
			return __('dc.errcode.notLogin');
		} else if (time() >= strtotime($ddl)) {
			return __('dc.errcode.timeout');
		} else {

			$userid = Auth::id();
			$fills = $req->all();
			$legal = true;
			$res = [];
			foreach ($fills as $k => $v) {
				if (!preg_match('/^\d_\d{1,3}$/', $k) || !preg_match('/^\d{1,3}$/', $v)) {
					$legal = false;
					break;
				} else {
					$res[] = $k . '_' . $v;
				}
			}

			if (!$legal) {
				return __('dc.errcode.wrongInfo');
			} else {
				$one = DcFill::firstOrNew(['year' => $year + 0, 'eid' => $eid, 'sextip' => $sextip, 'userid' => $userid + 0]);
				$one->fill = join("|", $res);
				try {
					$ret = $one->save();
				} catch (Exception $e) {
					return __('dc.errcode.failed');
				}

				if ($ret) {
					return __('dc.errcode.success');
				} else {
					return __('dc.errcode.failed');
				}
			}
		}
	}

	protected function calc_rank($lang, $eid, $year, $sextip) {

		App::setLocale($lang);

		$this->eid = $eid;
		$this->year = $year;
		$this->sextip = $sextip;

		$right = [];

		$file = join("/", [env('ROOT'), "store", "draw", $this->year, $this->eid]);
		$cmd = "grep '^" . $this->sextip . "\t' $file";
		unset($r); exec($cmd, $r);

		if ($r) {
			$totalRound = ceil(log(count($r)) / log(2));
			$blockRound = $totalRound - 3;
			$blockCapacity = 1 << $blockRound;
			
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$kvmap = [];
				foreach (Config::get('const.schema_drawsheet') as $k => $v) $kvmap[$v] = @$arr[$k];
				$matchid = intval($kvmap['id']);
				$round = floor(($matchid % 1000) / 100);
				$seq = $matchid % 100;
				$seq1 = ($seq - 0.5) * (1 << $round);
				$seq2 = $seq * (1 << $round);

				if (strpos($kvmap['P1A'], "QUAL") !== false) $kvmap['P1A'] .= $seq1;
				if (strpos($kvmap['P2A'], "QUAL") !== false) $kvmap['P2A'] .= $seq2;
				
				// 首轮时记下每个id对应的序号
				if ($round == 1) {
					$player2id[$kvmap['P1A']] = $seq1;
					$player2id[$kvmap['P2A']] = $seq2;
					continue;
				}

				// 将id映射到序号
				$p1 = $kvmap['P1A'] && strpos($kvmap['P1A'], "LIVE") === false && strpos($kvmap['P1A'], "COMEUP") === false && strpos($kvmap['P1A'], "TBD") === false ? $player2id[$kvmap['P1A']] : 0;
				$p2 = $kvmap['P2A'] && strpos($kvmap['P2A'], "LIVE") === false && strpos($kvmap['P2A'], "COMEUP") === false && strpos($kvmap['P2A'], "TBD") === false ? $player2id[$kvmap['P2A']] : 0;

				// right记录正确结果
				$right[$round][$seq1] = $p1;
				$right[$round][$seq2] = $p2;

				if ($round == $totalRound) {
					++$round;
					$seq = $seq2;
					if (in_array($kvmap['mStatus'], ['F', 'H', 'J', 'L'])) {
						$right[$round][$seq] = $player2id[$kvmap['P1A']];
					} else  if (in_array($kvmap['mStatus'], ['G', 'I', 'K', 'M'])) {
						$right[$round][$seq] = $player2id[$kvmap['P2A']];
					} else {
						$right[$round][$seq] = 0;
					}
				}

			}
		}

		$user_count = [];
		$rows = DcFill::where(['year' => $this->year, 'eid' => $this->eid, 'sextip' => $this->sextip])->get();
		foreach  ($rows as $one) {
			$fills = $one->fill;
			$userid = $one->userid;
			$arr = explode("|", $fills);
			foreach ($arr as $grid) {
				if (!preg_match('/\d_\d{1,3}_\d{1,3}$/', $grid)) continue;
				$grid_arr = explode("_", $grid);
				$x = $grid_arr[0];
				$y = $grid_arr[1];
				$p = $grid_arr[2];
				if (!isset($user_count[$userid][$x])) $user_count[$userid][$x] = 0;
				if ($p == $right[$x][$y] && $right[$x][$y] != 0) ++$user_count[$userid][$x];
			}
		}

		$user = [];

		foreach ($user_count as $userid => $u) {

			foreach ($u as $round => $point) {

				if (!isset($user[$userid])) $user[$userid] = [$userid, 0, 0, 0]; // 分别代表得分，场次，场次指数（越后面的轮次优先级越高）

				$user[$userid][1] += $point * $this->round2point[$round];

				$user[$userid][2] += $point;

				if ($round > 2) {
					$user[$userid][3] += $point * pow(100, $round - 3);
				}
			}
		}

		usort($user, 'self::sort1');

		$users = User::all();
		foreach ($users as $u) {
			$username[$u->id] = $u->oriname;
			$usertype[$u->id] = $u->method;
		}

		$pre_point = 0;
		$pre_index = 0;
		$count = 0;
		$pre_rank = 0;
		foreach ($user as $u => $v) {
			++$count;
			if ($pre_point == $v[1] && $pre_index == $v[3]) $rank = $pre_rank; else $rank = $count;
			$pre_rank = $rank;
			$userid = $v[0];
			$point = $v[1];
			$matches = $v[2];
			$index = $v[3];

			$one = DcRank::updateOrCreate(
				['year' => $year, 'eid' => $eid, 'sextip' => $sextip, 'userid' => $userid],
				['method' => $usertype[$userid], 'username' => $username[$userid], 'rank' => $rank, 'score' => $point, 'matches' => $matches]
			);

			echo join("\t", [$userid, $usertype[$userid], $username[$userid], $rank, $point, $matches, $index]) . "<br>";
			$pre_point = $point;
			$pre_index = $index;
		}
	}

	protected function sort1($v1, $v2) {

		return $v1[1] != $v2[1] ? $v2[1] - $v1[1] : ($v1[3] != $v2[3] ? $v2[3] - $v1[3] : 0);

	}

	protected function get_tour_info(&$ret) {

		$file = join('/', [$this->root, 'store', 'calendar', '*', '*']);
		$cmd = "grep $'\t" . $this->eid . "\t' $file | sort -k5gr,5";
		unset($r); exec($cmd, $r);

		$ret['levels'] = [];
		$ret['date'] = [];
		$ret['title'] = [];
		$ret['city'] = [];
		$ret['surface'] = [];

		$cityData = [['City']];

		foreach ($r as $row) {
			$arr = explode("\t", $row);
			$level = preg_replace('/^.*:/', '', $arr[0]);
			$levels = explode('/', $level);
			$city = $arr[9];
			$year = $arr[4];

			if ($year == $this->year) {
				if (!in_array($arr[7], $ret['title'])) $ret['title'][] = $arr[7];
				if (!in_array($arr[8], $ret['surface'])) $ret['surface'][] = $arr[8];
				self::get_display_country($arr[10], $ret['country'], $ret['region']);
				$ret['levels'] = array_merge($ret['levels'], $levels);
				if (!in_array($city, $ret['city'])) {
					$ret['city'][] = $city;
					$cityData[] = [$city];
				}
				$ret['date'][] = $arr[5];
			}
		}

		$ret['date'] = join('/', $ret['date']);
		$ret['title'] = join('/', $ret['title']);

		$ret['cityData'] = $cityData;

	}

	protected function get_display_country($ioc, &$chartData, &$region) {
		if (!$ioc) {
			$chartData = [];
			$region = '';
			return;
		}

		if ($ioc == "CHN") {
			$countries = ['CHN', 'TPE'];
		} else if ($ioc == 'TCH') {
			$countries = ['CZE', 'SVK'];
		} else if ($ioc == 'URS') {
			$countries = ['RUS', 'UKR', 'BLR', 'UZB', 'KAZ', 'GEO', 'AZE', 'LAT', 'LTU', 'MDA', 'KGZ', 'TJK', 'ARM', 'TKM', 'EST'];
		} else if ($ioc == 'CIS') {
			$countries = ['RUS', 'UKR', 'BLR', 'UZB', 'KAZ', 'GEO', 'AZE', 'MDA', 'KGZ', 'TJK', 'ARM', 'TKM'];
		} else if ($ioc == 'YUG') {
			$countries = ['SRB', 'CRO', 'BIH', 'MNE', 'MKD', 'SLO', 'KOS'];
		} else if ($ioc == 'SCG') {
			$countries = ['SRB', 'MNE', 'KOS'];
		} else {
			$countries = [$ioc];
		}

		$chartData = [ ['Country', ''] ];
		foreach ($countries as $i) {
			$chartData[] = [Config::get('const.iso2.' . $i), translate('nationname', $ioc)];
		}

		if (in_array($ioc, ['TCH', 'YUG', 'SCG'])) {
			$region = Config::get('const.iso2ToSubContinent.' . $chartData[1][0]);
		} else {
			$region = Config::get('const.iso2.' . $countries[0]);
		}
	}

	protected function get_distribution($lang, $eid, $year, $sextip) {

		App::setLocale($lang);
		$this->eid = $eid;
		$this->year = $year;
		$this->sextip = $sextip;

		$file = join("/", [env('ROOT'), "store", "draw", $this->year, $this->eid]);
		$cmd = "grep '^" . $this->sextip . "\t' $file";
		unset($r); exec($cmd, $r);

		if (substr($sextip, 0, 1) == "M") {
			$file1 = join("/", [env('ROOT'), "atp", "player_portrait"]);
			$file2 = join("/", [env('ROOT'), "atp", "player_headshot"]);
		} else if (substr($sextip, 0, 1) == "W") {
			$file1 = join("/", [env('ROOT'), "wta", "player_portrait"]);
			$file2 = join("/", [env('ROOT'), "wta", "player_headshot"]);
		} else {
			return;
		}
		$big = $small = [];
		$fp = fopen($file1, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$big[$arr[0]] = preg_match('/^http/', $arr[2]) ? $arr[2] : url(join("/", ['images', (substr($sextip, 0, 1) == "M" ? 'atp' : 'wta') . '_portrait', $arr[2]]));
		}
		fclose($fp);
		$fp = fopen($file2, "r");
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);
			$small[$arr[0]] = preg_match('/^http/', $arr[2]) ? $arr[2] : url(join("/", ['images', (substr($sextip, 0, 1) == "M" ? 'atp' : 'wta') . '_headshot', $arr[2]]));
		}
		fclose($fp);

		$id2player = [];
		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				$kvmap = [];
				foreach (Config::get('const.schema_drawsheet') as $k => $v) $kvmap[$v] = @$arr[$k];
				$matchid = intval($kvmap['id']);
				$round = floor(($matchid % 1000) / 100);
				$seq = $matchid % 100;
				$seq1 = ($seq - 0.5) * (1 << $round);
				$seq2 = $seq * (1 << $round);

				// 首轮时记下每个id对应的序号
				if ($round == 1) {
					$p1 = rename2short($kvmap['P1AFirst'], $kvmap['P1ALast'], $kvmap['P1ANation']);
					$p2 = rename2short($kvmap['P2AFirst'], $kvmap['P2ALast'], $kvmap['P2ANation']);
					if ($kvmap['Seed1']) $p1 .= " [" . $kvmap['Seed1'] . "]";
					if ($kvmap['Seed2']) $p2 .= " [" . $kvmap['Seed2'] . "]";
					$kvmap['P1A'] = str_replace("wta", "", str_replace("atp", "", $kvmap['P1A']));
					$kvmap['P2A'] = str_replace("wta", "", str_replace("atp", "", $kvmap['P2A']));
					if (isset($big[$kvmap['P1A']])) {
						$id2player[$seq1] = ['name' => $p1, 'size' => 'big', 'img' => $big[$kvmap['P1A']]];
					} else if (isset($small[$kvmap['P1A']])) {
						$id2player[$seq1] = ['name' => $p1, 'size' => 'small', 'img' => $small[$kvmap['P1A']]];
					} else {
						$id2player[$seq1] = ['name' => $p1, 'size' => 'big', 'img' => $big['0000']];
					}
					if (isset($big[$kvmap['P2A']])) {
						$id2player[$seq2] = ['name' => $p2, 'size' => 'big', 'img' => $big[$kvmap['P2A']]];
					} else if (isset($small[$kvmap['P2A']])) {
						$id2player[$seq2] = ['name' => $p2, 'size' => 'small', 'img' => $small[$kvmap['P2A']]];
					} else {
						$id2player[$seq2] = ['name' => $p2, 'size' => 'big', 'img' => $big['0000']];
					}
				}
			}
		}

		$SF1 = $SF2 = $SF3 = $SF4 = $F1 = $F2 = $F12 = $W = [];

		$ones = DcFill::where(['year' => $this->year, 'eid' => $this->eid, 'sextip' => $this->sextip])->get();
		foreach ($ones as $one) {
			$arr = explode("|", $one->fill);
			if (count($arr) <= 1) continue;
			$a = explode("_", $arr[count($arr) - 1]);
			$totalround = $a[0];
			$drawsize = $a[1];
			$w = $a[2];

			$sf1 = $sf2 = $sf3 = $sf4 = $f1 = $f2 = null;

			foreach ($arr as $grid) {
				$a = explode("_", $grid);
				if ($a[0] == $totalround - 2) {
					if ($a[1] == $drawsize / 4) $sf1 = $a[2];
					else if ($a[1] == $drawsize / 2) $sf2 = $a[2];
					else if ($a[1] == $drawsize / 4 * 3) $sf3 = $a[2];
					else if ($a[1] == $drawsize) $sf4 = $a[2];
				} else if ($a[0] == $totalround - 1) {
					if ($a[1] == $drawsize / 2) $f1 = $a[2];
					else if ($a[1] == $drawsize) $f2 = $a[2];
				}
			}

			if ($sf1) {!isset($SF1[$sf1]) ? $SF1[$sf1] = 1 : $SF1[$sf1] += 1;}
			if ($sf2) {!isset($SF2[$sf2]) ? $SF2[$sf2] = 1 : $SF2[$sf2] += 1;}
			if ($sf3) {!isset($SF3[$sf3]) ? $SF3[$sf3] = 1 : $SF3[$sf3] += 1;}
			if ($sf4) {!isset($SF4[$sf4]) ? $SF4[$sf4] = 1 : $SF4[$sf4] += 1;}
			if ($f1) {!isset($F1[$f1]) ? $F1[$f1] = 1 : $F1[$f1] += 1;}
			if ($f2) {!isset($F2[$f2]) ? $F2[$f2] = 1 : $F2[$f2] += 1;}
			if ($w) {!isset($W[$w]) ? $W[$w] = 1 : $W[$w] += 1;}
			if ($f1 && $f2) {!isset($F12[$f1."\t".$f2]) ? $F12[$f1."\t".$f2] = 1 : $F12[$f1."\t".$f2] += 1;}
		}

		arsort($SF1);
		arsort($SF2);
		arsort($SF3);
		arsort($SF4);
		arsort($F1);
		arsort($F2);
		arsort($F12);
		arsort($W);
		return [
			$SF1, $SF2, $SF3, $SF4, $F1, $F2, $F12, $W,
			$id2player
		];
	}

	private function get_pid($p) {
		if (strpos($p, "atp") === 0 || strpos($p, "itf") === 0) {
			return substr($p, 3);
		} else if (strpos($p, "wta") === 0) {
			return intval(substr($p, 3));
		} else {
			return $p;
		}
	}
}
