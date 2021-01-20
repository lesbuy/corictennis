<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;
use Config;
use Auth;

class FrameController extends Controller
{
    //

	public function user($lang) {

		App::setLocale($lang);

		$ret = [
			'icons' => [
				'logout' => get_icon('zhuxiao'),
				'login' => get_icon('denglu'),
			],
			'texts' => [
				'login' => __('frame.menu.login'),
				'logout' => __('frame.menu.logout'),
			],
			'routes' => [
				'logout' => route('logout'),
			],
			'method' => [
				[get_icon(Config::get('const.TYPE2ICONNAME.0')), url('login/baidu')],
				[get_icon(Config::get('const.TYPE2ICONNAME.1')), url('login/weibo')],
				[get_icon(Config::get('const.TYPE2ICONNAME.7')), url('login/facebook')],
				[get_icon(Config::get('const.TYPE2ICONNAME.8')), url('login/google')],
			],
		];
				
		if (Auth::check()) {
			$ret['logined'] = true;
			$ret['myicon'] = get_icon(Config::get('const.TYPE2ICONNAME.' . Auth::user()->method));
			$ret['username'] = Auth::user()->oriname;
			$ret['avatar'] = Auth::user()->bigavatar;
		} else {
			$ret['logined'] = false;
			$ret['myicon'] = $ret['username'] = $ret['avatar'] = '';
		}

		return json_encode($ret);
	}

	public function menu($lang) {
		App::setLocale($lang);

		$menu = [
			['key' => __('frame.menu.home'), 'open' => -1, 'route' => self::add_lang("")],
			['key' => __('frame.menu.rank'), 'open' => 0, 'children' => [
				['key' => __('frame.menu.atp_s_year'), 'open' => -1, 'route' => self::add_lang('/rank/atp/s/year')],
				['key' => __('frame.menu.wta_s_year'), 'open' => -1, 'route' => self::add_lang('/rank/wta/s/year')],
				['key' => __('frame.menu.atp_d_year'), 'open' => -1, 'route' => self::add_lang('/rank/atp/d/year')],
				['key' => __('frame.menu.wta_d_year'), 'open' => -1, 'route' => self::add_lang('/rank/wta/d/year')],
				['key' => __('frame.menu.atp_s_race'), 'open' => -1, 'route' => self::add_lang('/rank/atp/s/race')],
				['key' => __('frame.menu.wta_s_race'), 'open' => -1, 'route' => self::add_lang('/rank/wta/s/race')],
				['key' => __('frame.menu.atp_s_nextgen'), 'open' => -1, 'route' => self::add_lang('/rank/atp/s/nextgen')],
				['key' => __('frame.menu.custom'), 'open' => -1, 'route' => self::add_lang('/rank/custom')],
			]],
			['key' => __('frame.menu.score'), 'open' => -1, 'route' => self::add_lang('/result/' . date('Y-m-d', time() - 3600 * 8))],
			['key' => __('frame.menu.calendar'), 'open' => -1, 'route' => self::add_lang("/calendar/2021")],
			['key' => __('frame.menu.draw'), 'open' => 0, 'children' => []],
			['key' => __('frame.menu.h2h'), 'open' => -1, 'route' => self::add_lang('/h2h')],
			['key' => __('frame.menu.dc'), 'open' => 0, 'children' => [
				['key' => "ATP " . translate_tour('Shanghai'), 'open' => -1, 'route' => self::add_lang('/dc/5014/2019/MS')],
				['key' => "WTA " . translate_tour('Beijing'), 'open' => -1, 'route' => self::add_lang('/dc/M015/2019/WS')],
				['key' => "WTA " . translate_tour('Wuhan'), 'open' => -1, 'route' => self::add_lang('/dc/1075/2019/WS')],
			]],
			['key' => __('frame.menu.guess.game'), 'open' => 0, 'children' => [
				['key' => __('frame.menu.guess.pick'), 'open' => -1, 'route' => self::add_lang('/guess')],
				['key' => __('frame.menu.guess.schedule'), 'open' => -1, 'route' => self::add_lang('/guess/calendar/2020')],
				['key' => __('frame.menu.guess.rule'), 'open' => -1, 'route' => self::add_lang('/guess/rule')],
				['key' => __('frame.menu.guess.itgl.race'), 'open' => 0, 'children' => [
					['key' => __('frame.menu.guess.itgl.year'), 'open' => -1, 'route' => self::add_lang('/guess/rank/itgl/year/0')],
					['key' => __('frame.menu.guess.itgl.day'), 'open' => -1, 'route' => self::add_lang('/guess/rank/itgl/day')],
					['key' => __('frame.menu.guess.itgl.week'), 'open' => -1, 'route' => self::add_lang('/guess/rank/itgl/week')],
					['key' => __('frame.menu.guess.itgl.all'), 'open' => -1, 'route' => self::add_lang('/guess/rank/itgl/all/0')],
				]],
				['key' => __('frame.menu.guess.dcpk.race'), 'open' => 0, 'children' => [
					['key' => __('frame.menu.guess.dcpk.year'), 'open' => -1, 'route' => self::add_lang('/guess/rank/dcpk/year/0')],
					['key' => translate_tour('London')." ".__('frame.menu.draw'), 'open' => -1, 'route' => self::add_lang('/draw/D46/2019')],
					['key' => translate_tour('Shenzhen')." ".__('frame.menu.draw'), 'open' => -1, 'route' => self::add_lang('/draw/D44/2019')],
					['key' => __('frame.menu.guess.dcpk.sign'), 'open' => -1, 'route' => self::add_lang('/guess/sign')],
				]],
			]],
			['key' => __('frame.menu.entrylist'), 'open' => 0, 'children' => [
				['key' => "ATP", 'open' => -1, 'route' => self::add_lang('/entrylist/atp')],
				['key' => "WTA", 'open' => -1, 'route' => self::add_lang('/entrylist/wta')],
			]],
			['key' => __('frame.menu.activity'), 'open' => -1, 'route' => self::add_lang('/history/activity')],
			['key' => __('frame.menu.tourquery'), 'open' => -1, 'route' => self::add_lang('/history/gst1')],
			['key' => __('frame.menu.historyRank'), 'open' => 0, 'children' => [
				['key' => __('frame.menu.officialRank'), 'open' => -1, 'route' => self::add_lang('/history/official')],
				['key' => __('frame.menu.rankEvolution'), 'open' => -1, 'route' => self::add_lang('/history/evolv')],
			]],
			['key' => __('frame.menu.topN'), 'open' => -1, 'route' => self::add_lang('/history/topn')],
			['key' => __('frame.menu.profile'), 'open' => 0, 'children' => [
				['key' => "ATP", 'open' => -1, 'route' => self::add_lang('/profile/atp')],
				['key' => "WTA", 'open' => -1, 'route' => self::add_lang('/profile/wta')],
			]],
		];

		$this_monday = date('Ymd', strtotime("+1 day"));
		$monday = strtotime("$this_monday last Monday");
		$monday_last = $monday - 7 * 86400;
		$monday_next = $monday + 7 * 86400;

		$ret = [];
		$ret[] = ['key' => __('frame.menu.drawList.ThisWeek'), 'open' => 0, 'children' => self::get_tours($monday)];
		$ret[] = ['key' => __('frame.menu.drawList.NextWeek'), 'open' => 0, 'children' => self::get_tours($monday_next)];
		$ret[] = ['key' => __('frame.menu.drawList.LastWeek'), 'open' => 0, 'children' => self::get_tours($monday_last)];

		$menu[4]['children'] = $ret;

		return json_encode($menu);
	}

