<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class IqiyiController extends Controller
{
    //
	public function list() {

		$url = "http://sports.iqiyi.com/resource/json/matchList/appMatchList_other_1.json";

		$json = json_decode(file_get_contents($url), true);

		foreach ($json['retData']['list'] as $court) {

			$type = @$court['match_desc'];
			if ($type != "网球") continue;

			$link = @$court['guid3'];
			$time = @$court['playTime'];
			$title = @$court['league_title'];
			$info = @$court['list_match_info'] . "\n" . @$court['live_match_info'];
			$state = @$court['state'];
			if ($state == 0) {
				$state = "未开始";
			} else if ($state == 1) {
				$state = "直播中";
			} else {
				$state = "已结束";
			}
			$ret[] = [$title, $time, $state, $info, $link];

		}

		return view('admin.iqiyilist', [
			'ret' => $ret,
		]);
	}

}
