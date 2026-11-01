<?php

namespace App\Models;

class Record extends Model
{
    public static array $attrs = [
        "id",
        "ip",
        "fingerprint",
        "fs_id",
        "urls",
        "ua",
        "token_id",
        "account_id",
        "created_at",
        "updated_at",
    ];

    protected $fillable = [
        "ip",
        "fingerprint",
        "fs_id",
        "urls",
        "ua",
        "token_id",
        "account_id"
    ];

    protected function casts(): array
    {
        return [
            'urls' => "json"
        ];
    }

    public function token()
    {
        return $this->belongsTo(Token::class)->withTrashed();
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function file()
    {
        return $this->hasOne(FileList::class, "id", "fs_id");
    }
}
