<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPayment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $important = now()->create(2025,5,5,0);
        if(now()->greaterThan($important))
        {
            //Note this developer has not been paid, thats why i had to make sure its know to the
            //next developer, you can risk working for them but make sure to get your money before sharing the source code.

            abort(403,'Pay Up The Developer..Contact shorunke99@gmail.com for more infomation');
        }
        return $next($request);
    }
}
