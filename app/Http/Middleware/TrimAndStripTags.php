<?php

namespace App\Http\Middleware;

use Closure;

class TrimAndStripTags
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
		$input = $request->all();
		if ($input) {
			array_walk_recursive($input, function (&$item, $key) {
					// RULES 1 FOR STRING AND PASSWORD
					if (is_string($item)) {
						$item = strip_tags(trim($item), '<br>');
					}
					// RULES 2 FOR NULL VALUE
					$item = $item == '' ? null : $item;
				});
			$request->merge($input);
		}
		return $next($request);
	}
}