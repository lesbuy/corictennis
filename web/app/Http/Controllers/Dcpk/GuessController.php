<?php

namespace App\Http\Controllers\Dcpk;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Scripts\Ssp;
use DB;
use Config;
use App;
use Auth;
use App\Models\DcpkMatch;
use App\Models\DcpkWinner;

class GuessController extends Controller
{

	protected $tbname_match;
	protected $me;
	protected $is_me;

	public function __construct() {
		$this->tbname_match = 'dcpk_matches';
		$this->is_me = true;
	}

	public function guess($lang, $date = NULL, $userid = NULL) {

		App::setLocale($lang);

		if ($userid === NULL || $userid == Auth::id()) {
			$this->me = Auth::id();
		} else {
			$this->me = $userid;
			$this->is_me = false;
		}

		if ($date === NULL) {
			$date = date('Y-m-d', time(null));
//			$date = '2017-10-12';
			$minDate = date('Y-m-d', strtotime($date . ' -20 day'));
			$maxDate = date('Y-m-d', strtotime($date . ' +4 day'));
		} else {
			$date = $minDate = $maxDate = date('Y-m-d', strtotime($date));
		}

		$rows = DB::table($this->tbname_match)->select('date')->whereBetween('date', [$minDate, $maxDate])->distinct()->orderBy('date', 'desc')->get();

		if (!$rows || !count($rows)) {
			return view('dcpk.guess', ['ret' => ['status' => -2]]);
		}

		$ret = ['status' => 0, 'matches' => [], 'fill' => [], 'name' => [], 'permit' => [], 'rank' => [], 'deadline' => []];

		self::process_matches($minDate, $maxDate, $ret['matches'], $ret['name'], $ret['permit'], $ret['deadline']);
		self::process_fill($minDate, $maxDate, $ret['fill']);
		self::process_rank($minDate, $maxDate, $ret['rank']);

		if (!Auth::check()) $ret['status'] = -1; // 没登入
		else if (!$this->is_me) $ret['status'] = -3; // 查看别人的

		if (Auth::check() && Auth::user()->status == 0) { // 0表示还没有判定状态
			if (Auth::user()->method == Config::get('const.USERTYPE_BAIDU')) {
				$reqUrl = "http://tieba.baidu.com/home/get/panel?ie=utf-8&un=" . Auth::user()->oriname;
				$resp = http($reqUrl);
				if ($resp) {
					$resp = json_decode($resp, true);
					if ($resp && $resp['error'] == '成功') {
						$account_age = $resp['data']['tb_age'];
						$account_postnum = $resp['data']['post_num'];
						if (preg_match('/万/', $account_postnum)) {
							$account_postnum = ($account_postnum + 0) * 10000;
						}
						if (time() - ($account_age + 0.049) * 365 * 86400 - strtotime(Auth::user()->created_at) < -65 * 86400) { // 注册时间到登入时间超过2个月认为正常
							Auth::user()->status = 1;
						} else if ($account_postnum > 50) { // 如果不到2个月，则要求发贴至少50
							Auth::user()->status = 1;
						} else {
							Auth::user()->status = 2;
						}
						Auth::user()->save();
					}
				}
			} else if (Auth::user()->method == Config::get('const.USERTYPE_WEIBO')) {
/*
				$reqUrl = "https://weibo.com/p/100505" . Auth::user()->uid . "/info?mod=pedit_more&sudaref=weibo.com";
				$cookie = "Cookie: SINAGLOBAL=1391463593631.7854.1481292718900; wb_cmtLike_1040597525=1; YF-Ugrow-G0=169004153682ef91866609488943c77f; YF-V5-G0=9717632f62066ddd544bf04f733ad50a; YF-Page-G0=d0adfff33b42523753dc3806dc660aa7; _s_tentry=login.sina.com.cn; Apache=2759530581427.143.1520099042207; ULV=1520099042226:23:1:1:2759530581427.143.1520099042207:1517319951408; TC-Ugrow-G0=968b70b7bcdc28ac97c8130dd353b55e; TC-V5-G0=458f595f33516d1bf8aecf60d4acf0bf; TC-Page-G0=8dc78264df14e433a87ecb460ff08bfe; login_sid_t=6fb5861ce9ae7962b32960d12303aa7e; cross_origin_proto=SSL; WBtopGlobal_register_version=d7a77880fa9c5f84; SSOLoginState=1520956041; un=lesbuy; wvr=6; UOR=,,zj.sina.com.cn; SUBP=0033WrSXqPxfM725Ws9jqgMF55529P9D9WhrG-al849z-acm-QRXxZYk5JpX5KMhUgL.Fo27Sh5f1KMfeo-2dJLoI0YLxKMLB.-L12-LxKMLB.BL1K2LxK-L1hqLBK5LxK-L1hqL1-zLxKBLBonL1KnLxK.L1hML12eLxK-LBK-L1hMt; ALF=1552667427; SCF=Akz_1Z87hr_t04koZm2p6E4IA5P4wLLA3WuCMlO9hwIyKBQtoO2TArPwuGA1zk1Vh5jl18Z6tMeOOY9svOycHv8.; SUB=_2A253ru_0DeRhGedO71IU-SnJyTmIHXVU2kY8rDV8PUNbmtBeLWXkkW9NXeQIhDL6xBPaxu3k3LdzPeo-x67tOL3T; SUHB=0u5gbqJlneAlx_";
				$resp = http($reqUrl, null, $cookie);
				if ($resp) {
					echo strlen($resp)."\n";
					$lines = explode("\n", $resp);
					foreach ($lines as $line) {
						if (preg_match('/Official_PersonalInfo/', $line) && !preg_match('/WB_frame_c/', $line)) {
							echo strlen($line)."\n";
							$line = str_replace("<script>FM.view(", "", $line);
							$line = str_replace(")</script>", "", $line);
							$jso = json_decode($line, true);
							echo urlencode($jso['html']);
							break;
						}
					}
				}
*/
			}
		}

		if ($ret['status'] == 0 && Auth::user()->status == 2) $ret['status'] = -4; // 违禁用户

//		echo json_encode($ret)."\n";
		return view('dcpk.guess', [
			'ret' => $ret, 
			'pageTitle' => __('frame.menu.guess.game'),
			'title' => __('frame.menu.guess.game'),
			'pagetype1' => 'dcpk',
			'pagetype2' => 'guess',
		]);
	}

