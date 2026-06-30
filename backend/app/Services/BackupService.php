<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;
use ZipArchive;

class BackupService
{
    public function run(Backup $backup): Backup
    {
        $workDir = storage_path('app/backups/tmp/' . $backup->id);
        $backupDir = storage_path('app/backups');
        $zipName = 'backup-' . $backup->type . '-' . now()->format('Ymd-His') . '.zip';
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipName;

        File::ensureDirectoryExists($workDir);
        File::ensureDirectoryExists($backupDir);

        $backup->update([
            'status' => 'running',
            'started_at' => now(),
            'message' => null,
        ]);

        try {
            $databasePath = $workDir . DIRECTORY_SEPARATOR . 'database.sql';
            $this->dumpDatabase($databasePath);

            $counts = [
                'documents_count' => 0,
                'versions_count' => 0,
                'avatars_count' => 0,
            ];

            if ($backup->type === 'full') {
                $counts = $this->backupCloudinaryFiles($workDir);
            }

            $this->zipDirectory($workDir, $zipPath);

            $backup->update([
                'status' => 'success',
                'file_name' => $zipName,
                'file_path' => 'backups/' . $zipName,
                'file_size' => File::size($zipPath),
                'documents_count' => $counts['documents_count'],
                'versions_count' => $counts['versions_count'],
                'avatars_count' => $counts['avatars_count'],
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }

            $backup->update([
                'status' => 'failed',
                'message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
        } finally {
            File::deleteDirectory($workDir);
        }

        return $backup->fresh('creator');
    }

    private function dumpDatabase(string $path): void
    {
        $pdo = DB::getPdo();
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn (object $row) => array_values((array) $row)[0])
            ->values();

        $handle = fopen($path, 'w');
        if (! $handle) {
            throw new RuntimeException('Không thể tạo file dump database.');
        }

        fwrite($handle, "-- DocManager database backup\n");
        fwrite($handle, '-- Created at: ' . now()->toDateTimeString() . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            $quotedTable = $this->quoteIdentifier($table);
            $createRows = DB::select("SHOW CREATE TABLE {$quotedTable}");
            $createSql = array_values((array) $createRows[0])[1] ?? null;

            fwrite($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
            fwrite($handle, $createSql . ";\n\n");

            DB::table($table)
                ->orderByRaw('1')
                ->chunk(500, function ($rows) use ($handle, $pdo, $table, $quotedTable): void {
                    if ($rows->isEmpty()) {
                        return;
                    }

                    foreach ($rows as $row) {
                        $values = collect((array) $row)
                            ->map(fn ($value) => $this->sqlValue($pdo, $value))
                            ->implode(', ');

                        fwrite($handle, "INSERT INTO {$quotedTable} VALUES ({$values});\n");
                    }

                    fwrite($handle, "\n");
                });
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    private function backupCloudinaryFiles(string $workDir): array
    {
        $seen = [];
        $counts = [
            'documents_count' => 0,
            'versions_count' => 0,
            'avatars_count' => 0,
        ];

        Document::query()
            ->whereNotNull('file_path')
            ->get(['id', 'title', 'file_name', 'file_path'])
            ->each(function (Document $document) use ($workDir, &$seen, &$counts): void {
                if ($this->downloadFile(
                    $document->file_path,
                    $workDir . DIRECTORY_SEPARATOR . 'cloudinary' . DIRECTORY_SEPARATOR . 'documents',
                    $document->file_name ?: $document->title,
                    $seen
                )) {
                    $counts['documents_count']++;
                }
            });

        DocumentVersion::query()
            ->whereNotNull('file_path')
            ->get(['id', 'version', 'file_name', 'file_path'])
            ->each(function (DocumentVersion $version) use ($workDir, &$seen, &$counts): void {
                if ($this->downloadFile(
                    $version->file_path,
                    $workDir . DIRECTORY_SEPARATOR . 'cloudinary' . DIRECTORY_SEPARATOR . 'versions',
                    'v' . $version->version . '-' . ($version->file_name ?: $version->id),
                    $seen
                )) {
                    $counts['versions_count']++;
                }
            });

        User::query()
            ->whereNotNull('avatar')
            ->get(['id', 'full_name', 'avatar'])
            ->each(function (User $user) use ($workDir, &$seen, &$counts): void {
                if ($this->downloadFile(
                    $user->avatar,
                    $workDir . DIRECTORY_SEPARATOR . 'cloudinary' . DIRECTORY_SEPARATOR . 'avatars',
                    $user->full_name ?: $user->id,
                    $seen
                )) {
                    $counts['avatars_count']++;
                }
            });

        return $counts;
    }

    private function downloadFile(?string $url, string $directory, string $name, array &$seen): bool
    {
        if (! $url || isset($seen[$url])) {
            return false;
        }

        $seen[$url] = true;
        File::ensureDirectoryExists($directory);

        $response = Http::timeout(45)->get($url);
        if (! $response->successful()) {
            return false;
        }

        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION);
        $fileName = $this->safeFileName($name);
        if ($extension && ! str_ends_with(strtolower($fileName), '.' . strtolower($extension))) {
            $fileName .= '.' . $extension;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $fileName;
        $counter = 2;
        while (File::exists($path)) {
            $info = pathinfo($fileName);
            $path = $directory . DIRECTORY_SEPARATOR
                . ($info['filename'] ?? 'file')
                . '-' . $counter
                . (isset($info['extension']) ? '.' . $info['extension'] : '');
            $counter++;
        }

        File::put($path, $response->body());

        return true;
    }

    private function zipDirectory(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không thể tạo file zip backup.');
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceDir) + 1));
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();
    }

    private function sqlValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $pdo->quote((string) $value);
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function safeFileName(string $name): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|]+/', '-', $name);
        $name = trim((string) $name, ". \t\n\r\0\x0B");

        return $name !== '' ? mb_substr($name, 0, 120) : 'file';
    }
}
