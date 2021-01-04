<?php

namespace App\Http\Controllers\Dcpk;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Scripts\Ssp;
use DB;
use Config;
use App;
use Auth;

class RankController extends Controller
{

	protected $infoKey;

	protected $primaryKey = 'id';

	protected $table;

	protected $columns;

	protected $sql_details;

	public function __construct() {


	}

	public function index($lang, $sd, $gran, $date) {

		App::setLocale($lang);

		$title = __('frame.menu.guess.game') . ' ' . __('frame.menu.guess.' . $sd . '.race') . ' ' . __('frame.menu.guess.' . $sd . '.' . $gran);

		return view('dcpk.index', [
			'date' => $date, 
			'sd' => $sd, 
			'gran' => $gran,
			'title' => $title,
			'pageTitle' => $title,
			'pagetype1' => 'dcpk',
			'pagetype2' => 'rank',
		]);

	}

	public function query(Request $req, $lang, $sd, $gran, $date) {

		App::setLocale($lang);

		if ($sd == "itgl" || $sd == "dcpk") {

			if ($gran == "day" || $gran == "week" || $gran == "all") {
				$dbname = 'dcpk_rank_day';

				if ($gran == "all") {
					$tbname = 'dall';
				} else if ($gran == "day") {
					$tbname = 'd' . date('Ymd', strtotime($date));
				} else if ($gran == "week") {
					$tbname = 'd' . $date;
				}
			} else {
				$dbname = env('DB_DATABASE');
				$tbname = 'rank_guess_' . $sd . '_' . $gran;
			}

		} else {
			return '{"data": [], "recordsFiltered":0, "recordsTotal":0, "draw":1}';
		}

		if (!self::hasTable($dbname, $tbname)) {
			return '{"data": [], "recordsFiltered":0, "recordsTotal":0, "draw":1}';
		}

		$this->table = $tbname;

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => $dbname,
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

		if ($gran != 'year') {
			$this->columns = [
				[ 'db' => 'userid', 'dt' => 'userid' ],
				[ 'db' => 'username', 'dt' => 'username', 'formatter' => function( $d, $row ) {return '<img class=login_img src="' . url(env('CDN') . '/images/login/' . Config::get('const.TYPE2STRING.' . $row['usertype']) . '.png') . '" />' . $d;} ],
				[ 'db' => 'score', 'dt' => 'score' ],
				[ 'db' => 'matches', 'dt' => 'matches' ],
				[ 'db' => 'rank', 'dt' => 'rank' ],
				[ 'db' => 'userid', 'dt' => 'link', 'formatter' => function( $d, $row ) use ($lang, $date) {return url(join('/', [$lang, 'guess', date('Y-m-d', strtotime($date)), $d]));} ],
				[ 'db' => 'display', 'dt' => 'point', ],
				[ 'db' => 'matches', 'dt' => 'scorePerMatch', 'formatter' => function( $d, $row ) { return $d > 0 ? sprintf("%.2f", $row['score'] / $d / 100) : '0.00'; }],

				[ 'db' => 'usertype', 'dt' => 'usertype' ],
				[ 'db' => 'userid', 'dt' => 'me', 'formatter' => function( $d, $row ) { return $d == Auth::id() ? 1 : 0; } ],

				[ 'db' => 'display', 'dt' => 'last' ],
				[ 'db' => 'display', 'dt' => 'change' ],
				[ 'db' => 'display', 'dt' => 'tour_c' ],
				[ 'db' => 'display', 'dt' => 'streak' ],
				[ 'db' => 'display', 'dt' => 'win' ],
				[ 'db' => 'display', 'dt' => 'lose' ],
				[ 'db' => 'display', 'dt' => 'win_r' ],
				[ 'db' => 'display', 'dt' => 'w_point' ],
				[ 'db' => 'display', 'dt' => 'w_tour' ],
				[ 'db' => 'display', 'dt' => 'next_oppo' ],
				[ 'db' => 'display', 'dt' => 'q_tour' ],
				[ 'db' => 'display', 'dt' => 'q_point' ],
			];
		} else {
			$this->columns = [
				[ 'db' => 'engname', 'dt' => 'username', 'formatter' => function( $d, $row ) {return '<img class=login_img src="' . url(env('CDN') . '/images/login/' . Config::get('const.TYPE2STRING.' . $row['ioc']) . '.png') . '" />' . $d;} ],
				[ 'db' => 'c_rank', 'dt' => 'rank' ],
				[ 'db' => 'f_rank', 'dt' => 'last' ],
				[ 'db' => 'change', 'dt' => 'change' ], //升降
				[ 'db' => 'point', 'dt' => 'point' ],

				[ 'db' => 'tour_c', 'dt' => 'tour_c' ], //周期参赛数
				[ 'db' => 'streak', 'dt' => 'streak' ], //连胜
				[ 'db' => 'win', 'dt' => 'win' ], //胜
				[ 'db' => 'lose', 'dt' => 'lose' ], //负
				[ 'db' => 'win_r', 'dt' => 'win_r', 'formatter' => function( $d, $row ) {return round($d * 100, 1)."%";} ],   //胜率
				[ 'db' => 'q_tour', 'dt' => 'q_tour', 'formatter' => function( $d, $row ) {return translate_tour($d);} ], //起计分赛事
				[ 'db' => 'q_point', 'dt' => 'q_point' ], //起计分
				[ 'db' => 'w_in', 'dt' => 'w_in' ], //是否存签，该列不显示
				[ 'db' => 'w_point', 'dt' => 'w_point' ],
				[ 'db' => 'w_tour', 'dt' => 'w_tour', 'formatter' => function( $d, $row ) {return translate_tour($d) . " " . $row["w_round"];} ], //本周赛事
				[ 'db' => 'next_oppo', 'dt' => 'next_oppo', ],  //下轮对手

				[ 'db' => 'id', 'dt' => 'me', 'formatter' => function( $d, $row ) { return $d == Auth::id() ? 1 : 0; } ],

				[ 'db' => 'id', 'dt' => 'userid' ],
				[ 'db' => 'ioc', 'dt' => 'usertype' ],
				[ 'db' => 'w_round', 'dt' => 'w_round' ],

				[ 'db' => 'ioc', 'dt' => 'matches' ],
				[ 'db' => 'ioc', 'dt' => 'scorePerMatch' ],
			];

		}

		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);

	}

	protected function hasTable($db, $tb) {
		$sql = "select TABLE_NAME from INFORMATION_SCHEMA.TABLES where TABLE_SCHEMA='$db' and TABLE_NAME='$tb' ;";
		$row = DB::select($sql);
		if (!$row || !count($row)) {
			return false;
		}
		return true;
	}

}
