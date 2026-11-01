<?php

use Illuminate\Database\Schema\Blueprint;

// 2.0.7 新增指纹ip对应表
//if (!Schema::hasTable('fingerprints')) {
//    Schema::create('fingerprints', function (Blueprint $table) {
//        $table->id();
//        $table->text("fingerprint");
//        $table->json("ip");
//        $table->timestamps();
//    });
//}