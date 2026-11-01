<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    public static array $account_types = [
        "cookie",
        "enterprise_cookie",
        "open_platform",
        "download_ticket",
    ];

    public static array $attrs = [
        "id",
        "baidu_name",
        "uk",
        "account_type",
        "account_data",
        "switch",
        "reason",
        "prov",
        "used_count",
        "used_size",
        "total_size",
        "total_size_updated_at",
        "created_at",
        "updated_at",
        "deleted_at"
    ];

    protected $fillable = [
        "baidu_name",
        "uk",
        "account_type",
        "account_data",
        "switch",
        "reason",
        "prov",
        // 历史以来总量
        "used_count",
        "used_size",
        // 今日总量
        "total_size",
        "total_size_updated_at"
    ];

    protected function casts(): array
    {
        return [
            "account_data" => "json",
            "switch" => "boolean",
            "total_size_updated_at" => "datetime"
        ];
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
