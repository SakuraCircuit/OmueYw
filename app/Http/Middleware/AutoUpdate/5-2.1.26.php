<?php

use App\Models\Account;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 2.1.26 移除 enterprise_cookie_photography 账号类型
Account::withTrashed()->where("account_type", "enterprise_cookie_photography")->update(["account_type" => "enterprise_cookie"]);
Schema::table("accounts", function (Blueprint $table) {
    $table->enum("account_type", ["cookie", "enterprise_cookie", "open_platform", "download_ticket"])->change();
});