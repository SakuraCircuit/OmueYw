<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckPasswordController extends Controller
{
    public function checkPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "admin_password" => "nullable|string",
        ]);

        if ($validator->fails()) return ResponseController::paramsError($validator->errors());

        $admin_password = config("hklist.general.admin_password");
        if ($admin_password === "") return ResponseController::success();
        if ($admin_password !== $request["admin_password"]) return ResponseController::wrongPass("管理员");
        return ResponseController::success();
    }
}
