<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class BackupRestoreService
{
    public function restore(UploadedFile $file): array
    {
        $workDir = storage_path('app/backups/restore/' . (string) Str::uuid());
        File::ensureDirectoryExists($workDir);

        try {
            $sqlPath = $this->extractDatabaseSql($file, $workDir);
            $statements = $this->splitSqlStatements(File::get($sqlPath));
            $executed = $this->importStatements($statements);

            return [
                'executed_statements' => $executed,
            ];
        } finally {
            File::deleteDirectory($workDir);
        }
    }

    private function extractDatabaseSql(UploadedFile $file, string $workDir): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'sql') {
            $path = $workDir . DIRECTORY_SEPARATOR . 'database.sql';
            $file->move($workDir, 'database.sql');

            return $path;
        }

        if ($extension !== 'zip') {
            throw new RuntimeException('File import phải là .zip hoặc .sql.');
        }

        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('Không thể mở file backup zip.');
        }

        $databaseIndex = $zip->locateName('database.sql', ZipArchive::FL_NODIR);
        if ($databaseIndex === false) {
            $zip->close();
            throw new RuntimeException('Không tìm thấy database.sql trong file backup.');
        }

        $stream = $zip->getStream($zip->getNameIndex($databaseIndex));
        if (! $stream) {
            $zip->close();
            throw new RuntimeException('Không thể đọc database.sql trong file backup.');
        }

        $sqlPath = $workDir . DIRECTORY_SEPARATOR . 'database.sql';
        File::put($sqlPath, stream_get_contents($stream));
        fclose($stream);
        $zip->close();

        return $sqlPath;
    }

    private function importStatements(array $statements): int
    {
        $pdo = DB::connection()->getPdo();
        $executed = 0;

        foreach ($statements as $statement) {
            $statement = $this->stripCommentLines($statement);
            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
            $executed++;
        }

        return $executed;
    }

    private function stripCommentLines(string $statement): string
    {
        return trim(collect(preg_split('/\R/', $statement))
            ->reject(fn (string $line): bool => str_starts_with(trim($line), '--'))
            ->implode("\n"));
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $statement = '';
        $quote = null;
        $escaped = false;
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $char = $sql[$index];
            $statement .= $char;

            if ($quote) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === ';') {
                $statements[] = substr($statement, 0, -1);
                $statement = '';
            }
        }

        if (trim($statement) !== '') {
            $statements[] = $statement;
        }

        return $statements;
    }
}
