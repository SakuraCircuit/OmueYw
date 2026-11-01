<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\UtilsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LimitConfigController extends Controller
{
    public function getConfig()
    {
        return ResponseController::success(config("hklist.limit"));
    }

    public function updateConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "max_once" => "required|numeric",
            "min_single_filesize" => "required|numeric",
            "max_single_filesize" => "required|numeric",
            "max_all_filesize" => "required|numeric",
            "max_download_daily_pre_account" => "required|numeric",
            "limit_cn" => "required|boolean",
            "limit_prov" => "required|boolean"
        ]);

        if ($validator->fails()) return ResponseController::paramsError($validator->errors());

        UtilsController::updateEnv([
            "HKLIST_MAX_ONCE" => $request["max_once"],
            "HKLIST_MIN_SINGLE_FILESIZE" => $request["min_single_filesize"],
            "HKLIST_MAX_SINGLE_FILESIZE" => $request["max_single_filesize"],
            "HKLIST_MAX_ALL_FILESIZE" => $request["max_all_filesize"],
            "HKLIST_MAX_DOWNLOAD_DAILY_PRE_ACCOUNT" => $request["max_download_daily_pre_account"],
            "HKLIST_LIMIT_CN" => $request["limit_cn"],
            "HKLIST_LIMIT_PROV" => $request["limit_prov"]
        ]);

        return ResponseController::success();
    }
}
