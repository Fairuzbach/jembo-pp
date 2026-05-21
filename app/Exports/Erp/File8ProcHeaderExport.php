<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File8ProcHeaderExport implements FromCollection, WithHeadings, WithMapping
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
            'Item (child) (child)',
            'Search Key I',
            'Item Type',
            'Buy-from BP',
            'Buy-from BP (child)',
            'Buyer',
            'Buyer (child)',
            'Purchase Unit',
            'Purchase Unit (child)',
            'Purchase Stat. Group',
            'Purchase Stat. Group (child)',
            'Purchase Office',
            'Purchase Office (child)',
            'Purchase Office (child) (child)',
            'Purchase Office (child) (child) (child)',
            'Currency',
            'Currency (child)',
            'Purchase Price Unit',
            'Purchase Price Unit (child)',
            'Purchase Price',
            'Purchase Price Group',
            'Purchase Price Group (child)',
            'Tax Code',
            'Tax Code (child)',
            'Invoice by Stage Payments',
            'VAT Based on',

            // --- BLOK HARGA & SUMBER 1 ---
            'Source of Price',
            'Subcontracting Purchase Price',
            'Price in Home Currency',
            'Price in Home Currency (child)',
            'Requisition Mandatory',

            // --- BLOK DUPLIKAT DENGAN SPASI (Penting!) ---
            'Source of Price ',
            'Subcontracting Purchase Price ',
            'Price in Home Currency ',
            'Requisition Mandatory ',
            'Requisition Mandatory (child)',

            'Date Tolerance (-)',
            'Date Tolerance (+)',
            'Hard Stop on Date',
            'Quantity Tolerance (-)',
            'Quantity Tolerance (+)',
            'Hard Stop on Quantity',
            'Supply Time',
            'tdipu001.sutu',
            'Release to Warehousing',
            'Inspection',
            'Accessories Allowed',
            'Deliver by Specified Suppliers Only',
            'Specify Cost Optionally',
            'Purchase Text',
            'Latest Purchase Price Transaction Date',
            'Latest Purchase Price Transaction Date (child)',
            'Average Purchase Price',
            'Average Purchase Price in Home Currency',
            'Latest Purchase Price',
            'Latest Purchase Price in Home Currency',
            'Latest Purchase Price Transaction Date ',
            'Latest Purchase Price Transaction Date (child) ',
            'Average Purchase Price ',
            'Latest Purchase Price ',
            'Latest Landed Cost Transaction Date',
            'Latest Landed Cost Transaction Date (child)',
            'Average Landed Cost',
            'Average Landed Cost in Home Currency',
            'Latest Landed Cost',
            'Latest Landed Cost in Home Currency'
        ];
    }

    public function map($row): array
    {
        $p = $row->procurement;
        $unit = strtolower($row->unit ?: 'pcs');

        return [
            '',
            '',
            '',
            400,
            '',                             // Item
            $row->item_code,                // Item (child)
            $row->name,                     // Item (child) (child)
            $row->item_code,                // Search Key I
            'Product',
            $p?->buy_from_bp ?: '',         // Buy-from BP
            '',
            '',
            '',                     // BP child & Buyer
            $unit,                          // Purchase Unit
            $row->unit_child,               // Purchase Unit (child)
            'PSG',
            'Purchase Statistical Group', // Stat Group
            '400',
            '',
            '',
            '',              // Office
            $p?->currency ?: 'IDR',         // Currency
            'Indonesia Rupiah',             // Currency (child)
            $unit,                          // Price Unit
            $row->unit_child,               // Price Unit child
            $p?->standard_cost ?: 0,        // Purchase Price
            'PPG',
            'Purchase Price Group',  // Price Group
            $p?->tax_code ?: '',
            '',        // Tax
            'No',
            'Goods',

            // --- DATA SUMBER HARGA 1 ---
            'Subcontracting Rate',          // Source of Price
            0,
            0,
            'IDR',
            'No',

            // --- DATA SUMBER HARGA 2 (DUPLIKAT) ---
            'Reference Activity',           // Source of Price (spasi)
            0,
            0,
            'No',
            '',

            0,
            0,
            'Warn',                   // Date Tolerance
            0,
            0,
            'Warn',                   // Qty Tolerance
            0,
            'Days',                      // Supply Time
            'Yes',
            'No',                    // Warehousing & Inspection
            'No',
            'No',
            'No',
            'No',
            '',
            '',
            0,
            0,
            0,
            0,             // Price History 1
            '',
            '',
            0,
            0,                   // Price History 2
            '',
            '',
            0,
            0,
            0,
            0              // Landed Costs
        ];
    }
}
