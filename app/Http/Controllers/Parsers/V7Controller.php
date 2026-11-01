<?php

namespace App\Http\Controllers\Parsers;

use App\Http\Controllers\Api\BDWPApiController;
use App\Http\Controllers\ResponseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * V7：企业账号 · 企业套餐 · 盘外下载。
 *
 * 流程：取分享原始文件清单 → 随机取一个 enterprise_cookie 账号
 * → 用 dlink_cookie（缺省回退主 cookie）对分享路径直接 locatedownload，不转存。
 */
class V7Controller extends ApiController
{
    public static function request(Request $request): JsonResponse
    {
        $fileListResponse = BDWPApiController::getFileList(
            $request["surl"],
            $request["pwd"] ?? "",
            "/",
            1,
            100,
            "filename"
        );
        $fileListData = self::dataOf($fileListResponse);
        if ($fileListData === null) return $fileListResponse;

        $sharePaths = [];
        foreach ($fileListData["list"] as $item) {
            if (!empty($item["is_dir"])) continue;
            $sharePaths[(string)$item["fs_id"]] = $item["path"];
        }

        $targets = array_values(array_filter(
            array_map(fn($id) => (string)$id, (array)$request["fs_id"]),
            fn($id) => isset($sharePaths[$id])
        ));
        if (count($targets) === 0) return ResponseController::getFileListFailed(999, "分享中不存在所选文件");

        $accountResponse = self::acquireAccount($request);
        $account = self::dataOf($accountResponse);
        if ($account === null) return $accountResponse;

        if ($account["account_type"] !== "enterprise_cookie") return ResponseController::unknownAccountType();

        $downloadCookie = self::downloadCredential($account);
        if (!$downloadCookie) return ResponseController::unknownAccountType();

        $ua = self::parseUserAgent($request);
        $fsIdMap = self::latestFileList($request)->all();

        $results = [];
        foreach ($targets as $fs_id) {
            $path = $sharePaths[$fs_id];
            $filename = $fsIdMap[$fs_id]["filename"] ?? basename($path);

            $dlinkResponse = BDWPApiController::downloadByDisk($downloadCookie, $path, $ua);
            $dlinkData = self::dataOf($dlinkResponse);

            if ($dlinkData === null) {
                $payload = $dlinkResponse->getData(true);
                self::banIfFatal("V7Controller::downloadByDisk", $payload, (int)$account["id"]);
                $results[] = self::fail($account, $filename, $fs_id, $ua, (string)($payload["message"] ?? "获取下载链接失败"));
                continue;
            }

            $results[] = self::fromLocatedUrl($account, $filename, $fs_id, $ua, $dlinkData);
        }

        return ResponseController::success($results);
    }
}
