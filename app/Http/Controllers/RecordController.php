<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\Token;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecordController extends Controller
{
    public function select(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "column" => ["nullable", "string", Rule::in(Record::$attrs)],
            "direction" => ["nullable", "string", Rule::in(["asc", "desc"])],
        ]);
        if ($validator->fails()) return ResponseController::paramsError($validator->errors());

        $data = Record::query()
            ->with(["token", "account", "file"])
            ->orderBy($request["column"] ?? "id", $request["direction"] ?? "asc")
            ->paginate($request["size"]);

        return ResponseController::success($data);
    }

    public function getHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "token" => "required|string"
        ]);
        if ($validator->fails()) return ResponseController::paramsError($validator->errors());

        $token = Token::query()->firstWhere("token", $request["token"]);
        if (!$token) return ResponseController::TokenNotExists();

        $recordsQuery = Record::query()
            ->with(["file:id,filename,size"])
            ->where("token_id", $token["id"]);

        if ($request["token"] === "guest") $recordsQuery->where("ip", UtilsController::getIp($request));

        $records = $recordsQuery->orderBy("id", "desc")->paginate(
            $request["size"] ?? 5,
            ["id", "urls", "ua", "created_at", "fs_id"]
        );

        return ResponseController::success($records);
    }
}
