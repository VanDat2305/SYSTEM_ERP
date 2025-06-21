<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BasicApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = env('BASIC_API_USER', 'apiuser');
        $pass = env('BASIC_API_PASS', 'apipassword');

        if (
            $request->getUser() !== $user ||
            $request->getPassword() !== $pass
        ) {
            // $headers = ['WWW-Authenticate' => 'Basic realm="API"'];
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