	// 从比赛库中找出附近几天的比赛以及结果 
	protected function process_matches($minDate, $maxDate, &$m, &$n, &$permit, &$deadline) {

		$rows = DB::table($this->tbname_match)->whereBetween('date', [$minDate, $maxDate])->orderBy('id', 'desc')->get();

		$atp_id = [];
		$wta_id = [];

		foreach ($rows as $row) {

			$seq = $row->id;
			$date = $row->date;
			$city = $row->city;
			$round = $row->round;
			$p1 = explode('/', $row->p1);
			$p2 = explode('/', $row->p2);
			foreach (array_merge($p1, $p2) as $p) {
				if (preg_match('/^[A-Z0-9]{4}$/', $p)) {
					$atp_id[] = $p;
				} else if (preg_match('/^[0-9]{5,6}$/', $p)) {
					$wta_id[] = $p;
				}
			}
			$w = $row->winner;
			$s = $row->sets;
			$d = $row->duras;
			$a = $row->aces;
			$prob1 = $row->prob1;
			$prob2 = $row->prob2;
			$ddl = $row->ddl;

			if (!isset($deadline[$date])) {
				$deadline[$date] = 50;
			}
			if ($w != 3 && $ddl !== NULL && $ddl < $deadline[$date]) $deadline[$date] = $ddl;
			$m[$date][$seq] = [$city, $round, $p1, $p2, $prob1, $prob2, $w, $s, $a, $d];

			if (time(null) < strtotime($date) + $deadline[$date] * 3600) {
				$permit[$date] = true;
			} else {
				$permit[$date] = false;
			}

		}

		foreach (['atp', 'wta'] as $type) {

			if (count(${$type . '_id'}) > 0) {

				$profile_table = 'profile_' . $type;

				$rows = DB::table($profile_table)->select('longid', 'first_name', 'last_name', 'nation3')->whereIn('longid', ${$type . '_id'})->get();
				foreach ($rows as $row) {
					$n[strtoupper($row->longid)] = rename2short($row->first_name, $row->last_name, $row->nation3);
				}
			}
		}
	}

