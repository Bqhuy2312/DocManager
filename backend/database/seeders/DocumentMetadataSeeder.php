<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentMetadataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $folders = [
            ['id' => '30000000-0000-0000-0000-000000000001', 'parent_id' => null, 'name' => 'Tài liệu chung'],
            ['id' => '30000000-0000-0000-0000-000000000002', 'parent_id' => null, 'name' => 'Nhân sự'],
            ['id' => '30000000-0000-0000-0000-000000000003', 'parent_id' => null, 'name' => 'CNTT'],
            ['id' => '30000000-0000-0000-0000-000000000004', 'parent_id' => null, 'name' => 'Tài chính'],
            ['id' => '30000000-0000-0000-0000-000000000005', 'parent_id' => null, 'name' => 'Vận hành'],
            ['id' => '30000000-0000-0000-0000-000000000006', 'parent_id' => null, 'name' => 'Pháp chế'],
            ['id' => '31000000-0000-0000-0000-000000000001', 'parent_id' => '30000000-0000-0000-0000-000000000001', 'name' => 'Hướng dẫn'],
            ['id' => '31000000-0000-0000-0000-000000000002', 'parent_id' => '30000000-0000-0000-0000-000000000002', 'name' => 'Quy trình'],
            ['id' => '31000000-0000-0000-0000-000000000003', 'parent_id' => '30000000-0000-0000-0000-000000000002', 'name' => 'Đào tạo'],
            ['id' => '31000000-0000-0000-0000-000000000004', 'parent_id' => '30000000-0000-0000-0000-000000000003', 'name' => 'Chính sách'],
            ['id' => '31000000-0000-0000-0000-000000000005', 'parent_id' => '30000000-0000-0000-0000-000000000004', 'name' => 'Quy trình làm việc'],
            ['id' => '31000000-0000-0000-0000-000000000006', 'parent_id' => '30000000-0000-0000-0000-000000000005', 'name' => 'Quy trình'],
            ['id' => '31000000-0000-0000-0000-000000000007', 'parent_id' => '30000000-0000-0000-0000-000000000006', 'name' => 'Sổ tay'],
        ];

        foreach ($folders as $folder) {
            DB::table('folders')->updateOrInsert(
                ['id' => $folder['id']],
                [...$folder, 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }
}
