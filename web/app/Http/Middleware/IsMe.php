<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsMe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

		if (!in_array(Auth::id(), [15526, 2999, 17540,17541, 9066, 20518, 20530])) {
			return;
		}

        return $next($request);
    }
}
