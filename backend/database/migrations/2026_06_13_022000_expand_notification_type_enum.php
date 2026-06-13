<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'type')) {
            DB::statement("ALTER TABLE notifications MODIFY type ENUM('upload','edit','approve','approved','rejected','system') NOT NULL DEFAULT 'system'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'type')) {
            DB::statement("UPDATE notifications SET type = 'approve' WHERE type = 'approved'");
            DB::statement("UPDATE notifications SET type = 'system' WHERE type = 'rejected'");
            DB::statement("ALTER TABLE notifications MODIFY type ENUM('upload','edit','approve','system') NOT NULL DEFAULT 'system'");
        }
    }
};
