<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency_code', 16);
            $table->string('to_currency_code', 16);
            $table->decimal('rate', 18, 8);
            $table->date('effective_date')->nullable();
            $table->text('source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['from_currency_code', 'to_currency_code']);
            $table->index(['effective_date']);
            $table->unique(['from_currency_code', 'to_currency_code', 'effective_date']);

            $table->foreign('from_currency_code')
                ->references('code')
                ->on('currencies')
                ->onDelete('cascade');

            $table->foreign('to_currency_code')
                ->references('code')
                ->on('currencies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_exchange_rates');
    }
};
