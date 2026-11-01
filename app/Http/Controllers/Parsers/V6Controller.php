<?php

namespace App\Http\Controllers\Parsers;

use App\Http\Controllers\Api\BDWPApiController;
use App\Http\Controllers\ResponseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * V6：企业账号 · 下载券 · 盘内下载。
 *
 * 流程：download_ticket 账号同时持有“转存 cookie”和“下载 cookie”两套凭证
 * → 用 save_cookie 转存到 /我的资源 → 用 download_cookie 取直链。
 * 该模式绑定具体的分享链接（surl / pwd / dir），因此会严格校验请求是否与账号匹配。
 */
class V6Controller extends ApiController
{
    public static function request(Request $request): JsonResponse
    {
        $accountResponse = self::acquireAccount($request);
        $account = self::dataOf($accountResponse);
        if ($account === null) return $accountResponse;

        if ($account["account_type"] !== "download_ticket") return ResponseController::unknownAccountType();

        $data = $account["account_data"];

        // 下载券账号与分享链接绑定，校验匹配
        if (($data["surl"] ?? null) !== $request["surl"]) {
            return ResponseController::saveToDiskFailed(999, "下载券账号与当前分享链接不匹配");
        }
        if (($data["dir"] ?? "/") !== ($request["dir"] ?? "/")) {
            return ResponseController::saveToDiskFailed(999, "下载券账号与当前目录不匹配");
        }

        $saveCookie = $data["save_cookie"] ?? null;
        $downloadCookie = $data["download_cookie"] ?? null;
        if (!$saveCookie || !$downloadCookie) return ResponseController::unknownAccountType();

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
                self::banIfFatal("V6Controller::saveToDisk", $payload, (int)$account["id"]);
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
                self::banIfFatal("V6Controller::downloadByDisk", $payload, (int)$account["id"]);
                $results[] = self::fail($account, $filename, $fs_id, $ua, (string)($payload["message"] ?? "获取下载链接失败"));
                continue;
            }

            $results[] = self::fromLocatedUrl($account, $filename, $fs_id, $ua, $dlinkData);
        }

        return ResponseController::success($results);
    }
}
