<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File11SerialExport implements FromCollection, WithHeadings, WithMapping
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
            'Serial Number',
            'Description',
            'Search Argument',
            'Alternate Serial Number',
            'Installation Group',
            'Installation Group (child)',
            'Track GPS Location',
            'Creation Time',
            'Creation Time (child)',
            'Owner',
            'Owner (child)',
            'Address',
            'Address (child)',
            'Address (child) (child)',
            'Address (child) (child) (child)',
            'Address (child) (child) (child) (child)',
            'Contact',
            'Contact (child)',
            'Contact (child) (child)',
            'Contact (child) (child) (child)'
        ];
    }

    public function map($row): array
    {
        // Filter: Hanya barang yang memiliki flag Serialized yang diproses
        // Jika tidak serialized, kita kembalikan array kosong agar tidak muncul di excel
        if (!optional($row->serialization)->is_serialized) {
            return [];
        }

        return [
            '',
            '',
            '',
            400,                            // Company
            '',                             // Item (Kosongkan)
            $row->item_code,                // Item (child) (Kode Barang)
            $row->serial_number ?: 'SN-PENDING', // Serial Number
            $row->name,                     // Description
            $row->name,                     // Search Argument (Sama dengan nama)
            '',                             // Alternate SN
            '',
            '',                         // Installation Group
            'No',                           // Track GPS Location

            // --- PEMISAHAN TANGGAL & JAM ---
            now()->format('Y-m-d'),         // Creation Time
            now()->format('H:i:s'),         // Creation Time (child)

            '',
            '',                         // Owner
            '',
            '',
            '',
            '',
            '',             // Address blocks
            '',
            '',
            '',
            ''                  // Contact blocks
        ];
    }
}
