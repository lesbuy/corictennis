<?php

namespace App\Http\Middleware;

use Closure;
use Auth;

class IsAdmin
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
		if (!in_array(Auth::id(), [
			15526, 2999, 17540,17541, 9066, 20518, 20530,
			2432, 1579,
		])) {
			return redirect('home');
		}

        return $next($request);
    }
}
