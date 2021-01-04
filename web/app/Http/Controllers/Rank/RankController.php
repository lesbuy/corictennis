<?php

namespace App\Http\Controllers\Rank;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Scripts\Ssp;
use Config;
use App;
use DB;

class RankController extends Controller
{
    //
	protected $infoKey;

	protected $primaryKey = 'id';

	protected $table;

	protected $columns;

	protected $sql_details;


	public function __construct() {

		$this->table = 'info';

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

	}

	public function index($lang, $type, $sd, $period) {

		App::setLocale($lang);

		if (!isset($sd) || !$sd) $sd = 's';
		if (!isset($type) || !$type) $type = 'atp';
		if (!isset($period) || !$period) $period = 'year';

		$tb = join('_', ['calc', $type, $sd, $period]);

		$this->infoKey = $tb . '_update_time';
		$update_time = DB::table($this->table)->where('key', $tb . '_update_time')->first();
		$update_time = $update_time ? $update_time->value_time : __('rank.table.timeTip.unknown');
			
		$this->infoKey = $tb . '_live_time';
		$live_time = DB::table($this->table)->where('key', $tb . '_live_time')->first();
		$live_time = $live_time ? date('Y-m-d', strtotime($live_time->value_time)) : __('rank.table.timeTip.unknown');

		$this->infoKey = $tb . '_official_time';
		$official_time = DB::table($this->table)->where('key', $tb . '_official_time')->first();
		$official_time = $official_time ? date('Y-m-d', strtotime($official_time->value_time)) : __('rank.table.timeTip.unknown');

		return view('rank.index', [
			'type' => $type, 
			'sd' => $sd,
			'period' => $period,
			'update_time' => $update_time,
			'live_time' => $live_time,
			'official_time' => $official_time,
			'pageTitle' => __('frame.menu.' . $type . '_' . $sd . '_' . $period) . ' ' . $live_time,
			'title' => __('frame.menu.' . $type . '_' . $sd . '_' . $period),
			'pagetype1' => 'rank',
			'pagetype2' => $type,
		]);
	}

