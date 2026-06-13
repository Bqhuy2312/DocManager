<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_settings', 'two_factor_pin_hash')) {
                $table->string('two_factor_pin_hash')->nullable()->after('two_factor_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table): void {
            if (Schema::hasColumn('user_settings', 'two_factor_pin_hash')) {
                $table->dropColumn('two_factor_pin_hash');
            }
        });
    }
};
