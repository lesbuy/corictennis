<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App;

class I18nController extends Controller
{
    //
	public function fetch($lang, $path = null) {
		$ret = [];

		if ($path === null) {
			$paths = ['api', 'draw', 'frame', 'gs', 'h2h', 'home', 'help', 'dc', 'evolv', 'nationname', 'pbp', 'pointflag', 'rank', 'result', 'roundname', 'sexname', 'stat'];
		} else {
			$paths = [$path];
		}

		foreach ($paths as $path) {
			App::setLocale('en');
			$fetch = __($path);
			if ($lang != 'null') App::setLocale($lang);
			$ret[$path] = [];
			foreach ($fetch as $k1 => $v1) {
				if (!is_array($v1)) $ret[$path][$k1] = $lang == 'null' ? "\u3000" : __(join('.', [$path, $k1]));
				else {
					foreach ($v1 as $k2 => $v2) {
						if (!is_array($v2)) $ret[$path][$k1][$k2] = $lang == 'null' ? "\u3000" : __(join('.', [$path, $k1, $k2]));
						else {
							foreach ($v2 as $k3 => $v3) {
								if (!is_array($v3)) $ret[$path][$k1][$k2][$k3] = $lang == 'null' ? "\u3000" : __(join('.', [$path, $k1, $k2, $k3]));
								else {
									foreach ($v3 as $k4 => $v4) {
										if (!is_array($v4)) $ret[$path][$k1][$k2][$k3][$k4] = $lang == 'null' ? "\u3000" : __(join('.', [$path, $k1, $k2, $k3, $k4]));
										else {
											foreach ($v4 as $k5 => $v5) {
												if (!is_array($v5)) $ret[$path][$k1][$k2][$k3][$k4][$k5] = $lang == 'null' ? "\u3000" : __(join('.', [$path, $k1, $k2, $k3, $k4, $k5]));
												else {
													foreach ($v5 as $k6 => $v6) {
														if (!is_array($v6)) $ret[$path][$k1][$k2][$k3][$k4][$k5][$k6] = $lang == 'null' ? "\u3000" : __(join('.', [$path, $k1, $k2, $k3, $k4, $k5, $k6]));
														else {
															foreach ($v6 as $k7 => $v7) {
																if (!is_array($v7)) $ret[$path][$k1][$k2][$k3][$k4][$k5][$k6][$k7] = $lang == 'null' ? "\u3000" : __(join('.', [$path, $k1, $k2, $k3, $k4, $k5, $k6, $k7]));
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $ret;
	}

	public function fetch_null($lang, $path = null) {
		$ret = [];

		if ($path === null) {
			$paths = ['api', 'draw', 'frame', 'gs', 'h2h', 'home', 'help', 'dc', 'evolv', 'nationname', 'pbp', 'pointflag', 'rank', 'result', 'roundname', 'sexname', 'stat'];
		} else {
			$paths = [$path];
		}

		foreach ($paths as $path) {
			App::setLocale('en');
			$fetch = __($path);
			$ret[$path] = [];
			foreach ($fetch as $k1 => $v1) {
				if (!is_array($v1)) $ret[$path][$k1] = '　';
				else {
					foreach ($v1 as $k2 => $v2) {
						if (!is_array($v2)) $ret[$path][$k1][$k2] = '　';
						else {
							foreach ($v2 as $k3 => $v3) {
								if (!is_array($v3)) $ret[$path][$k1][$k2][$k3] = '　';
								else {
									foreach ($v3 as $k4 => $v4) {
										if (!is_array($v4)) $ret[$path][$k1][$k2][$k3][$k4] = '　';
										else {
											foreach ($v4 as $k5 => $v5) {
												if (!is_array($v5)) $ret[$path][$k1][$k2][$k3][$k4][$k5] = '　';
												else {
													foreach ($v5 as $k6 => $v6) {
														if (!is_array($v6)) $ret[$path][$k1][$k2][$k3][$k4][$k5][$k6] = '　';
														else {
															foreach ($v6 as $k7 => $v7) {
																if (!is_array($v7)) $ret[$path][$k1][$k2][$k3][$k4][$k5][$k6][$k7] = '　';
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $ret;

	}
}
