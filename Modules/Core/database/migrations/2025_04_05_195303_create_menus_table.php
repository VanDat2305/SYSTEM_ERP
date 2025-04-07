<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name', 100);
            $table->string('route', 255)->nullable();
            $table->string('permission_name', 255)->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('sort_order')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('menus')->onDelete('cascade');
        });

        Schema::create('menu_permission', function (Blueprint $table) {
            $table->uuid('menu_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->primary(['menu_id', 'permission_id']);
            $table->foreign('menu_id')->references('id')->on('menus')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on(config('permission.table_names.permissions'))->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_permission');
        Schema::dropIfExists('menus');
    }
};