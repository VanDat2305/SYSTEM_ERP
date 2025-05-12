<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('path')->unique();
            $table->uuid('parent_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('parent_id')->references('id')->on('folders');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};