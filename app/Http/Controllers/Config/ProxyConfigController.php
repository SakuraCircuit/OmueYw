<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\UtilsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProxyConfigController extends Controller
{
    public function getConfig()
    {
        return ResponseController::success(config("hklist.proxy"));
    }

    public function updateConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "enable" => "required|boolean",
            "http" => "nullable|string",
            "https" => "nullable|string",
        ]);

        if ($validator->fails()) return ResponseController::paramsError($validator->errors());

        UtilsController::updateEnv([
            "HKLIST_PROXY_ENABLE" => $request["enable"],
            "HKLIST_PROXY_HTTP" => $request["http"],
            "HKLIST_PROXY_HTTPS" => $request["https"],
        ]);

        return ResponseController::success();
    }

    public function testProxy()
    {
        if (!config("hklist.proxy.enable")) return ResponseController::proxyIsNotEnable();
        return UtilsController::sendRequest("ProxyConfigController::testProxy", "get", "https://www.cz88.net/api/cz88/ip/base?ip=");
    }
}
