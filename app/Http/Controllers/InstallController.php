<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * 安装入口已停用。
 *
 * 本工程不提供在线安装流程，也不自动建表或写入 .env，
 * 所有环境配置均由部署方手工完成。
 */
class InstallController extends Controller
{
    public function install(Request $request)
    {
        return ResponseController::response(20403, 403, "当前构建不提供在线安装");
    }
}
