<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileListsTemp extends Model
{
    protected $table = "file_lists_temp";

    public $timestamps = false;

    protected $fillable = [
        "table_id",
        "fs_id"
    ];
}
