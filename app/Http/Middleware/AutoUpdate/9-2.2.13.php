<?php

use App\Models\Record;
use App\Models\Token;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2.2.13 优化卡密记录逻辑
if (!Schema::hasColumn("tokens", "used_count")) {
    Schema::table("tokens", function (Blueprint $table) {
        $table->unsignedBigInteger("used_count")->after("day")->default(0);
        $table->unsignedBigInteger("used_size")->after("used_count")->default(0);
    });

    Token::query()
        ->firstWhere("token", "guest")
        ->update([
            "token_type" => "daily"
        ]);

    $tokens = Token::query()->where("token_type", "normal")->get();
    foreach ($tokens as $token) {
        // 获取token对应的数据
        $records = Record::query()
            ->where("token_id", $token["id"])
            ->leftJoin("file_lists", "file_lists.id", "=", "records.fs_id")
            ->selectRaw("SUM(size) as size,COUNT(*) as count")
            ->first();

        $token->update([
            "used_count" => $records["count"] ?? 0,
            "used_size" => $records["size"] ?? 0,
        ]);
    }
}