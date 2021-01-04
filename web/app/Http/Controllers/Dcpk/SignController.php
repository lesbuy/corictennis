<?php

namespace App\Http\Controllers\Dcpk;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App;
use Config;
use Auth;
use App\Models\DcpkSign;
use App\Models\DcpkFakeSign;
use App\Models\DcpkMatch;
use App\Models\DcpkWinner;
use App\Scripts\Ssp;

class SignController extends Controller
{
	protected $primaryKey = "id";
	protected $table;
	protected $sql_details;
	protected $columns;
	protected $root_path = '/home/ubuntu/dcpk';

	public function index($lang, $year, $week) {

		App::setLocale($lang);

		$ret = ['status' => -1];

		if (Auth::check()) {

			$ds = DcpkWinner::where('year', $year)->where('week', $week)->whereIn('level', ['1000', 'GS'])->first();

			$ret['errmsg'] = __('dcpk.sign.noTour');
			$ret['userid']= Auth::id();

			if ($ds) {
				$ret['status'] = 0;

				$ret['start'] = date('Y-m-d', strtotime($ds->start));
				$ret['end'] = date('Y-m-d', strtotime($ds->end));
				$ret['tour'] = translate_tour($ds->tour);
				$ret['level'] = $ds->level;

				$ddl = DcpkMatch::where('date', $ret['start'])->min('ddl');
				if (!$ddl) {
					$ret['ddl'] = NULL;
				} else {
					$ddl -= 3;
					$ret['ddl'] = date('Y-m-d H:i:s', strtotime($ret['start']) + $ddl * 3600);
				}
			}
		} else {

			$ret['errmsg'] = __('dcpk.sign.notLogin');

		}

//		echo json_encode($ret);
		return view('dcpk.sign', [
			'ret' => $ret, 
			'year' => $year, 
			'week' => $week, 
			'pageTitle' => __('dcpk.title.sign'),
			'title' => __('dcpk.title.sign'),
			'pagetype1' => 'dcpk',
			'pagetype2' => 'sign',
		]);
	}

