<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2.1.17 新增 daily 卡密类型
if (!Schema::hasColumn("tokens", "token_type")) {
    Schema::table("tokens", function (Blueprint $table) {
        $table->enum("token_type", ["normal", "daily"])->after("token")->default("normal");
    });
}