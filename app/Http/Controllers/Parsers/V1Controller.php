<?php

namespace App\Http\Controllers\Parsers;

use App\Http\Controllers\Api\BDWPApiController;
use App\Http\Controllers\ResponseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * V1：SVIP 账号 · 盘内下载
 *
 * 流程：随机取一个 cookie 类型 SVIP 账号 → 转存到 /我的资源 → locatedownload 取直链。
 */
class V1Controller extends ApiController
{
    public static function request(Request $request): JsonResponse
    {
        $accountResponse = self::acquireAccount($request);
        $account = self::dataOf($accountResponse);
        if ($account === null) return $accountResponse;

        $cookie = $account["account_data"]["cookie"] ?? null;
        if (!$cookie) return ResponseController::unknownAccountType();

        $ua = self::parseUserAgent($request);

        $saveResponse = BDWPApiController::saveToDiskWeb(
            $cookie,
            $request["shareid"],
            $request["fs_id"],
            $request["uk"],
            $request["randsk"],
            "https://pan.baidu.com/s/$request[surl]"
        );
        $saveData = self::dataOf($saveResponse);
        if ($saveData === null) {
            $payload = $saveResponse->getData(true);
            // errno: 2 = 参数过期，不触发封号
            if (!str_contains((string)($payload["message"] ?? ""), "errno: 2")) {
                self::banIfFatal("V1Controller::saveToDisk", $payload, (int)$account["id"]);
            }
            return $saveResponse;
        }

        $results = [];
        foreach ($saveData as $item) {
            $path = $item["to"];
            $filename = str_replace(self::SAVE_PATH . "/", "", $path);
            $fs_id = $item["from_fs_id"];

            $dlinkResponse = BDWPApiController::downloadByDisk($cookie, $path, $ua);
            $dlinkData = self::dataOf($dlinkResponse);

            if ($dlinkData === null) {
                $payload = $dlinkResponse->getData(true);
                self::banIfFatal("V1Controller::downloadByDisk", $payload, (int)$account["id"]);
                $results[] = self::fail($account, $filename, $fs_id, $ua, (string)($payload["message"] ?? "获取下载链接失败"));
                continue;
            }

            $results[] = self::fromLocatedUrl($account, $filename, $fs_id, $ua, $dlinkData);
        }

        return ResponseController::success($results);
    }
}
