<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * 请求前置检查中间件。
 *
 * 职责：
 * 1. 校验客户端指纹参数 rand2 的类型，异常时降级为空值。
 * 2. 强校验行为由 HKLIST_DISABLE_CHECK_RAND 控制。
 */
class CheckRand
{
    public function handle(Request $request, Closure $next): Response
    {
        // 默认关闭指纹强校验，避免依赖前端 rand2 生成逻辑
        if (!config('hklist.general.disable_check_rand')) {
            $rand2 = $request->input('rand2');

            if ($rand2 !== null && !is_string($rand2)) {
                Log::warning('CheckRand: rand2 参数类型非法', ['rand2' => $rand2]);
                $request->merge(['rand2' => '']);
            }
        }

        return $next($request);
    }
}
