<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('documents')
            ->select(['id', 'version'])
            ->orderBy('id')
            ->chunk(100, function ($documents): void {
                foreach ($documents as $document) {
                    $versions = DB::table('document_versions')
                        ->where('document_id', $document->id)
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->get(['id', 'version']);

                    if ($versions->isEmpty()) {
                        continue;
                    }

                    $latestHistoryVersion = (string) $versions->last()->version;
                    if ($this->numericVersion($latestHistoryVersion) !== $this->numericVersion((string) $document->version)) {
                        continue;
                    }

                    foreach ($versions as $version) {
                        DB::table('document_versions')
                            ->where('id', $version->id)
                            ->update(['version' => $this->previousVersion((string) $version->version)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Legacy rows cannot be restored reliably because their old numbering was incorrect.
    }

    private function numericVersion(string $version): float
    {
        preg_match('/\d+(?:\.\d+)?/', $version, $matches);

        return (float) ($matches[0] ?? 1.0);
    }

    private function previousVersion(string $version): string
    {
        return number_format(max(1.0, $this->numericVersion($version) - 1), 1, '.', '');
    }
};
