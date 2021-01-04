<?php

namespace App\Http\Controllers\Rank;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Scripts\Ssp;
use Config;
use App;
use DB;

class ProfileController extends Controller
{
    //
	protected $infoKey;

	protected $primaryKey = 'longid';

	protected $table;

	protected $columns;

	protected $sql_details;

	public function __construct() {

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

	}

	public function index($lang, $type) {

		App::setLocale($lang);

		if (!isset($type) || !$type) $type = 'atp';

		return view('profile.index', [
			'type' => $type, 
			'pageTitle' => strtoupper($type) . " " . __('frame.menu.profile'),
			'title' => strtoupper($type) . " " . __('frame.menu.profile'),
			'pagetype1' => 'profile',
			'pagetype2' => $type,
		]);
	}

	public function query(Request $req, $lang, $type) {

		App::setLocale($lang);

		$device = $req->input('device', 0);

		if (!isset($type) || !$type) $type = 'atp';

		$this->table = 'profile_' . $type;

		$this->columns = [
			['db' => 'longid', 'dt' => 'id', ],
			['db' => 'name', 'dt' => 'name', 'formatter' => $device == 0 ? 
				function($d, $row) {return get_flag($row['nation3']) . rename2long($row['first_name'], $row['last_name'], $row['nation3']);} : 
				function($d, $row) {return get_flag($row['nation3']) . rename2short($row['first_name'], $row['last_name'], $row['nation3']);}
			],
			['db' => 'nation3', 'dt' => 'ioc', ],
			['db' => 'nation3', 'dt' => 'nationfull', 'formatter' => function($d, $row) {return translate('nationname', $d);} ],
			['db' => 'birthday', 'dt' => 'birthday', ],
			['db' => 'birthplace', 'dt' => 'birthplace', ],
			['db' => 'residence', 'dt' => 'residence', ],
			['db' => 'height_bri', 'dt' => 'height_bri', ],
			['db' => 'height', 'dt' => 'height', ],
			['db' => 'weight_bri', 'dt' => 'weight_bri', ],
			['db' => 'weight', 'dt' => 'weight', ],
			['db' => 'hand', 'dt' => 'hand', ],
			['db' => 'backhand', 'dt' => 'backhand', ],
			['db' => 'proyear', 'dt' => 'proyear', ],
			['db' => 'pronoun', 'dt' => 'pronoun', ],
			['db' => 'website', 'dt' => 'website', ],
			['db' => 'prize_c', 'dt' => 'prize_c', ],
			['db' => 'prize_y', 'dt' => 'prize_y', ],
			['db' => 'rank_s', 'dt' => 'rank_s', 'formatter' => function ($d, $row) {return $d == 9999 || $d == "" || $d == "-" ? "-" : $d;} ],
			['db' => 'rank_s_hi', 'dt' => 'rank_s_hi', 'formatter' => function ($d, $row) {return $d == 9999 || $d == "" || $d == "-" ? "-" : $d;} ],
			['db' => 'rank_s_hi_date', 'dt' => 'rank_s_hi_date', ],
			['db' => 'title_s_c', 'dt' => 'title_s_c', ],
			['db' => 'title_s_y', 'dt' => 'title_s_y', ],
			['db' => 'win_s_c', 'dt' => 'win_s_c', ],
			['db' => 'lose_s_c', 'dt' => 'lose_s_c', ],
			['db' => 'win_s_y', 'dt' => 'win_s_y', ],
			['db' => 'lose_s_y', 'dt' => 'lose_s_y', ],
			['db' => 'rank_d', 'dt' => 'rank_d', 'formatter' => function ($d, $row) {return $d == 9999 || $d == "" || $d == "-" ? "-" : $d;} ],
			['db' => 'rank_d_hi', 'dt' => 'rank_d_hi', 'formatter' => function ($d, $row) {return $d == 9999 || $d == "" || $d == "-" ? "-" : $d;} ],
			['db' => 'rank_d_hi_date', 'dt' => 'rank_d_hi_date', ],
			['db' => 'title_d_c', 'dt' => 'title_d_c', ],
			['db' => 'title_d_y', 'dt' => 'title_d_y', ],
			['db' => 'win_d_c', 'dt' => 'win_d_c', ],
			['db' => 'lose_d_c', 'dt' => 'lose_d_c', ],
			['db' => 'win_d_y', 'dt' => 'win_d_y', ],
			['db' => 'lose_d_y', 'dt' => 'lose_d_y', ],

			['db' => 'first_name', 'dt' => 'first', ],
			['db' => 'last_name', 'dt' => 'last', ],
		];

		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);
	}

}
