<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ResponseController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PassFilter
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {
        if ($role === "ADMIN") {
            $pass = config("hklist.general.admin_password");
            if ($pass === "") return $next($request);
            if ($pass !== $request["admin_password"]) return ResponseController::wrongPass("管理员");
        } else if ($role === "USER") {
            $pass = config("hklist.general.parse_password");
            if ($pass === "") return $next($request);
            if ($pass !== $request["parse_password"]) return ResponseController::wrongPass("解析");
        }

        return $next($request);
    }
}
