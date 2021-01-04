<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ShortMsg;
use App\Scripts\Ssp;
use App;

class ShortMsgController extends Controller
{
    //

	protected $infoKey;

	protected $primaryKey = 'id';

	protected $table;

	protected $columns;

	protected $sql_details;


	public function __construct() {

		$this->table = 'short_msgs';

		$this->sql_details = [
			'user' => env('DB_USERNAME'),
			'pass' => env('DB_PASSWORD'),
			'db' => env('DB_DATABASE'),
			'host' => env('DB_HOST') . ':' . env('DB_PORT'),
		];

	}

	public function index() {
		return view('admin.shortmsg');
	}

	public function query(Request $req) {

		$this->columns = [
			[ 'db' => 'id', 'dt' => 'id' ],
			[ 'db' => 'userid', 'dt' => 'userid' ],
			[ 'db' => 'username', 'dt' => 'username' ],
			[ 'db' => 'msg', 'dt' => 'msg' ],
			[ 'db' => 'reply', 'dt' => 'reply' ],
			[ 'db' => 'created_at', 'dt' => 'created_at' ],
			[ 'db' => 'read', 'dt' => 'read' ],
		];

		return json_encode(
			Ssp::simple($req->all(), $this->sql_details, $this->table, $this->primaryKey, $this->columns)
		);

	}

	public function save(Request $req) {
		$id = $req->input('id');
		$col = $req->input('col');
		$content = $req->input('content');

		$one = ShortMsg::find($id);
		$one->${"col"} = $content;
		$one->save();
	}
}
