<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File6WhWarehouseExport implements FromCollection, WithHeadings, WithMapping
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
            'Warehouse',
            'Item',
            'Item (child)',
            'Status  ',
            'Use Item Ordering Data by Site  ',
            'Inventory Valuation Method  ',
            'Order System  ',
            'Order Method  ',
            'Supply System  ',
            'Handling Units in Use  ',
            'Text',
            'Item Group'
        ];
    }

    public function map($row): array
    {
        return [
            '',
            '',
            '',
            400,
            $row->warehouse?->warehouse_code ?: 'WHSPT',
            '',
            $row->item_code,
            'Active',
            'Yes',
            'Standard Cost',
            'Planned',
            'Lot for Lot',
            'None',
            'No',
            'No',
            $row->item_group ?: 'SPT-03'
        ];
    }
}