	// 找出当前用户在附近几天的填写结果
	protected function process_fill($minDate, $maxDate, &$f) {

		$months = [date('Y-m', strtotime($maxDate))];
		if (date('Y-m', strtotime($maxDate)) != date('Y-m', strtotime($minDate))) {
			$months[] = date('Y-m', strtotime($minDate));
		}

		foreach ($months as $month) {

			$tbname_fill = 'dcpk_fill.' . $month;
			$rows = DB::table($tbname_fill)->where('userid', $this->me)->whereBetween('date', [$minDate, $maxDate])->get();

			foreach ($rows as $row) {
				$seq = $row->seq;
				$w = $row->winner;
				$s = $row->sets;
				$a = $row->aces;
				$d = $row->duras;
				$p = $row->point;

				$f[$seq] = [$w, $s, $a, $d, $p];
			}
		}
	}

	// 找出当前用户在附近几天的名次和得分情况
	protected function process_rank($minDate, $maxDate, &$r) {

		$curDate = $minDate;
		while (true) {
			$dbname = 'dcpk_rank_day';
			$tbname_rank = 'd' . date('Ymd', strtotime($curDate));

			if (self::hasTable($dbname, $tbname_rank)) {
				$row = DB::table($dbname . '.' . $tbname_rank)->where('userid', $this->me)->first();
				if ($row) {
					$r[$curDate] = ['usertype' => $row->usertype, 'username' => $row->username, 'score' => $row->score, 'rank' => $row->rank, 'weekRank' => 0];
				} else {
					$r[$curDate] = NULL;
				}
			} else {
				$r[$curDate] = NULL;
			}

			$dcpk_winner = DcpkWinner::where('start', '<=', $curDate)->where('end', '>', $curDate)->first();
			if ($dcpk_winner && $r[$curDate] && count($r[$curDate]) == 5) {
				$year = $dcpk_winner->year;
				$week = $dcpk_winner->week;
				if ($week < 10) $week = 0 . $week;
				$tbname_rank = 'd' . $year . '_' . $week;

				if (self::hasTable($dbname, $tbname_rank)) {
					$row = DB::table($dbname . '.' . $tbname_rank)->where('userid', $this->me)->first();
					if ($row) {
						$r[$curDate]['weekRank'] = $row->rank;
					}
				}
			}

			if ($curDate == $maxDate) break;
			$curDate = date('Y-m-d', strtotime($curDate . ' +1 day'));
		}

	}

