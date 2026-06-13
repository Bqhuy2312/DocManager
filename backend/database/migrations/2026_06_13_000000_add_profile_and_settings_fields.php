<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('full_name');
            }
        });

        Schema::table('user_settings', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_settings', 'auto_save')) {
                $table->boolean('auto_save')->default(true)->after('language');
            }

            if (!Schema::hasColumn('user_settings', 'timezone')) {
                $table->string('timezone', 50)->default('UTC+7')->after('dark_mode');
            }

            if (!Schema::hasColumn('user_settings', 'email_enabled')) {
                $table->boolean('email_enabled')->default(true)->after('timezone');
            }

            if (!Schema::hasColumn('user_settings', 'in_app_enabled')) {
                $table->boolean('in_app_enabled')->default(true)->after('email_enabled');
            }

            if (!Schema::hasColumn('user_settings', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('notify_system');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table): void {
            $columns = [
                'auto_save',
                'timezone',
                'email_enabled',
                'in_app_enabled',
                'two_factor_enabled',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('user_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'position')) {
                $table->dropColumn('position');
            }
        });
    }
};
