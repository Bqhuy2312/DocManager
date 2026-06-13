<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('document_versions');

        Schema::create('document_versions', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->char('id', 36)->primary();
            $table->char('document_id', 36);
            $table->char('updated_by', 36)->nullable();
            $table->string('version', 20);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->string('cloudinary_public_id')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
