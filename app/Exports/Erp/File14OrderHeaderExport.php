<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File14OrderHeaderExport implements FromCollection, WithHeadings, WithMapping
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
            'Method',
            'Interval',
            'tcibd200.oivu',
            'Safety Stock',
            'Safety Stock (child)',
            'Seasonal Pattern',
            'Seasonal Pattern (child)',
            'Safety Time',
            'tcibd200.tuni',
            'Reorder Point',
            'Reorder Point (child)',
            'Planner',
            'Planner (child)',
            'Order Increment',
            'Order Increment (child)',
            'Minimum',
            'Minimum (child)',
            'Maximum',
            'Maximum (child)',
            'Fixed Order',
            'Fixed Order (child)',
            'Lot Size Calculation Allowed',
            'Maximum Inventory',
            'Maximum Inventory (child)',
            'First Allowed Order Date',
            'First Allowed Order Date (child)',
            'Last allowed Order Date',
            'Last allowed Order Date (child)',
            'Economic Order Quantity',
            'Economic Order Quantity (child)',
            'Seasonal Pattern ',
            'Seasonal Pattern (child) ',
            'Service Level',
            'Use Recommended Quantity',
            'Order Costs',
            'Order Costs (child)'
        ];
    }

    public function map($row): array
    {
        $o = $row->orderRule;
        $unit = strtolower($row->unit ?: 'pcs');
        $unitChild = $row->unit_child ?: 'Pieces buah';

        return [
            '',
            '',
            '',
            400,
            '',                             // Item (Kosong)
            $row->item_code,                // Item (child)
            $row->name,                     // Item (child) (child)
            'Lot for Lot',                  // Method
            0,
            'Days',

            // --- DATA ORDERING ---
            $o?->safety_stock ?: 0,
            $unitChild,                     // Safety Stock (child)
            '',
            '',
            0,
            'Hours',
            $o?->reorder_point ?: 0,
            $unitChild,                     // Reorder Point (child)
            '',
            '',
            1,
            $unit,                       // Order Increment
            $o?->min_order_qty ?: 0,
            $unit,                          // Minimum (child)
            $o?->max_order_qty ?: 999999999,
            $unit,                          // Maximum (child)
            1,
            $unit,                       // Fixed Order
            'No',
            999999999,
            $unit,               // Max Inventory

            '1970-01-01',
            '07:00:00',       // Start Date
            '9999-12-31',
            '07:00:00',       // End Date

            1,
            $unit,                       // EOQ
            '',
            '',
            0,
            'No',
            0,
            'IDR'                        // Order Costs
        ];
    }
}
