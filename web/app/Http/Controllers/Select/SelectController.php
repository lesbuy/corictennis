<?php

namespace App\Http\Controllers\Select;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use DB;

class SelectController extends Controller
{

	public $tablename;
    //
	public function byyear($lang, $type, $sd = NULL, $period = NULL) {

		App::setLocale($lang);

		if ($sd === NULL && $period === NULL) {
			$this->tablename = 'profile_' . $type;
			$rows = DB::table($this->tablename)->select(DB::raw('year(birthday) as birthyear'))->whereYear('birthday', '>', 0)->distinct()->orderBy('birthyear', 'desc')->get();
		} else {
			if (!isset($sd) || !$sd) $sd = 's';
			if (!isset($type) || !$type) $type = 'atp';
			if (!isset($period) || !$period) $period = 'year';
			$this->tablename = join('_', ['rank', $type, $sd, $period, 'en']);
			$rows = DB::table($this->tablename)->select(DB::raw('year(birth) as birthyear'))->whereYear('birth', '>', 0)->distinct()->orderBy('birthyear', 'desc')->get();
		}

		$ret = [];
		foreach ($rows as $row) {
			$ret[] = $row->birthyear;
		}
		return json_encode($ret);
	}

	public function bycountry($lang, $type, $sd = NULL, $period = NULL) {

		App::setLocale($lang);

		if ($sd === NULL && $period === NULL) {
			$this->tablename = 'profile_' . $type;
			$rows = DB::table($this->tablename)->select('nation3 as ioc')->where('nation3', '<>', '')->distinct()->get();
		} else {
			if (!isset($sd) || !$sd) $sd = 's';
			if (!isset($type) || !$type) $type = 'atp';
			if (!isset($period) || !$period) $period = 'year';
			$this->tablename = join('_', ['rank', $type, $sd, $period, 'en']);
			$rows = DB::table($this->tablename)->select('ioc')->where('ioc', '<>', '')->distinct()->get();
		}

		$ret = [];
		$lists = [];

		if (App::isLocale('zh')) {
			$has_china = $has_hk = $has_tpe = false;
			foreach ($rows as $row) {
				if ($row->ioc == "CHN") {
					$has_china = true;
				} else if ($row->ioc == "HKG") {
					$has_hk = true;
				} else if ($row->ioc == "TPE") {
					$has_tpe = true;
				} else {
					$lists[$row->ioc] = iconv('UTF-8', 'GBK', __('nationname.' . $row->ioc));
				}
			}
			if (count($lists) > 0) {asort($lists);}

			if ($has_china) {$ret['CHN'] = __('nationname.CHN');}
			if ($has_hk) {$ret['HKG'] = __('nationname.HKG');}
			if ($has_tpe) {$ret['TPE'] = __('nationname.TPE');}

			foreach ($lists as $k => $v) {$ret[$k] = iconv('GBK', 'UTF-8', $v);}
		} else if (App::isLocale('ja')) {
			$has_japan = false;
			foreach ($rows as $row) {
				if ($row->ioc == "JPN") {
				  $has_japan = true;
				} else {
					$lists[$row->ioc] = iconv('UTF-8', 'Shift_JIS', __('nationname.' . $row->ioc));
				}
			}
			if (count($lists) > 0) {asort($lists);}

			if ($has_japan) {$ret['JPN'] = __('nationname.JPN');}

			foreach ($lists as $k => $v) {$ret[$k] = iconv('Shift_JIS', 'UTF-8', $v);}
		} else {
			foreach ($rows as $row) {$lists[$row->ioc] = __('nationname.' . $row->ioc);}

			if (count($lists) > 0) {asort($lists);}

			$ret = $lists;
//			foreach ($lists as $k => $v) {$ret[$k] = $v;}
		}

		return json_encode($ret, JSON_UNESCAPED_UNICODE);
	}

	public function bytour($lang, $type, $sd, $period) {

		App::setLocale($lang);

		if (!isset($sd) || !$sd) $sd = 's';
		if (!isset($type) || !$type) $type = 'atp';
		if (!isset($period) || !$period) $period = 'year';

		$this->tablename = join('_', ['rank', $type, $sd, $period, 'en']);

		$rows = DB::table($this->tablename)->select('w_tour')->where('w_tour', '<>', '')->distinct()->get();

		$ret = [];

		foreach ($rows as $row) {
			$ret[$row->w_tour] = translate_tour($row->w_tour);
		}
 
		return json_encode($ret, JSON_UNESCAPED_UNICODE);
	}

	public function byname(Request $req) {

		$type = resetParam($req->input('t'), 'atp');

		$queryStr = resetParam($req->input('n'), '');

		$regexp = "(^" . $queryStr . ")|([ -]" . $queryStr . ")";

		$this->tablename = 'all_name_' . $type;

		$rows = DB::table($this->tablename)->select('name', 'id', 'short')->where('name', 'regexp', $regexp)->orderBy('priority', 'asc')->orderBy('highest', 'asc')->take(30)->get();

		$ret = [];

		foreach ($rows as $row) {
			$ret[] = [$row->name, $row->id, $row->short];
		}

		return json_encode($ret, JSON_UNESCAPED_UNICODE);
	}

	public function bynation(Request $req) {

		$type = resetParam($req->input('t'), 'atp');

		$queryStr = resetParam($req->input('n'), '');

		$regexp = "(^" . $queryStr . ")|([ -]" . $queryStr . ")";

		$this->tablename = 'all_name_' . $type;

		$rows = DB::table($this->tablename)->select('nation', 'nat')->where('nation', 'regexp', $regexp)->where('nat', '<>', '')->where('nation', '<>', 'No Country Available')->where('nation', '<>', '')->distinct()->orderBy('nation', 'asc')->take(30)->get();

		$ret = [];

		foreach ($rows as $row) {
			$ret[] = [$row->nation, $row->nat, $row->nat];
		}

		return json_encode($ret, JSON_UNESCAPED_UNICODE);
	}

	public function byseason(Request $req) {

		$ret = [];

		$ret[] = ["ALL", -1];

		for ($i = 2020; $i >= 1968; --$i) {
			$ret[] = [$i, $i];
		}

		return json_encode($ret, JSON_UNESCAPED_UNICODE);
	}

}
