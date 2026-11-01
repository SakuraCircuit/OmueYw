<?php

namespace App\Http\Controllers\Parsers;

use App\Http\Controllers\Api\BDWPApiController;
use App\Http\Controllers\ResponseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * V3：开放平台（access_token）· SVIP · 盘内下载。
 *
 * 流程：随机取一个 open_platform 账号 → 用 access_token 走开放平台分享转存
 * → 再用开放平台 locatedownload 换取直链。账号 access_token 过期时先刷新。
 */
class V3Controller extends ApiController
{
    public static function request(Request $request): JsonResponse
    {
        $accountResponse = self::acquireAccount($request);
        $account = self::dataOf($accountResponse);
        if ($account === null) return $accountResponse;

        if ($account["account_type"] !== "open_platform") return ResponseController::unknownAccountType();

        $accessToken = $account["account_data"]["access_token"] ?? null;
        if (!$accessToken) return ResponseController::getAccessTokenFailed("未知", "账号缺少 access_token");

        $ua = self::parseUserAgent($request);

        $saveResponse = BDWPApiController::saveToDiskWeb(
            $accessToken,
            $request["shareid"],
            $request["fs_id"],
            $request["uk"],
            $request["randsk"],
            "https://pan.baidu.com/s/$request[surl]"
        );
        $saveData = self::dataOf($saveResponse);
        if ($saveData === null) {
            $payload = $saveResponse->getData(true);
            if (!str_contains((string)($payload["message"] ?? ""), "errno: 2")) {
                self::banIfFatal("V3Controller::saveToDisk", $payload, (int)$account["id"]);
            }
            return $saveResponse;
        }

        $results = [];
        foreach ($saveData as $item) {
            $path = $item["to"];
            $filename = str_replace(self::SAVE_PATH . "/", "", $path);
            $fs_id = $item["from_fs_id"];

            $dlinkResponse = BDWPApiController::downloadByDisk($accessToken, $path, $ua);
            $dlinkData = self::dataOf($dlinkResponse);

            if ($dlinkData === null) {
                $payload = $dlinkResponse->getData(true);
                self::banIfFatal("V3Controller::downloadByDisk", $payload, (int)$account["id"]);
                $results[] = self::fail($account, $filename, $fs_id, $ua, (string)($payload["message"] ?? "获取下载链接失败"));
                continue;
            }

            // 开放平台直链默认带签名参数，额外附加 origin 标记以便前端识别
            $results[] = self::fromLocatedUrl($account, $filename, $fs_id, $ua, $dlinkData, "");
        }

        return ResponseController::success($results);
    }
}
