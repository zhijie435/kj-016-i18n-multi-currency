<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 64);
            $table->string('symbol', 16)->nullable();
            $table->unsignedTinyInteger('decimals')->default(2);
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
