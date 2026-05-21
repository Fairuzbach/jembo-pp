<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File10ServiceExport implements FromCollection, WithHeadings, WithMapping
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
            'Text',
            'Item Type',
            'Logistic Company',
            'Logistic Company (child)',
            'Service Office',
            'Service Office (child)',
            'Operations Department',
            'Operations Department (child)',
            'Configuration Controlled',
            'Repairable',
            'Ownership',
            'Critical for Inventory Check',
            'Process to Service after Delivery',
            'Service Item Group',
            'Service Item Group (child)',
            'Serialized Item Group',
            'Serialized Item Group (child)',
            'Site',
            'Site (child)',
            'Warehouse',
            'Warehouse (child)',
            'Location',
            'Location (child)',
            'Site ',
            'Site (child) ',
            'Warehouse ',
            'Location ',
            'Location (child) ',
            'Site  ',
            'Site (child)  ',
            'Warehouse  ',
            'Warehouse (child)  ',
            'Site   ',
            'Site (child)   ',
            'Warehouse   ',
            'Warehouse (child)   ',
            'Service Kit Type',
            'Service Kit Type (child)',
            'Delivery Type',
            'Use Residual Value',
            'Return unconsumed Items to Warehouse',
            'Quote Mandatory before Releasing Work Order',
            'Currency',
            'Currency (child)',
            'Sales Price',
            'Last Transaction Date',
            'Last Transaction Date (child)',
            'Sales Unit',
            'Sales Unit (child)',
            'Sales Price Unit',
            'Sales Price Unit (child)',
            'Serialized Item Warranty Terms',
            'Warranty Template',
            'Warranty Template (child)',
            'Generic Warranty',
            'Generic Warranty (child)',
            'Supplier Warranty',
            'Supplier Warranty (child)',
            'Contract Discount Scheme',
            'Contract Discount Scheme (child)',
            'Covered by Contract',
            'Repair Warranty Duration',
            'Repair Warranty Duration (child)',
            'Life Cycle',
            'Life Cycle (child)',
            'Life Cycle (child) (child)',
            'Generate Planned Activities'
        ];
    }

    public function map($row): array
    {
        $unit = strtolower($row->unit ?: 'pcs');
        $unit_child = $row->unit_child ?: 'Pieces buah';

        return [
            '',
            '',
            '',
            400,
            '',                             // Item (Kosong)
            $row->item_code,                // Item (child) (Kode)
            $row->name,                     // Item (child) (child) (Nama)
            'No',                           // Text
            'Product',
            '400',                          // Logistic Company
            'PT. JEMBO CABLE COMPANY TBK',  // Logistic Company (child)
            '',
            '',
            '',
            '',                 // Dept & Office
            'Serialized',                   // Configuration Controlled
            'No',                           // Repairable
            'Company Owned',                // Ownership
            'No',
            'No',
            'SIG',
            'Service Item Group',    // Service Item Group
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '', // Site/Warehouse blanks
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '', // More site/wh blanks
            'Not Applicable',
            '',
            'From Warehouse',               // Delivery Type
            'No',
            'No',
            'No',
            'IDR',
            'Indonesia Rupiah',

            // --- PERBAIKAN: WAJIB ANGKA 0 ---
            0,                              // Sales Price

            now()->format('Y-m-d'),
            now()->format('H:i:s'),

            // --- PERBAIKAN: HURUF KECIL ---
            $unit,                          // Sales Unit
            $unit_child,                    // Sales Unit (child)
            $unit,                          // Sales Price Unit
            $unit_child,                    // Sales Price Unit (child)

            'No',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'Covered',

            // --- PERBAIKAN: WAJIB ANGKA 0 ---
            0,                              // Repair Warranty Duration

            'Day',
            '',
            '',
            '',
            'No'
        ];
    }
}
