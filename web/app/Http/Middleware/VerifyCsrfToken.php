<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
		'auth/weixin/*',
    ];

	public function handle($request, \Closure $next)
	{
		if ($_SERVER['SERVER_NAME'] != "www.live-tennis.cn") {
			return $next($request);
		}

		// 使用CSRF
		return parent::handle($request, $next);
		// 禁用CSRF
		//return $next($request);
	}
}
