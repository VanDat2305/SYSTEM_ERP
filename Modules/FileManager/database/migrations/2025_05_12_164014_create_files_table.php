<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('original_name');
            $table->string('path');
            $table->string('url');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('disk')->default('spaces');
            $table->uuid('folder_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('folder_id')->references('id')->on('folders');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};