	// 用户提交结果
	public function submit(Request $req, $lang) {

		App::setLocale($lang);

		if (!Auth::check()) return __('dcpk.errcode.notLogin');

		if (Auth::user()->status == 2) return __('dcpk.errcode.notPermitted');

		$this->me = Auth::id();

		$params = $req->all();

		$date = '';
		$month = '';
		$res = [];

		foreach ($params as $k => $v) {

			if ($k == 'date') {
				$date = date('Y-m-d', strtotime($v));
				$month = date('Y-m', strtotime($v));
			} else if (preg_match('/^([a-z]+)([0-9]+)$/', $k, $m)) {
				if ($m[1] == 'winner') {
					$res[$m[2]]['winner'] = $v + 0;
				} else if ($m[1] == 'set') {
					$res[$m[2]]['sets'] = $v + 0;
				} else if ($m[1] == 'ace') {
					$res[$m[2]]['aces'] = $v + 0;
				} else if ($m[1] == 'hour') {
					@$res[$m[2]]['duras'] += $v * 60;
				} else if ($m[1] == 'minute') {
					@$res[$m[2]]['duras'] += $v + 0;
				}
			}
		}

		$ddl = DB::table($this->tbname_match)->where('date', $date)->min('ddl');
		if (time(null) >= strtotime($date) + $ddl * 3600) {
			return __('dcpk.errcode.timeout');
		}

		$data = [];
		if ($date != '' && count($res) > 0) {
			foreach ($res as $seq => $v) {
				$data[] = [
					'userid' => $this->me,
					'date' => $date,
					'seq' => $seq,
					'winner' => $v['winner'] + 0,
					'sets' => $v['sets'] + 0,
					'aces' => $v['aces'] + 0,
					'duras' => $v['duras'] + 0,
					'update_time' => date('Y-m-d H:i:s', time()),
				];
			}
		}

		$dbname = 'dcpk_fill';
		$tbname_fill = $month;

		if (count($data) > 0 && self::hasTable($dbname, $tbname_fill)) {

			if (DB::transaction(function () use ($dbname, $tbname_fill, $date, $data) {
				DB::table($dbname . '.' . $tbname_fill)->where([ ['userid', $this->me], ['date', $date] ])->delete();
				return DB::table($dbname . '.' . $tbname_fill)->insert($data);
				})) {
				return __('dcpk.errcode.success');
			} else {
				return __('dcpk.errcode.fail');
			}

		} else {
			return __('dcpk.errcode.fail');
		}

		return __('dcpk.errcode.success');
	}

	public function select($lang, $date) {

		App::setLocale($lang);

		$userid = Auth::id();

		if (!in_array($userid, [2999, 17540, 17541, 9066])) { return ""; }

		$avail = [];
		$matches = [];
		$rows = App\Models\DcpkMatch::where('date', $date)->get();
		foreach ($rows as $row) {
			$eid = $row->tourid;
			$matchid = $row->matchid;
			if ($row->winner != 3) {
				$avail[$eid . "`" . $matchid] = 1;
				$matches[] = [
					'eid' => $row->tourid,
					'matchid' => $row->matchid,
					'city' => $row->city,
					'round' => $row->round,
					'tour' => $row->tour,
					'p1id' => $row->p1,
					'p2id' => $row->p2,
					'p1eng' => '',
					'p2eng' => '',
					'earliest' => $row->ddl,
					'date' => $date,
				];
			}
		}

		$file = join('/', [Config::get('const.root'), 'share', '*completed', $date]);
		$schema = Config::get('const.schema_completed');

		$cmd = "cat $file | sort -s -t $\"\t\" -k4gr,4 | awk -F\"\\t\" '$4 >= 125000'";
		unset($r); exec($cmd, $r);

		$ret = ['date' => $date, 'avail' => $avail, 'matches' => $matches];
		$pre_eid = "";
		$true_tz = 8;
		foreach ($r as $row) {
			$arr = explode("\t", $row);
			$kvmap = [];
			foreach ($arr as $k => $v) $kvmap[Config::get('const.schema_completed.' . $k)] = $v;
			if (!isset($kvmap['eid'])) continue;

			if ($kvmap['eid'] != $pre_eid) $true_tz = 8;
			$pre_eid = $kvmap['eid'];

			$h = '';
			if (strpos($kvmap['schedule'], "[") === false) {
				$s = explode(",", $kvmap['schedule']);
			} else {
				$s = json_decode($kvmap['schedule'], true);
			}
			if (count($s) > 1 && $s[0] != "Followed By") {
/*
				$t = @$s[1];
				$tz = intval(preg_replace('/[,\]]/', "", @$s[4]));
				if ($tz != 8) $true_tz = $tz;
				$t1 = strtotime($date);
				$t2 = strtotime($date . " " . $t . " " . (8 - $tz) . " hours");
				$h = round(($t2 - $t1) / 3600, 1);

				if ($tz == 8 && $h - (8 - $true_tz) < 0) {
					$h += 24;
				}
*/
				$h = (intval(@$s[5]) - strtotime($date)) / 3600;
			}

			if (isset($avail[$kvmap['eid'] . "`" . $kvmap['matchid']])) continue;

			$ret['matches'][] = [
				'p1id' => $kvmap['p1id'],
				'p2id' => $kvmap['p2id'],
				'p1eng' => $kvmap['p1eng'],
				'p2eng' => $kvmap['p2eng'],
				'matchid' => $kvmap['matchid'],
				'eid' => $kvmap['eid'],
				'city' => $kvmap['city'],
				'tour' => $kvmap['tour'],
				'round' => $kvmap['round'],
				'date' => $date,
				'earliest' => $h,
			];
		}

//		echo json_encode($ret) . "\n";
		return view('dcpk.select', [
			'ret' => $ret,
			'pagetype1' => 'dcpk',
			'pagetype2' => 'select',
		]);
	}

