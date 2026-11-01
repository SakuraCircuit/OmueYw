<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2.1.12 新增 enterprise_cookie_photography 账号类型
Schema::table("accounts", function (Blueprint $table) {
    $table->enum("account_type", ["cookie", "enterprise_cookie", "enterprise_cookie_photography", "open_platform", "download_ticket"])->change();
});