	protected function get_tours($monday) {

		$year = date('Y', $monday);
		$files = [
			Config::get('const.root') . "/store/calendar/" . $year . "/GS", 
			Config::get('const.root') . "/store/calendar/" . $year . "/WT",
			Config::get('const.root') . "/store/calendar/" . $year . "/CH",
			Config::get('const.root') . "/store/calendar/" . $year . "/ITF",
		];

		$monday_last = $monday - 7 * 86400;

		$cmd = "grep -E \"$monday|$monday_last\" " . join(" ", $files);
		unset($r); exec($cmd, $r);

		$info = [];

		if ($r) {
			foreach ($r as $row) {
				$arr = explode("\t", $row);
				if ((@$arr[21] == 2 && $arr[6] == $monday_last)	|| $arr[6] == $monday) {
					$match = "";
					if (preg_match('/\/([A-Z]+):(.*)$/', $arr[0], $match)) {
						$level = $match[1];
						$eid = $arr[1];
						$year = $arr[4];
						$title = $arr[7];
						$sfc = $arr[8];
						$city = $arr[9];
						$ioc = $arr[10];

						if ($match[2] == "CH") $level = "ATP-Challenger";
						else if ($match[2] == "125K") $level = "WTA-125K";
						else if ($match[2] == "ITF") {
							if (substr($eid, 0, 1) == "M") {
								$level = "ITF-men";
							} else {
								$level = "ITF-women";
							}
						}
						$type = explode("/", $match[2]);
						$logo = [];
						foreach ($type as $k => $v) {
							$logo[] = get_tour_logo_by_id_type_name($eid, $v);
						}

						$link = join("/", ["draw", $eid, $year]);
						$info[] = ['key' => translate_tour(strtolower($city)), 'open' => -1, 'route' => self::add_lang($link), 'logo' => $logo];
					}
				}
			}
		}

		return $info;
	}

	protected function add_lang($uri) {
		return preg_replace('/\/$/', '', str_replace("//", "/", '/' . App::getLocale() . '/' . $uri));
	}

}
