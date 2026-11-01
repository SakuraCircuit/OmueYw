<?php

use App\Models\Account;
use App\Models\Record;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2.2.18 修复账号解析总数错误
if (!Schema::hasColumn("accounts", "used_count")) {
    Schema::table("accounts", function (Blueprint $table) {
        $table->unsignedBigInteger("used_count")->after("prov")->default(0);
        $table->unsignedBigInteger("used_size")->after("used_count")->default(0);
    });

    $accounts = Account::query()->get();
    foreach ($accounts as $account) {
        // 获取token对应的数据
        $records = Record::query()
            ->where("account_id", $account["id"])
            ->leftJoin("file_lists", "file_lists.id", "=", "records.fs_id")
            ->selectRaw("SUM(size) as size,COUNT(*) as count")
            ->first();

        $account->update([
            "used_count" => $records["count"] ?? 0,
            "used_size" => $records["size"] ?? 0,
        ]);
    }
}