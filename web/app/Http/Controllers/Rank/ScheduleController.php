<?php

namespace App\Http\Controllers\Rank;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Scripts\Ssp;
use Config;
use App;
use DB;

class ScheduleController extends Controller
{
	protected $infoKey;

	protected $primaryKey = 'playerid';

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

		return view('entrylist.index', [
			'type' => $type, 
			'pageTitle' => strtoupper($type) . " " . __('frame.menu.entrylist'),
			'title' => strtoupper($type) . " " . __('frame.menu.entrylist'),
			'pagetype1' => 'entrylist',
			'pagetype2' => $type,
		]);
	}

	public function query(Request $req, $lang, $type) {

		App::setLocale($lang);

		if (!isset($type) || !$type) $type = 'atp';

		$this->table = 'schedule_' . $type;

		$this->columns = [
			[ 'db' => 'rank', 'dt' => 'rank' ],
			[ 'db' => 'last', 'dt' => 'name', 'formatter' => function ($d, $row) { return get_flag($row['engcty']) . rename2long($row['first'], $d, $row['engcty']); } ],
			[ 'db' => 'engcty', 'dt' => 'ioc', ],
			[ 'db' => 'eng0', 'dt' => 'week1', 'formatter' => function ($d, $row) { return join("<br>", array_map(function ($v) { return translate_tour($v); }, explode("<br>", $d))); } ],
			[ 'db' => 'eng1', 'dt' => 'week2', 'formatter' => function ($d, $row) { return join("<br>", array_map(function ($v) { return translate_tour($v); }, explode("<br>", $d))); } ],
			[ 'db' => 'eng2', 'dt' => 'week3', 'formatter' => function ($d, $row) { return join("<br>", array_map(function ($v) { return translate_tour($v); }, explode("<br>", $d))); } ],
			[ 'db' => 'eng3', 'dt' => 'week4', 'formatter' => function ($d, $row) { return join("<br>", array_map(function ($v) { return translate_tour($v); }, explode("<br>", $d))); } ],
			[ 'db' => 'eng4', 'dt' => 'week5', 'formatter' => function ($d, $row) { return join("<br>", array_map(function ($v) { return translate_tour($v); }, explode("<br>", $d))); } ],
			[ 'db' => 'eng5', 'dt' => 'week6', 'formatter' => function ($d, $row) { return join("<br>", array_map(function ($v) { return translate_tour($v); }, explode("<br>", $d))); } ],
			[ 'db' => 'first', 'dt' => 'first' ],
		];

		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);
	}

}
