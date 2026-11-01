<?php

namespace App\Console\Commands;

use App\Http\Controllers\UtilsController;
use App\Models\Account;
use App\Models\Token;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CheckAppStatus extends Command
{
    protected $signature = 'app:check-app-status';

    protected $description = '检查程序状态并在需要时初始化数据表';

    public function handle()
    {
        $connection = config("database.default");
        if ($connection === "no" || $connection === "" || $connection === null) {
            $this->error("请完整配置环境变量（DB_CONNECTION 等）");
            return self::FAILURE;
        }

        if (!Schema::hasTable('accounts')) {
            Schema::create("accounts", function (Blueprint $table) {
                $table->id();
                $table->text("baidu_name");
                $table->text("uk");
                $table->enum("account_type", Account::$account_types);
                $table->json("account_data");
                $table->boolean("switch");
                $table->text("reason");
                $table->enum("prov", UtilsController::$provincesFull)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('black_lists')) {
            Schema::create("black_lists", function (Blueprint $table) {
                $table->id();
                $table->enum("type", ["ip", "fingerprint"]);
                $table->text("identifier");
                $table->text("reason");
                $table->dateTime("expires_at");
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('file_lists')) {
            Schema::create("file_lists", function (Blueprint $table) {
                $table->id();
                $table->text("surl");
                $table->text("pwd");
                $table->text("fs_id");
                $table->unsignedBigInteger("size");
                $table->text("filename");
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tokens')) {
            Schema::create("tokens", function (Blueprint $table) {
                $table->id();
                $table->text("token");
                $table->unsignedBigInteger("count");
                $table->unsignedBigInteger("size");
                $table->unsignedBigInteger("day");
                $table->unsignedBigInteger("can_use_ip_count");
                $table->json("ip");
                $table->boolean("switch");
                $table->text("reason");
                $table->dateTime("expires_at")->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            Token::query()->create([
                "token"            => "guest",
                "count"            => 10,
                "size"             => 10 * UtilsController::$GB,
                "day"              => 0,
                "can_use_ip_count" => 114514,
                "ip"               => [],
                "switch"           => true,
                "reason"           => "",
                "expires_at"       => "2099-01-01 00:00:00",
            ]);
        }

        if (!Schema::hasTable('records')) {
            Schema::create("records", function (Blueprint $table) {
                $table->id();
                $table->text("ip");
                $table->text("fingerprint");
                $table->unsignedBigInteger("fs_id");
                $table->json("urls");
                $table->text("ua");
                $table->unsignedBigInteger("token_id");
                $table->unsignedBigInteger("account_id");
                $table->timestamps();

                $table->foreign("fs_id")->references("id")->on("file_lists");
                $table->foreign("token_id")->references("id")->on("tokens");
                $table->foreign("account_id")->references("id")->on("accounts");
            });
        }

        $this->info("启动完成");
        return self::SUCCESS;
    }
}
