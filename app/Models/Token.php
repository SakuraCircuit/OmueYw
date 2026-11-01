<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Token extends Model
{
    use SoftDeletes;

    public static array $attrs = [
        "id",
        "token",
        "token_type",
        "count",
        "size",
        "day",
        "can_use_ip_count",
        "ip",
        "switch",
        "reason",
        "expires_at",
        "created_at",
        "updated_at",
        "deleted_at"
    ];

    protected $fillable = [
        "token",
        "token_type",
        "count",
        "size",
        "day",
        "used_count",
        "used_size",
        "can_use_ip_count",
        "ip",
        "switch",
        "reason",
        "expires_at"
    ];

    protected function casts(): array
    {
        return [
            "ip" => "json",
            "expires_at" => "datetime",
            "switch" => "boolean"
        ];
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
