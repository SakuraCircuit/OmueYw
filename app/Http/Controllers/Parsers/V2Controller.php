<?php

namespace App\Http\Controllers\Parsers;

use App\Http\Controllers\Api\BDWPApiController;
use App\Http\Controllers\ResponseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * V2：SVIP 账号 · 盘外下载。
 *
 * 流程：先取分享原始文件清单拿到分享内路径 → 随机取一个 SVIP 账号
 * → 用账号 cookie 对分享路径直接发起 locatedownload 取直链，不做转存。
 * 相比 V1 不占用账号盘内容量与转存配额，因此需要账号 cookie 具备分享文件的可访问权限。
 */
class V2Controller extends ApiController
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

        // fs_id => 分享内路径
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

        $cookie = self::downloadCredential($account);
        if (!$cookie) return ResponseController::unknownAccountType();

        $ua = self::parseUserAgent($request);
        $fsIdMap = self::latestFileList($request)->all();

        $results = [];
        foreach ($targets as $fs_id) {
            $path = $sharePaths[$fs_id];
            $filename = $fsIdMap[$fs_id]["filename"] ?? basename($path);

            $dlinkResponse = BDWPApiController::downloadByDisk($cookie, $path, $ua);
            $dlinkData = self::dataOf($dlinkResponse);

            if ($dlinkData === null) {
                $payload = $dlinkResponse->getData(true);
                self::banIfFatal("V2Controller::downloadByDisk", $payload, (int)$account["id"]);
                $results[] = self::fail($account, $filename, $fs_id, $ua, (string)($payload["message"] ?? "获取下载链接失败"));
                continue;
            }

            $results[] = self::fromLocatedUrl($account, $filename, $fs_id, $ua, $dlinkData);
        }

        return ResponseController::success($results);
    }
}