	public function save(Request $req, $lang, $date) {

		$all = $req->all();

		$items = [];
		$tmp = [];

		foreach ($all as $k => $v) {

			$arr = explode("`", $k);
			$eid = $arr[0];
			$matchid = $arr[1];
			$type = $arr[2];
			if (!isset($items[$eid . "`" . $matchid])) {
				if ($dcpkMatch = App\Models\DcpkMatch::where('tourid', $eid)->where('matchid', $matchid)->where('date', $date)->first()) {
					$items[$eid . "`" . $matchid] = $dcpkMatch;
				} else {
					$items[$eid . "`" . $matchid] = new DcpkMatch;
				}
			}

			if ($type == "city") {
				$items[$eid . "`" . $matchid]->city = $v;
			} else if ($type == "round") {
				$items[$eid . "`" . $matchid]->round = $v;
			} else if ($type == "tour") {
				$items[$eid . "`" . $matchid]->tour = $v;
			} else if ($type == "eid") {
				$items[$eid . "`" . $matchid]->tourid = $v;
			} else if ($type == "matchid") {
				$items[$eid . "`" . $matchid]->matchid = $v;
			} else if ($type == "p1id") {
				$items[$eid . "`" . $matchid]->p1 = $v;
			} else if ($type == "p2id") {
				$items[$eid . "`" . $matchid]->p2 = $v;
			} else if ($type == "p1eng") {
				$tmp[$eid . "`" . $matchid]['p1eng'] = $v;
			} else if ($type == "p2eng") {
				$tmp[$eid . "`" . $matchid]['p2eng'] = $v;
			} else if ($type == "earliest") {
				$items[$eid . "`" . $matchid]->ddl = $v;
			}
		}

		$ret = "success";
		foreach ($items as $k => $v) {
			$items[$k]->date = $date;
			if (!preg_match('/^[A-Z0-9]{4,6}(\/[A-Z0-9]{4,6})?$/', $v->p1)) $items[$k]->p1 = $tmp[$k]['p1eng'];
			if (!preg_match('/^[A-Z0-9]{4,6}(\/[A-Z0-9]{4,6})?$/', $v->p2)) $items[$k]->p2 = $tmp[$k]['p2eng'];

			if (!$items[$k]->save()) {
				$ret = "fail";
			}
		}
//		echo json_encode($all) . "\n";
		return $ret;

	}

