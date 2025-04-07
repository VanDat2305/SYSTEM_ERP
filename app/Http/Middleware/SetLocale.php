<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('Accept-Language') ?? $request->query('lang', 'en');
        if (in_array($locale, ['en', 'vi'])) {
            app()->setLocale($locale);
        } else {
            app()->setLocale('en'); // Ngôn ngữ mặc định
        }
        return $next($request);
    }
}