<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2.1.30 恢复账号单日解析量上限
if (!Schema::hasColumn("accounts", "total_size")) {
    Schema::table("accounts", function (Blueprint $table) {
        $table->unsignedBigInteger("total_size")->after("prov")->default(0);
    });
}