	public function query(Request $req, $lang) {

		App::setLocale($lang);

		$this->table = 'dcpk_signs';

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

		$this->columns = [
			[ 'db' => 'year', 'dt' => 'year' ],
			[ 'db' => 'week', 'dt' => 'week' ],
			[ 'db' => 'created_at', 'dt' => 'signTime' ],
			[ 'db' => 'itglPoint', 'dt' => 'itglPoint' ],
			[ 'db' => 'dcpkPoint', 'dt' => 'dcpkPoint' ],
			[ 'db' => 'qualifySeq', 'dt' => 'qualifySeq' ],
			[ 'db' => 'drawSeq', 'dt' => 'drawSeq' ],
			[ 'db' => 'username', 'dt' => 'username', 'formatter' => function ($d, $row) {return '<img class=login_img src="' . url(env('CDN') . '/images/login/' . Config::get('const.TYPE2STRING.' . $row['method']) . '.png') . '" />' . $d;} ],

			[ 'db' => 'userid', 'dt' => 'userid' ],
			[ 'db' => 'method', 'dt' => 'method' ],
		];


		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);
	}

	public function save(Request $req, $lang) {

		App::setLocale($lang);

		if (!Auth::check()) {
			return __('dcpk.errcode.notLogin');
		}
		$id = $req->input('id');
		if ($id != Auth::id()) {
			return __('dcpk.errcode.wrongInfo');
		}

        $ip = getIP();
        $ua = $_SERVER['HTTP_USER_AGENT'];
		$uuid = $req->input('uuid', 0);
		if ($uuid != 0) {
			$coo = $uuid;
		} else {
			return __('dcpk.errcode.wrongBrowser');
//	        $coo = phpencrypt($ip . "_" . substr($ua, strlen($ua)-35, 15));
		}

		$year = $req->input('year');
		$week = $req->input('week');

		$arr = explode("_", $uuid);
/*
		if (count($arr) != 3 || time() - strtotime($arr[1] . " " . $arr[2]) < 86400 * 5) {
			$fakeSign = new DcpkFakeSign;
			$fakeSign->year = $year;
			$fakeSign->week = $week;
			$fakeSign->userid = $id;
			$fakeSign->method = Auth::user()->method;
			$fakeSign->username = Auth::user()->oriname;
			$fakeSign->ip = $ip;
			$fakeSign->ua = $ua;
			$fakeSign->code = $coo;
			$fakeSign->save();
			return __('dcpk.errcode.wrongDevice');
		}
*/

		$rows = DcpkSign::where('year', $year)->where('week', $week)->where('ip', $ip)->get();
		if (count($rows) >= 3) {
			$fakeSign = new DcpkFakeSign;
			$fakeSign->year = $year;
			$fakeSign->week = $week;
			$fakeSign->userid = $id;
			$fakeSign->method = Auth::user()->method;
			$fakeSign->username = Auth::user()->oriname;
			$fakeSign->ip = $ip;
			$fakeSign->ua = $ua;
			$fakeSign->code = $coo;
			$fakeSign->save();
			return __('dcpk.errcode.ipBlocked');
		}

		$rows = DcpkSign::where('year', $year)->where('week', $week)->where('code', $coo)->get();
		if (count($rows) >= 3) {
			$fakeSign = new DcpkFakeSign;
			$fakeSign->year = $year;
			$fakeSign->week = $week;
			$fakeSign->userid = $id;
			$fakeSign->method = Auth::user()->method;
			$fakeSign->username = Auth::user()->oriname;
			$fakeSign->ip = $ip;
			$fakeSign->ua = $ua;
			$fakeSign->code = $coo;
			$fakeSign->save();
			return __('dcpk.errcode.deviceBlocked');
		}

		$signInfo = DcpkSign::where('year', $year)->where('week', $week)->where('userid', $id)->first();
		if ($signInfo) {
			return __('dcpk.errcode.signed');
		}
		$newSign = new DcpkSign;
		$newSign->year = $year;
		$newSign->week = $week;
		$newSign->userid = $id;
		$newSign->method = Auth::user()->method;
		$newSign->username = Auth::user()->oriname;

		$newSign->ip = $ip;
		$newSign->ua = $ua;
		$newSign->code = $coo;

		$date = date('Y-m-d', strtotime($year . '-01-01 this Monday ' . ($week - 2) . ' weeks'));

		$file = $this->root_path . '/ranks_itgl/' . $date;
		if (file_exists($file)) {
			$cmd = "grep '^$id\t' $file | cut -f2";
			unset($r); exec($cmd, $r);
			if ($r) {
				$newSign->itglPoint = $r[0] + 0;
			} else {
				$newSign->itglPoint = 0;
			}
		}
		$file = $this->root_path . '/ranks_dcpk/' . $date;
		if (file_exists($file)) {
			$cmd = "grep '^$id\t' $file | cut -f2";
			unset($r); exec($cmd, $r);
			if ($r) {
				$newSign->dcpkPoint = $r[0] + 0;
			} else {
				$newSign->dcpkPoint = 0;
			}
		}
		if (!$newSign->save()) {
			return __('dcpk.errcode.failed');
		}

		return __('dcpk.errcode.success');
	}

	public function mend_point($lang, $year, $week) {

		$date = date('Y-m-d', strtotime($year . '-01-01 this Monday ' . ($week - 2) . ' weeks'));
		$file = $this->root_path . '/ranks_itgl/' . $date;
		if (file_exists($file)) {
			$cmd = "cat $file | cut -f1,2";
			unset($r); exec($cmd, $r);
			if ($r) {
				foreach ($r as $row) {
					$arr = explode("\t", $row);
					$id2itgl[$arr[0]] = $arr[1];
				}
			}
		}
		$file = $this->root_path . '/ranks_dcpk/' . $date;
		if (file_exists($file)) {
			$cmd = "cat $file | cut -f1,2";
			unset($r); exec($cmd, $r);
			if ($r) {
				foreach ($r as $row) {
					$arr = explode("\t", $row);
					$id2dcpk[$arr[0]] = $arr[1];
				}
			}
		}

		$signs = DcpkSign::where('year', $year)->where('week', $week)->whereNull('itglPoint')->get();

		foreach ($signs as $sign) {
			$itgl = 0;
			if (isset($id2itgl[$sign->userid])) {
				$itgl = $id2itgl[$sign->userid];
			}
			$dcpk = 0;
			if (isset($id2dcpk[$sign->userid])) {
				$dcpk = $id2dcpk[$sign->userid];
			}
			$sign->itglPoint = $itgl;
			$sign->dcpkPoint = $dcpk;

			$sign->save();
			echo $sign->username . " done<br>\n";
		}
	}
}
