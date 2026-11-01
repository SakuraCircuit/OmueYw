# HkList

HkList 结构的学习工程。

## 项目定位

本项目聚焦于解析流程本身的组织方式，用于阅读接口调用顺序与数据结构设计。

- 解析层 `app/Http/Controllers/Parsers/` 为明文实现，可单独阅读每个模式的调用顺序。
- `app:check-app-status` 只做只读环境检查，不建表、不写 `.env`、不初始化数据。
- `InstallController` 始终返回 403，不提供在线安装入口。
- 仓库不附带演示配置、示例口令与开箱即可启动的编排文件。

## 阅读顺序

1. `app/Http/Controllers/Parsers/ApiController.php`：结果契约与账号选择
2. `app/Http/Controllers/Parsers/V1Controller.php` ~ `V7Controller.php`：各解析模式的调用顺序
3. `app/Http/Controllers/Api/BDWPApiController.php`：接口封装
4. `app/Http/Middleware/CheckRand.php`：请求前置检查

## 目录

```
app/Http/Controllers/Parsers/      解析层
app/Http/Controllers/Api/          接口封装
app/Http/Middleware/CheckRand.php  请求前检查
config/hklist.php                  配置项定义
```

## 说明

运行所需的 PHP 环境、数据库与账号池均需自行提供。
`.env.example` 仅作为配置键清单参考，所有敏感值均为空。

## 许可

MIT