	public function query(Request $req, $lang, $type, $sd, $period) {

		App::setLocale($lang);

		if (!isset($sd) || !$sd) $sd = 's';
		if (!isset($type) || !$type) $type = 'atp';
		if (!isset($period) || !$period) $period = 'year';

		$device = $req->input('device', 0);

		$this->table = join('_', ['rank', $type, $sd, $period, 'en']);

		$this->columns = [
			[ 'db' => 'change', 'dt' => 'change' ], //升降
			[ 'db' => 'c_rank', 'dt' => 'c_rank' ], //即时排名
			[ 'db' => 'f_rank', 'dt' => 'f_rank' ], //官方排名
			[ 'db' => 'highest', 'dt' => 'highest' ], //最高排名
			[ 'db' => 'point', 'dt' => 'point' ],  //即时积分
			[ 'db' => 'alt_point', 'dt' => 'alt_point' ], //替补分
			[ 'db' => 'flop', 'dt' => 'flop' ], //去除分
			[ 'db' => 'w_point', 'dt' => 'w_point' ], //本周新增
			[ 'db' => 'engname', 'dt' => 'full_name', 'formatter' => $device == 0 ? 
				function( $d, $row ) { $arr = explode(",", $d); return get_flag($row["ioc"]) . rename2long(trim(@$arr[1]), trim($arr[0]), $row["ioc"]);} : 
				function( $d, $row ) { $arr = explode(",", $d); return get_flag($row["ioc"]) . rename2short(trim(@$arr[1]), trim($arr[0]), $row["ioc"]);} ], //英文全名
			[ 'db' => 'engname', 'dt' => 'eng_name', 'formatter' => function( $d, $row ) { $arr = explode(",", $d); return trim(@$arr[1]) . " " . trim($arr[0]);} ],
			[ 'db' => 'age', 'dt' => 'age' ], //年龄
			[ 'db' => 'birth', 'dt' => 'birth', 'formatter' => function( $d, $row ) {return date( 'Y-m-d', strtotime($d));} ],  //生日
			[ 'db' => 'ioc', 'dt' => 'nation', 'formatter' => function( $d, $row ) {return translate('nationname', $d);} ], //国家
			[ 'db' => 'id', 'dt' => 'id' ], //球员id，该列不显示
			[ 'db' => 'ioc', 'dt' => 'ioc' ], //国家三字码，该列不显示
			[ 'db' => 'engname', 'dt' => 'engname' ], // 英文全名，作搜索用，该列不显示
			[ 'db' => 'titles', 'dt' => 'titles' ], //冠军数
			[ 'db' => 'tour_c', 'dt' => 'tour_c' ], //周期参赛数
			[ 'db' => 'mand_0', 'dt' => 'mand_0' ], //周期强制0
			[ 'db' => 'streak', 'dt' => 'streak' ], //连胜
			[ 'db' => 'prize', 'dt' => 'prize', 'formatter' => function( $d, $row ) {return '$'.number_format($d);} ], //奖金
			[ 'db' => 'win', 'dt' => 'win' ], //胜
			[ 'db' => 'lose', 'dt' => 'lose' ], //负
			[ 'db' => 'win_r', 'dt' => 'win_r', 'formatter' => function( $d, $row ) {return round($d * 100, 1)."%";} ],   //胜率
			[ 'db' => 'q_tour', 'dt' => 'q_tour', 'formatter' => function( $d, $row ) {return translate_tour($d);} ], //起计分赛事
			[ 'db' => 'q_point', 'dt' => 'q_point' ], //起计分
			[ 'db' => 'w_in', 'dt' => 'w_in' ], //是否存签，该列不显示
			[ 'db' => 'w_tour', 'dt' => 'w_tour', 'formatter' => function( $d, $row ) {return translate_tour($d) . " " . $row["w_round"];} ], //本周赛事
			[ 'db' => 'partner', 'dt' => 'partner', 'formatter' => function( $d, $row ) { $arr = explode(",", $d); return rename2short(trim(@$arr[1]), trim($arr[0])); }, ], //同伴中文简名
			[ 'db' => 'next_oppo', 'dt' => 'next_oppo', 'formatter' => function( $d, $row ) {$arr = explode("/", $d); foreach ($arr as $k => $p) {$ar = explode(",", trim($p)); $arr[$k] = rename2short(trim(@$ar[1]), trim($ar[0]));} return join("/", $arr);} ],  //下轮对手
			[ 'db' => 'next_h2h', 'dt' => 'next_h2h' ], //头对头
			[ 'db' => 'predict', 'dt' => 'predict_R64', 'formatter' => function( $d, $row ) {$s = "\1R64\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_R32', 'formatter' => function( $d, $row ) {$s = "\1R32\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_R16', 'formatter' => function( $d, $row ) {$s = "\1R16\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_QF', 'formatter' => function( $d, $row ) {$s = "\1QF\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_SF', 'formatter' => function( $d, $row ) {$s = "\1SF\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_F', 'formatter' => function( $d, $row ) {$s = "\1F\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_W', 'formatter' => function( $d, $row ) {$s = "\1W\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测

			// 下面不显示，仅提取数据之用
			[ 'db' => 'name', 'dt' => 'name' ],
			[ 'db' => 'w_round', 'dt' => 'w_round' ],
		];

		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);
	}

	public function new_query(Request $req, $lang, $type, $sd, $period) {

		App::setLocale($lang);

		if (!isset($sd) || !$sd) $sd = 's';
		if (!isset($type) || !$type) $type = 'atp';
		if (!isset($period) || !$period) $period = 'year';

		$device = $req->input('device', 0);

		$this->table = join('_', ['calc', $type, $sd, $period]);

		$this->columns = [
			[ 'db' => 'change', 'dt' => 'change' ], //升降
			[ 'db' => 'c_rank', 'dt' => 'c_rank' ], //即时排名
			[ 'db' => 'f_rank', 'dt' => 'f_rank' ], //官方排名
			[ 'db' => 'highest', 'dt' => 'highest' ], //最高排名
			[ 'db' => 'point', 'dt' => 'point' ],  //即时积分
			[ 'db' => 'alt_point', 'dt' => 'alt_point' ], //替补分
			[ 'db' => 'flop', 'dt' => 'flop' ], //去除分
			[ 'db' => 'w_point', 'dt' => 'w_point' ], //本周新增
			[ 'db' => 'first', 'dt' => 'full_name', 'formatter' => $device == 0 ? 
				function( $d, $row ) { $arr = explode(",", $d); return get_flag($row["ioc"]) . translate2long($row['id'], $row['first'], $row['last'], $row["ioc"]);} : 
				function( $d, $row ) { $arr = explode(",", $d); return get_flag($row["ioc"]) . translate2short($row['id'], $row['first'], $row['last'], $row["ioc"]);} ], //英文全名
			[ 'db' => 'engname', 'dt' => 'eng_name', ],
			[ 'db' => 'age', 'dt' => 'age' ], //年龄
			[ 'db' => 'birth', 'dt' => 'birth', 'formatter' => function( $d, $row ) {return date( 'Y-m-d', strtotime($d));} ],  //生日
			[ 'db' => 'ioc', 'dt' => 'nation', 'formatter' => function( $d, $row ) {return translate('nationname', $d);} ], //国家
			[ 'db' => 'id', 'dt' => 'id' ], //球员id，该列不显示
			[ 'db' => 'ioc', 'dt' => 'ioc' ], //国家三字码，该列不显示
			[ 'db' => 'engname', 'dt' => 'engname' ], // 英文全名，作搜索用，该列不显示
			[ 'db' => 'titles', 'dt' => 'titles' ], //冠军数
			[ 'db' => 'tour_c', 'dt' => 'tour_c' ], //周期参赛数
			[ 'db' => 'mand_0', 'dt' => 'mand_0' ], //周期强制0
			[ 'db' => 'streak', 'dt' => 'streak' ], //连胜
			[ 'db' => 'prize', 'dt' => 'prize', 'formatter' => function( $d, $row ) {return '$'.number_format($d);} ], //奖金
			[ 'db' => 'win', 'dt' => 'win' ], //胜
			[ 'db' => 'lose', 'dt' => 'lose' ], //负
			[ 'db' => 'win_r', 'dt' => 'win_r', 'formatter' => function( $d, $row ) {return round($d * 100, 1)."%";} ],   //胜率
			[ 'db' => 'q_tour', 'dt' => 'q_tour', 'formatter' => function( $d, $row ) {return translate_tour($d);} ], //起计分赛事
			[ 'db' => 'q_point', 'dt' => 'q_point' ], //起计分
			[ 'db' => 'w_in', 'dt' => 'w_in' ], //是否存签，该列不显示
			[ 'db' => 'w_tour', 'dt' => 'w_tour', 'formatter' => function( $d, $row ) {return translate_tour($d) . " " . $row["w_round"];} ], //本周赛事
			[ 'db' => 'partner_id', 'dt' => 'partner', 'formatter' => function( $d, $row ) {return translate2short($d, $row['partner_first'], $row['partner_last'], $row['partner_ioc']); }, ], //同伴中文简名
			[ 'db' => 'oppo_id', 'dt' => 'next_oppo', 'formatter' => function( $d, $row ) {
				$arr = explode("/", $d); $arr1 = explode("/", $row["oppo_first"]); $arr2 = explode("/", $row["oppo_last"]); $arr3 = explode("/", $row["oppo_ioc"]); 
				foreach ($arr as $k => $p) {
					$arr[$k] = translate2short($p, $arr1[$k], $arr2[$k], $arr3[$k]);
				}
				return join("/", $arr);
			} ],  //下轮对手
			[ 'db' => 'next_h2h', 'dt' => 'next_h2h' ], //头对头
			[ 'db' => 'predict', 'dt' => 'predict_R64', 'formatter' => function( $d, $row ) {$s = "\1R64\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_R32', 'formatter' => function( $d, $row ) {$s = "\1R32\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_R16', 'formatter' => function( $d, $row ) {$s = "\1R16\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_QF', 'formatter' => function( $d, $row ) {$s = "\1QF\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_SF', 'formatter' => function( $d, $row ) {$s = "\1SF\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_F', 'formatter' => function( $d, $row ) {$s = "\1F\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测
			[ 'db' => 'predict', 'dt' => 'predict_W', 'formatter' => function( $d, $row ) {$s = "\1W\2"; $t = strpos($d, $s); return $t === false ? "" : intval(substr($d, $t + strlen($s))); } ], //轮次预测

			// 下面不显示，仅提取数据之用
			[ 'db' => 'engname', 'dt' => 'name' ],
			[ 'db' => 'w_round', 'dt' => 'w_round' ],
			[ 'db' => 'last', 'dt' => 'last' ],
			[ 'db' => 'partner_first', 'dt' => 'partner_first' ],
			[ 'db' => 'partner_last', 'dt' => 'partner_last' ],
			[ 'db' => 'partner_ioc', 'dt' => 'partner_ioc' ],
			[ 'db' => 'oppo_first', 'dt' => 'oppo_first' ],
			[ 'db' => 'oppo_last', 'dt' => 'oppo_last' ],
			[ 'db' => 'oppo_ioc', 'dt' => 'oppo_ioc' ],
		];

		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);
	}

}
