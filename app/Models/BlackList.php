<?php

namespace App\Models;

class BlackList extends Model
{
    public static array $attrs = [
        "id",
        "type",
        "identifier",
        "reason",
        "expires_at",
        "created_at",
        "updated_at",
    ];

    protected $fillable = [
        "type",
        "identifier",
        "reason",
        "expires_at"
    ];

    protected function casts(): array
    {
        return [
            "expires_at" => "datetime"
        ];
    }
}
