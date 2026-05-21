<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File3WhFooterExport implements FromCollection, WithHeadings, WithMapping
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
            'Search Key',
            'Item Type',
            'Outbound Method',
            'Package Definition',
            'Location Controlled',
            'Lot Controlled',
            'Lots in Inventory',
            'Lot Tracking'
        ];
    }

    public function map($row): array
    {
        return [
            '',
            '',
            '',
            400,                            // Company (Col 4)

            // --- PERBAIKAN: GESER KE CHILD ---
            '',                             // Item (Col 5) (Dikosongkan)
            $row->item_code,                // Item (child) (Col 6) (Kode Barang di sini)

            400,                            // Company (Col 7)
            'JCC1',                         // Site (Col 8)
            '',                             // Search Key
            'Product',                      // Item Type
            'By Location',                  // Outbound Method
            '',                             // Package Definition
            'Yes',                          // Location Controlled
            'No',                           // Lot Controlled
            'No',                           // Lots in Inventory
            'No'                            // Lot Tracking
        ];
    }
}
