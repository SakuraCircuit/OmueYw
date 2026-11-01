<?php

namespace App\Http\Controllers\Parsers;

use App\Http\Controllers\Api\BDWPApiController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ParseController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\UtilsController;
use App\Models\Account;
use App\Models\Proxy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * 解析层基类：结果规范化、限速识别、账号熔断、代理挑选。
 *
 * 统一返回契约（与 ParseController::getDownloadLinks 的消费方式一致）：
 * 成功：ResponseController::success([
 *     [
 *         "message"    => "请求成功",
 *         "filename"   => string,
 *         "fs_id"      => int|float,
 *         "ua"         => string,
 *         "account_id" => int,
 *         "urls"       => string[],
 *     ],
 *     ...
 * ])
 * 失败：直接返回 ResponseController::xxxFailed(...) 构造的 JsonResponse。
 */
abstract class ApiController extends Controller
{
    /** 临时转存目录 */
    protected const SAVE_PATH = "/我的资源";

    /**
     * 子类实现真正的解析流程。
     */
    abstract public static function request(Request $request): JsonResponse;

    /**
     * 取本次请求应使用的解析 UA。
     */
    protected static function parseUserAgent(Request $request): string
    {
        return $request["token"] === "guest"
            ? (string)config("hklist.parse.guest_user_agent")
            : (string)config("hklist.parse.token_user_agent");
    }

    /**
     * 规范化一条成功结果。
     */
    protected static function ok(array $item): array
    {
        return array_merge([
            "message" => "请求成功",
            "filename" => "",
            "fs_id" => 0,
            "ua" => "",
            "account_id" => 0,
            "urls" => [],
        ], $item);
    }

    /**
     * 把 downloadByDisk 的 data 映射为结果项。
     *
     * @param array $account   账号记录
     * @param array $dlinkData downloadByDisk 的 data 部分
     */
    protected static function fromLocatedUrl(
        array  $account,
        string $filename,
        $fs_id,
        string $ua,
        array  $dlinkData,
        string $suffix = "&origin=dlna"
    ): array
    {
        $urls = [];
        foreach (($dlinkData["urls"] ?? []) as $url) {
            $urls[] = $url . $suffix;
        }

        return self::ok([
            "message" => "请求成功",
            "filename" => $filename,
            "fs_id" => $fs_id,
            "ua" => $ua,
            "account_id" => $account["id"],
            "urls" => $urls,
        ]);
    }

    /**
     * 构造一条失败结果，便于部分成功场景透传。
     */
    protected static function fail(array $account, string $filename, $fs_id, string $ua, string $message): array
    {
        return [
            "message" => $message,
            "filename" => $filename,
            "fs_id" => $fs_id,
            "ua" => $ua,
            "account_id" => $account["id"] ?? 0,
            "urls" => [],
        ];
    }

    /**
     * 判断链接集合中是否出现限速特征。
     */
    public static function isLimited(array $urls): bool
    {
        foreach ($urls as $url) {
            if (str_contains($url, "qdall")) return true;

            $query = parse_url($url, PHP_URL_QUERY) ?? "";
            parse_str($query, $params);
            $tsl = (int)($params["tsl"] ?? 0);
            if ($tsl !== 0 && $tsl <= 10240) return true;
        }

        return false;
    }

    /**
     * 账号出现范围性失效时禁用（例如 cookie 失效、风控）。
     */
    protected static function banIfFatal(string $actionName, array $data, int $accountId): void
    {
        $message = (string)($data["message"] ?? "");
        if ($message === "" || $message === "请求成功") return;
        UtilsController::banAccount($actionName, $message, $accountId);
    }

    /**
     * 为账号挑选一个可用代理（type = proxy）。
     */
    protected static function pickProxy(array $account): ?Proxy
    {
        return Proxy::query()
            ->where([
                "account_id" => $account["id"],
                "type" => "proxy",
                "enable" => true,
            ])
            ->inRandomOrder()
            ->first();
    }

    /**
     * 读取内层响应的 data，失败时返回 null。
     */
    protected static function dataOf(JsonResponse $response): ?array
    {
        $payload = $response->getData(true);
        if (!is_array($payload) || ($payload["code"] ?? null) !== 200) return null;
        return is_array($payload["data"] ?? null) ? $payload["data"] : null;
    }

    /**
     * 解析失败时记录一次告警日志（不落 cookie 明文）。
     */
    protected static function logFailure(string $parser, JsonResponse $response): void
    {
        $payload = $response->getData(true);
        Log::warning("$parser 解析失败", [
            "code" => $payload["code"] ?? null,
            "message" => $payload["message"] ?? null,
        ]);
    }

    /**
     * 按账号类型取出可用于“转存”的凭证。
     */
    protected static function saveCredential(array $account): ?string
    {
        return $account["account_data"]["cookie"]
            ?? $account["account_data"]["save_cookie"]
            ?? $account["account_data"]["access_token"]
            ?? null;
    }

    /**
     * 按账号类型取出可用于“取直链”的凭证。
     */
    protected static function downloadCredential(array $account): ?string
    {
        return $account["account_data"]["dlink_cookie"]
            ?? $account["account_data"]["download_cookie"]
            ?? $account["account_data"]["cookie"]
            ?? null;
    }

    /**
     * 取账号的随机可用 cookie（内部透传 ParseController 的账号挑选逻辑）。
     */
    protected static function acquireAccount(Request $request)
    {
        return ParseController::getRandomCookie($request);
    }

    /**
     * 取文件清单（用于补全 filename 等元信息）。
     */
    protected static function latestFileList(Request $request)
    {
        return \App\Models\FileList::query()
            ->whereIn("fs_id", $request["fs_id"])
            ->get()
            ->keyBy(fn($item) => (string)$item["fs_id"]);
    }
}
