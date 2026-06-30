<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('created_by')->nullable()->index();
            $table->enum('type', ['database', 'full'])->default('database');
            $table->enum('status', ['pending', 'running', 'success', 'failed'])->default('pending');
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('documents_count')->default(0);
            $table->unsignedInteger('versions_count')->default(0);
            $table->unsignedInteger('avatars_count')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
