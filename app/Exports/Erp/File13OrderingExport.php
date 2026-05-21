<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File13OrderingExport implements FromCollection, WithHeadings, WithMapping
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
            'Order Method',
            'Warehouse',
            'Warehouse (child)'
        ];
    }

    public function map($row): array
    {
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
            'Lot for Lot',                  // Order Method (Gunakan teks, bukan angka)
            $row->warehouse?->warehouse_code ?: 'WHSPT', // Warehouse
            'Warehouse Sparepart'           // Warehouse (child)
        ];
    }
}
