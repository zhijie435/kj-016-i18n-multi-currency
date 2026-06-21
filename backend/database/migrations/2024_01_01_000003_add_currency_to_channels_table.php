<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->string('currency_code', 16)->nullable()->after('locale_id');
            $table->string('currency_symbol', 16)->nullable()->after('currency_code');
            $table->unsignedTinyInteger('currency_decimals')->default(2)->after('currency_symbol');

            $table->index('currency_code');
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropIndex(['currency_code']);
            $table->dropColumn(['currency_code', 'currency_symbol', 'currency_decimals']);
        });
    }
};
