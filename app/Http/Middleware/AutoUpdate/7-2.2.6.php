<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2.2.6 修复账号单日解析量上限
if (!Schema::hasColumn("accounts", "total_size_updated_at")) {
    Schema::table("accounts", function (Blueprint $table) {
        $table->dateTime("total_size_updated_at")->after("total_size")->nullable();
    });
}