<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');

            // Sử dụng UUID cho morphs
            $table->uuid('subject_id')->nullable()->index('subject_id');
            $table->string('subject_type')->nullable()->index('subject_type');

            $table->uuid('causer_id')->nullable()->index('causer_id');
            $table->string('causer_type')->nullable()->index('causer_type');

            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}