	public function saveOne(Request $req, $lang, $date) {

		$all = $req->all();

		$item = new DcpkMatch;
		$tmp = [];

		foreach ($all as $k => $v) {

			if ($k == "city") {
				$item->city = $v;
			} else if ($k == "round") {
				$item->round = $v;
			} else if ($k == "tour") {
				$item->tour = $v;
			} else if ($k == "eid") {
				$item->tourid = $v;
			} else if ($k == "matchid") {
				$item->matchid = $v;
			} else if ($k == "p1id") {
				$item->p1 = $v;
			} else if ($k == "p2id") {
				$item->p2 = $v;
			} else if ($k == "p1eng") {
				$tmp['p1eng'] = $v;
			} else if ($k == "p2eng") {
				$tmp['p2eng'] = $v;
			} else if ($k == "earliest") {
				$item->ddl = $v;
			}
		}

		$ret = "success";
		$item->date = $date;
		if (!preg_match('/^[A-Z0-9]{4,6}(\/[A-Z0-9]{4,6})?$/', $item->p1)) $item->p1 = $tmp['p1eng'];
		if (!preg_match('/^[A-Z0-9]{4,6}(\/[A-Z0-9]{4,6})?$/', $item->p2)) $item->p2 = $tmp['p2eng'];

		if (!$item->save()) {
			$ret = "fail";
		}
//		echo json_encode($all) . "\n";
		return $ret;

	}

	public function delete(Request $req, $lang, $date) {

		$all = $req->all();

		$items = [];

		$ret = "success";
		foreach ($all as $k => $v) {
			$arr = explode("`", $k);
			if ($arr[2] != "checkbox") continue;
			$eid = $arr[0];
			$matchid = $arr[1];

			if (!App\Models\DcpkMatch::where(['tourid' => $eid, 'matchid' => $matchid])->delete()) {
				$ret = "fail";
			}
		}

		return $ret;
	}
			
	public function abandon(Request $req, $lang, $date) {

		$all = $req->all();

		$items = [];

		$ret = "success";
		foreach ($all as $k => $v) {
			$arr = explode("`", $k);
			if ($arr[2] != "checkbox") continue;
			$eid = $arr[0];
			$matchid = $arr[1];

			if (!App\Models\DcpkMatch::where(['tourid' => $eid, 'matchid' => $matchid])->update(['winner' => 3])) {
				$ret = "fail";
			}
		}

		return $ret;
	}

	protected function hasTable($db, $tb) {
		$sql = "select TABLE_NAME from INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA='$db' and TABLE_NAME='$tb' ;";
		$row = DB::select($sql);
		if (!$row || !count($row)) {
			return false;
		}
		return true;
	}

	public function add_new_start_of_year($year) {

		DcpkWinner::where('year', $year)->delete();

		$ones = DcpkWinner::where('year', $year - 1)->get();

		foreach ($ones as $one) {

			$newone = new DcpkWinner;
			$newone->date = date('Y-m-d', strtotime($one->date) + 364 * 86400);
			$newone->year = $year;
			$newone->week = $one->week;
			$newone->eid = $one->eid;
			$newone->tour = $one->tour;
			$newone->level = $one->level;
			$newone->surface = $one->surface;
			$newone->start = date('Y-m-d H:i:s', strtotime($one->start) + 364 * 86400);
			$newone->end = date('Y-m-d H:i:s', strtotime($one->end) + 364 * 86400);

			$newone->save();

			echo $newone->date . " done\n";
		}
	}

	public function add_new_year($year) {

		if ($year != 2020) return;

		DcpkWinner::where('year', $year)->delete();

		$file = '/home/ubuntu/dcpk/calendar';
		$fp = fopen($file, "r");
		
		while ($line = trim(fgets($fp))) {
			$arr = explode("\t", $line);

			$newone = new DcpkWinner;
			$newone->date = $arr[0];
			$newone->year = $arr[1];
			$newone->week = $arr[2];
			$newone->eid = $arr[5];
			$newone->tour = $arr[6];
			$newone->level = $arr[7];
			$newone->surface = $arr[8];
			$newone->start = $arr[3];
			$newone->end = $arr[4];

			$newone->save();

			echo $newone->date . " done\n";
		}
		fclose($fp);
	}

}
