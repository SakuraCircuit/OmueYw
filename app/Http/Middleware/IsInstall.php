<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class IsInstall
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (str_contains($request->url(), "install")) return $next($request);

        // 检查是否安装
        $dbDefault = config("database.default");
        if ($dbDefault === "no") return response()->redirectTo("/install");

        return $next($request);
    }
}
