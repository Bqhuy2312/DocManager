<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'guest_device_id')) {
                $table->string('guest_device_id', 100)->nullable()->after('guest_expires_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'guest_device_id')) {
                $table->dropIndex(['guest_device_id']);
                $table->dropColumn('guest_device_id');
            }
        });
    }
};
