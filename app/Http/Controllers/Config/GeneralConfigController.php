<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\UtilsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeneralConfigController extends Controller
{
    public function getConfig()
    {
        return ResponseController::success([
            ...config("hklist.general"),
            "version" => config("hklist.version"),
        ]);
    }

    public function updateConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "new_admin_password" => "nullable|string",
            "parse_password" => "nullable|string",
            "show_announce" => "required|boolean",
            "announce" => "nullable|string",
            "custom_script" => "nullable|string",
            "custom_button" => "nullable|string",
            "show_hero" => "required|boolean",
            "name" => "required|string",
            "logo" => "required|string",
            "debug" => "required|boolean",
            "disable_check_rand" => "required|boolean",
            "save_histories_day" => "required|numeric",
        ]);

        if ($validator->fails()) return ResponseController::paramsError($validator->errors());

        UtilsController::updateEnv([
            "HKLIST_ADMIN_PASSWORD" => $request["new_admin_password"] ?? "",
            "HKLIST_PARSE_PASSWORD" => $request["parse_password"] ?? "",
            "HKLIST_SHOW_ANNOUNCE" => $request["show_announce"],
            "HKLIST_ANNOUNCE" => htmlspecialchars(str_replace("\n", "[NextLine]", $request["announce"]), ENT_QUOTES) ?? "",
            "HKLIST_CUSTOM_SCRIPT" => htmlspecialchars(str_replace("\n", "[NextLine]", $request["custom_script"]), ENT_QUOTES) ?? "",
            "HKLIST_CUSTOM_BUTTON" => str_replace("\n", "[NextLine]", $request["custom_button"]) ?? "",
            "HKLIST_SHOW_HERO" => $request["show_hero"],
            "APP_NAME" => $request["name"],
            "APP_LOGO" => $request["logo"],
            "APP_DEBUG" => $request["debug"],
            "HKLIST_DISABLE_CHECK_RAND" => $request["disable_check_rand"],
            "HKLIST_SAVE_HISTORIES_DAY" => max($request["save_histories_day"], 7),
        ]);

        return ResponseController::success();
    }
}
