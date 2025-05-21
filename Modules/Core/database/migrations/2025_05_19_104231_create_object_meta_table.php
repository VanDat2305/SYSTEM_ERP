<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('object_meta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('object_id');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('object_id')->references('id')->on('objects')->onDelete('cascade');
            $table->unique(['object_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('object_meta');
    }
};
