<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;

class TestController extends Controller
{
    //

	public function test() {

		$file = env('ROOT') . "/diary";
		echo $file;

	}
}
