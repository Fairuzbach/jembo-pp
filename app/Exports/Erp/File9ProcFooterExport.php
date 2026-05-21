<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File9ProcFooterExport implements FromCollection, WithHeadings, WithMapping
{
    protected $items;
    public function __construct($items)
    {
        $this->items = $items;
    }
    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'Import Status',
            'Import Code',
            'Import Message',
            'Company',
            'Item',
            'Item (child)',
            'Company',
            'Site',
            'Site (child)',
            'Warehouse',
            'Use Global Item Purchase',
            'Buy-from Business Partner',
            'Buy-from Business Partner (child)',
            'Purchase Price',
            'Purchase Price (child)',
            'Buyer',
            'Buyer (child)',
            'Purchase Office',
            'Purchase Office (child)'
        ];
    }

    public function map($row): array
    {
        $p = $row->procurement;

        return [
            '',
            '',
            '',
            400,                            // Company (Col 4)
            '',                             // Item (Dikosongkan sesuai referensi)
            $row->item_code,                // Item (child) (Kode Barang)
            400,                            // Company (Col 7)
            'JCC1',                         // Site
            'Pt Jembo Factory (Main)',      // Site (child)
            '',                             // Warehouse
            'Yes',                          // Use Global Item Purchase
            '',
            '',                         // BP & BP child

            // --- PERBAIKAN: WAJIB ANGKA 0 ---
            $p?->standard_cost ?: 0,        // Purchase Price (Wajib 0 jika null)

            $p?->currency ?: 'IDR',         // Purchase Price (child)
            '',
            '',
            '',
            ''                  // Buyer & Office
        ];
    }
}
