<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterItem;
use App\Models\ItemGroup;

class MasterItemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Data Item Group Terlebih Dahulu
        $groupRM = ItemGroup::firstOrCreate(
            ['code' => 'RM-001'],
            ['description' => 'Raw Material']
        );

        $groupSPT = ItemGroup::firstOrCreate(
            ['code' => 'SPT-01'],
            ['description' => 'Maintenance Elecetric Sparepart']
        );

        $groupPKG = ItemGroup::firstOrCreate(
            ['code' => 'PKG-01'],
            ['description' => 'Packaging Item']
        );

        $groupMV = ItemGroup::firstOrCreate(
            ['code' => 'MV-001'],
            ['description' => 'Medium Voltage']
        );

        // 2. Masukkan Data Master Item (Ganti account_no dengan item_group_id)
        $items = [
            [
                // HAPUS: 'account_no' => '1130-10',
                // HAPUS: 'item_group' => 'RAW_MATERIAL',
                'item_group_id' => $groupRM->id, // GUNAKAN INI SEKARANG
                'item_code' => 'RM-CU-001',
                'name' => 'Tembaga Batangan (Copper Rod) 8mm',
                'item_type' => 'Purchased',
                'unit' => 'kg',
                'requires_energy' => false,
                'status' => 'active',
                'ai_tags' => json_encode(["tembaga", "copper", "bahan utama"]),
            ],
            [
                'item_group_id' => $groupSPT->id,
                'item_code' => 'SPT-NYM-001',
                'name' => 'Kabel NYM 2x1.5mm',
                'item_type' => 'Manufactured',
                'unit' => 'm',
                'requires_energy' => false,
                'status' => 'active',
                'ai_tags' => json_encode(["kabel", "nym", "listrik", "putih"]),
            ],
            [
                'item_group_id' => $groupPKG->id,
                'item_code' => 'PKG-BR-055',
                'name' => 'Bearing SKF 6205',
                'item_type' => 'Purchased',
                'unit' => 'pcs',
                'requires_energy' => false,
                'status' => 'active',
                'ai_tags' => json_encode(["bearing", "skf", "mesin extruder"]),
            ],
            [
                'item_group_id' => $groupMV->id,
                'item_code' => 'MV-AC-002',
                'name' => 'AC Daikin 2 PK',
                'item_type' => 'Purchased',
                'unit' => 'pcs',
                'requires_energy' => true,
                'status' => 'active',
                'ai_tags' => json_encode(["ac", "pendingin", "daikin"]),
            ],
        ];

        foreach ($items as $item) {
            MasterItem::create($item);
        }
    }
}
