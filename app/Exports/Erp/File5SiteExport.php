<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File5SiteExport implements FromCollection, WithHeadings, WithMapping
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
            'Site',
            'Site (child)',
            'Use Global Item Warehousing',
            'Item Type',
            'Item Group',
            'Item Group (child)',
            'Item Valuation Group',
            'Item Valuation Group (child)',
            'Search Key',
            'Package Definition',
            'Package Definition (child)',
            'Allocation Level',
            'Location Controlled',
            'Process Inventory Variances Automatically',
            'Packing Instructions',
            'Item Text',
            'Use Item Ordering Data by Site  ',
            'Default Supply System  ',
            'Automatically Release Production Orders  ',
            'Generate Order Advice  ',
            'Combine Order Advice  ',
            'Automatically Confirm Advice  ',
            'Ownership Registration Level  ',
            'Ownership Issue Priority  ',
            'Ownership for Return to Warehouse  ',
            'Exclude from Cycle Counting',
            'If Inventory becomes Zero',
            'Hazardous Material',
            'Class of Risk',
            'Floor Stock',
            'Inventory Carrying Costs',
            'Inventory Carrying Costs (child)',
            'Inventory Inspection',
            'Allow Stock Point Issue during Inventory Inspection',
            'Frequency for Inventory Inspection',
            'Length',
            'Length (child)',
            'Width',
            'Height',
            'Floor Space',
            'Floor Space (child)',
            'Volume',
            'Volume (child)',
            'Weight',
            'Weight (child)',
            'Slow-Moving Percentage',
            'Expected Annual Issue',
            'Expected Annual Issue (child)',
            'Forecast Method',
            'Forecast Method (child)',
            'ABC Code',
            'Manually Entered ABC Code',
            'Period Length',
            'whwmd404.ptyp',
            'whwmd404.ptyp (child)',
            'Shelf Life [Periods]',
            'Shelf Life [Periods] (child)',
            'Disposition Due Lead Time',
            'whwmd404.tmun',
            'whwmd404.tmun (child)',
            'Lot Tracking',
            'Lots in Inventory',
            'Default Inventory Lot Size',
            'Lot Price',
            'Lot Entry for Direct Delivery',
            'Lot Entry During Receipt',
            'Lot Entry During Transfer',
            'Register Lot Issue During As Built',
            'Register Lot Issue in Service & Maintenance',
            'Handling Units in Use',
            'Handling Unit Version Controlled',
            'Log Version History',
            'Track Handling Unit Status'
        ];
    }

    public function map($row): array
    {
        $w = $row->warehouse;

        return [
            '',
            '',
            '',
            400,
            '',                             // Item (Kosong)
            $row->item_code,                // Item (child) (Kode)
            $row->name,                     // Item (child) (child) (Nama)
            'JCC1',                         // Site
            'Pt Jembo Factory (Main)',      // Site (child)
            'Yes',                          // Use Global Item Warehousing
            'Product',
            $row->item_group,               // Item Group
            $row->item_group_child,         // Item Group (child) (Inputan User)
            '',
            '',                         // Valuation Group & child
            $row->item_code,                // Search Key
            '',
            '',                         // Package Def & child
            'Warehouse',                    // Allocation Level
            'Yes',
            'Yes',
            'No',
            'No',
            'Yes',
            'None',
            'No',
            'No',
            'No',
            'No',
            'Warehouse',
            'Owned Inventory First',
            'Company Owned',
            'No',
            'No Cycle Count',
            $w?->hazardous_material ? 'Yes' : 'No',
            $w?->class_of_risk ?: '',
            'No',
            0,
            'IDR',
            'No',
            'No',
            0,
            $w?->length ?: 0,
            'm',
            $w?->width ?: 0,
            $w?->height ?: 0,
            0,
            '',
            0,
            '',                   // Floor Space & Volume
            $w?->weight ?: 0,
            'kg',
            0,
            0,
            $row->unit_child,               // Expected Annual Issue (child) (Inputan User)
            'Not Applicable',
            '',
            'C',
            'C',
            0,
            1,
            'Month',                  // Period length & ptyp
            0,
            '',
            0,
            0,
            'Days',
            'No',
            'No',
            0,
            'IDR',
            'No',
            'No',
            'No',
            'No',
            'No',
            'No',
            'No',
            'No',
            'No'
        ];
    }
}
