<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('visits');
    }

    public function down(): void
    {
        // Intentionally left empty. Visit tracking has been removed.
    }
};
