<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File12CostingExport implements FromCollection, WithHeadings, WithMapping
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
        // Header dengan spasi tambahan yang sangat spesifik untuk modul Costing
        return [
            'Import Status',
            'Import Code',
            'Import Message',
            'Company',
            'Item',
            'Item (child)',
            'Item (child) (child)',
            'Enterprise Unit',
            'Enterprise Unit (child)',
            'Costing Type',
            'Standard Costs Base',
            'Warehouse',
            'Warehouse (child)',
            'Costing Source',
            'Supplying Enterprise Unit',
            'Supplying Enterprise Unit (child)',
            'Supplying Purchase Office',
            'Supplying Purchase Office (child)',
            'Scheme',
            'Scheme (child)',
            'Currency',
            'Currency (child)',
            'Currency (child) ',
            'Currency (child)  ',
            'Currency (child)   ',
            'Currency (child)    ',
            'Currency (child)     ',
            'Currency (child)      ',
            'Currency (child)       ',
            'Currency (child)        ',
            'Material Costs',
            'Material Costs (child)',
            'Surcharge Costs',
            'Operation Costs',
            'General Costs',
            'Total Costs',
            'Last Actualization Date',
            'Last Actualization Date (child)',
            'By Item',
            'By Warehouse',
            'Include Landed Costs',
            'Landed Costs Set',
            'Landed Costs Set (child)'
        ];
    }

    public function map($row): array
    {
        $p = $row->procurement;
        $w = $row->warehouse;

        return [
            '',
            '',
            '',
            400,                            // Company
            '',                             // Item (Kosong)
            $row->item_code,                // Item (child) (Kode)
            $row->name,                     // Item (child) (child) (Nama)
            'JEC001',                       // Enterprise Unit
            'PT. Jembo Cable Company Tbk.', // EU (child)
            'Logistics',
            'Yes',
            $w?->warehouse_code ?: 'WHSPT', // Warehouse
            'Warehouse Sparepart',          // Warehouse (child)
            'Purchase',
            '',
            '',
            '',
            '',
            'M00001',                       // Scheme
            'Material',
            $p?->currency ?: 'IDR',         // Currency
            'Indonesia Rupiah',             // Currency (child)
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '', // Blanks for multiple currencies

            // --- PERBAIKAN: WAJIB ANGKA 0 ---
            0,
            'IDR',
            0,
            0,
            0,
            0,

            now()->format('Y-m-d'),
            now()->format('H:i:s'),
            'No',
            'Yes',
            'Yes',
            '',
            ''
        ];
    }
}
