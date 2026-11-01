<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 环境自检：只做只读检查，不建表、不写 .env、不初始化数据。
 */
class CheckAppStatus extends Command
{
    protected $signature = 'app:check-app-status';

    protected $description = '检查运行环境配置是否完整';

    public function handle()
    {
        $issues = [];

        if (config("app.key") === null || config("app.key") === "") {
            $issues[] = "APP_KEY 未设置";
        }

        if (config("database.default") === "" || config("database.default") === "no") {
            $issues[] = "DB_CONNECTION 未配置";
        }

        if (in_array(config("hklist.general.admin_password"), ["", "admin"], true)) {
            $issues[] = "HKLIST_ADMIN_PASSWORD 未设置或仍为默认值";
        }

        if (in_array(config("hklist.general.parse_password"), ["", "admin"], true)) {
            $issues[] = "HKLIST_PARSE_PASSWORD 未设置或仍为默认值";
        }

        if (empty($issues)) {
            $this->info("环境检查通过");
            return self::SUCCESS;
        }

        foreach ($issues as $issue) {
            $this->error("· $issue");
        }

        $this->warn("本命令不执行任何自动装配，请手动完成上述配置");
        return self::FAILURE;
    }
}
