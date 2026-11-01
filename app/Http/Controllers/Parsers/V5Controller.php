<?php

namespace App\Http\Controllers\Parsers;

use App\Http\Controllers\Api\BDWPApiController;
use App\Http\Controllers\ResponseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * V5：企业账号 · 企业套餐 · 盘内下载。
 *
 * 流程：随机取一个 enterprise_cookie 账号 → 转存到 /我的资源
 * → 优先使用 dlink_cookie（独立的下载专用 cookie）取直链，否则用主 cookie。
 */
class V5Controller extends ApiController
{
    public static function request(Request $request): JsonResponse
    {
        $accountResponse = self::acquireAccount($request);
        $account = self::dataOf($accountResponse);
        if ($account === null) return $accountResponse;

        if ($account["account_type"] !== "enterprise_cookie") return ResponseController::unknownAccountType();

        $saveCookie = $account["account_data"]["cookie"] ?? null;
        if (!$saveCookie) return ResponseController::unknownAccountType();

        $downloadCookie = self::downloadCredential($account) ?: $saveCookie;
        $ua = self::parseUserAgent($request);

        $saveResponse = BDWPApiController::saveToDiskWeb(
            $saveCookie,
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
                self::banIfFatal("V5Controller::saveToDisk", $payload, (int)$account["id"]);
            }
            return $saveResponse;
        }

        $results = [];
        foreach ($saveData as $item) {
            $path = $item["to"];
            $filename = str_replace(self::SAVE_PATH . "/", "", $path);
            $fs_id = $item["from_fs_id"];

            $dlinkResponse = BDWPApiController::downloadByDisk($downloadCookie, $path, $ua);
            $dlinkData = self::dataOf($dlinkResponse);

            if ($dlinkData === null) {
                $payload = $dlinkResponse->getData(true);
                self::banIfFatal("V5Controller::downloadByDisk", $payload, (int)$account["id"]);
                $results[] = self::fail($account, $filename, $fs_id, $ua, (string)($payload["message"] ?? "获取下载链接失败"));
                continue;
            }

            $results[] = self::fromLocatedUrl($account, $filename, $fs_id, $ua, $dlinkData);
        }

        return ResponseController::success($results);
    }
}
