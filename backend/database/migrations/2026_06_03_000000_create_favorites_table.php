<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('favorites')) {
            return;
        }

        Schema::create('favorites', function (Blueprint $table): void {
            $table->uuid('user_id');
            $table->uuid('document_id');
            $table->timestamp('created_at')->nullable();

            $table->primary(['user_id', 'document_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
