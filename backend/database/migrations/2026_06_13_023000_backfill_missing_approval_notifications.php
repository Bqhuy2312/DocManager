<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('documents')
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('approved_by')
            ->orderBy('updated_at')
            ->get()
            ->each(function ($document): void {
                if ((string) $document->created_by === (string) $document->approved_by) {
                    return;
                }

                $type = $document->status === 'approved' ? 'approved' : 'rejected';
                $exists = DB::table('notifications')
                    ->where('user_id', $document->created_by)
                    ->where('type', $type)
                    ->where('link', '/documents/' . $document->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                DB::table('notifications')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $document->created_by,
                    'title' => $type === 'approved' ? 'Tài liệu đã được phê duyệt' : 'Tài liệu đã bị từ chối',
                    'message' => '"' . $document->title . '" ' . ($type === 'approved' ? 'đã được phê duyệt.' : 'đã bị từ chối.'),
                    'type' => $type,
                    'link' => '/documents/' . $document->id,
                    'is_read' => false,
                    'created_at' => $document->updated_at ?? now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // Keep generated user notifications.
    }
};
