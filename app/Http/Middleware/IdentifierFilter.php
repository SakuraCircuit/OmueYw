<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ResponseController;
use App\Http\Controllers\UtilsController;
use App\Models\BlackList;
use App\Models\Fingerprint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifierFilter
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // debug 时不校验
        if (!config("app.debug")) {
            $ip = BlackList::query()->firstWhere(["type" => "ip", "identifier" => UtilsController::getIp($request)]);
            if ($ip) return ResponseController::inBlackList($ip["reason"]);

            $fingerprint = BlackList::query()->firstWhere(["type" => "fingerprint", "identifier" => $request["rand2"]]);
            if ($fingerprint) return ResponseController::inBlackList($fingerprint["reason"]);
        }

        return $next($request);
    }
}
