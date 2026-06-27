<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'is_guest')) {
                $table->boolean('is_guest')->default(false)->after('avatar_public_id');
            }

            if (!Schema::hasColumn('users', 'guest_expires_at')) {
                $table->timestamp('guest_expires_at')->nullable()->after('is_guest');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'guest_expires_at')) {
                $table->dropColumn('guest_expires_at');
            }

            if (Schema::hasColumn('users', 'is_guest')) {
                $table->dropColumn('is_guest');
            }
        });
    }
};
