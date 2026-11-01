# HkList-Libre

HkList 结构的复刻工程。仅用于源码阅读与接口契约比对。

## 内容边界

- 解析层 `app/Http/Controllers/Parsers/` 为明文实现，可单独阅读接口调用顺序。
- 不包含授权校验、远程校验回调、加密加载器。
- 不包含在线安装流程、默认口令、演示配置，也没有开箱即可启动的编排文件。
- `app:check-app-status` 只做只读环境检查，不建表、不写 `.env`、不初始化数据。
- `InstallController` 始终返回 403，不提供任何安装入口。

## 阅读顺序

1. `app/Http/Controllers/Parsers/ApiController.php`：结果契约与账号选择
2. `app/Http/Controllers/Parsers/V1Controller.php` ~ `V7Controller.php`：各解析模式的调用顺序
3. `app/Http/Controllers/Api/BDWPApiController.php`：上游接口封装
4. `app/Http/Middleware/CheckRand.php`：请求前置检查

## 目录

```
app/Http/Controllers/Parsers/      解析层
app/Http/Controllers/Api/          接口封装
app/Http/Middleware/CheckRand.php  请求前检查
config/hklist.php                  配置项定义
```

## 说明

运行所需的 PHP 环境、数据库、账号池与各项密钥均需自行提供。
仓库不提供部署指引、环境装配脚本，也不承诺任何可直接运行的形态。
`.env.example` 仅作为配置键清单参考，所有敏感值均为空。
