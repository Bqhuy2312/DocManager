<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'department_id')) {
            DB::statement('ALTER TABLE users MODIFY department_id CHAR(36) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'department_id')) {
            DB::statement('ALTER TABLE users MODIFY department_id CHAR(36) NOT NULL');
        }
    }
};
