<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->char('id', 36)->primary();
                $table->char('user_id', 36);
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('type', 50)->default('system');
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['user_id', 'is_read']);
            });

            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            if (!Schema::hasColumn('notifications', 'message')) {
                $table->text('message')->nullable()->after('title');
            }

            if (!Schema::hasColumn('notifications', 'type')) {
                $table->string('type', 50)->default('system')->after('message');
            }

            if (!Schema::hasColumn('notifications', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('type');
            }
        });
    }

    public function down(): void
    {
        // Keep user notification history when rolling back unrelated feature work.
    }
};
