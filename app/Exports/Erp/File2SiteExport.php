<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class File2SiteExport implements FromCollection, WithHeadings, WithMapping, WithTitle
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
        // Tepat 91 Kolom sesuai file referensi "2. Master Data - Item By Site.xlsx"
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
            'Item Type',
            'Item Group',
            'Item Group (child)',
            'Order System',
            'Unit Set',
            'Unit Set (child)',
            'Inventory Unit',
            'Inventory Unit (child)',
            'Customizable',
            'Customized',
            'With PCS',
            'Use Global Item',
            'Product Group',
            'Product Group (child)',
            'Default Supply Source',
            'Actual Supply Source',
            'Source by Date  ',
            'Source by Date (child)  ',
            'Sales  ',
            'Ordering  ',
            'Production  ',
            'Purchase  ',
            'Warehousing  ',
            'Service  ',
            'Planning  ',
            'Item Text',
            'Item Signal',
            'Item Signal (child)',
            'Search Key I',
            'Search Key II',
            'List Type',
            'Creation Date',
            'Creation Date (child)',
            'Last Modification Date',
            'Last Modification Date (child)',
            'Change Controlled',
            'Effectivity Dates by CO',
            'Change Order',
            'Multiple COs',
            'Effective Date',
            'In Process by CHM',
            'Demand Pegged',
            'Demand Pegging Type',
            'Use Unallocated Inventory',
            'Cost Component',
            'Cost Component (child)',
            'Standard Costs at Level',
            'Lot Controlled',
            'Serialized',
            'Derived-from Item',
            'Derived-from Item (child)',
            'Type of Replacement',
            'Revision Controlled',
            'Actual Revision',
            'Responsible Department',
            'Responsible Department (child)',
            'Critical Safety Item',
            'Weight',
            'Weight Unit',
            'Weight Unit (child)',
            'Material',
            'Size',
            'Standard',
            'Contains Material',
            'Product Type',
            'Product Type (child)',
            'Product Class',
            'Product Class (child)',
            'Product Line',
            'Product Line (child)',
            'Manufacturer',
            'Manufacturer (child)',
            'Selection Code',
            'Selection Code (child)',
            'Technical Coordinator',
            'Technical Coordinator (child)',
            'Country of Origin',
            'Country of Origin (child)',
            'EAN Code',
            'Harmonized System Code',
            'Harmonized System Code (child)',
            'Environmental Compliance'
        ];
    }

    public function map($row): array
    {
        return [
            '',
            '',
            '',
            400,                            // Company
            '',                             // Item (Kosong)
            $row->item_code,                // Item (child) (Kode Barang)
            $row->name,                     // Item (child) (child) (Nama Barang)
            'JCC1',                         // Site
            'Pt Jembo Factory (Main)',      // Site (child)
            'Product',                      // Item Type
            $row->item_group,               // Item Group
            $row->item_group_child,         // Item Group (child) (Inputan User)
            'Planned',                      // Order System
            'STD',                          // Unit Set
            'Standard Unit Set',            // Unit Set (child)
            strtolower($row->unit),         // Inventory Unit (Huruf kecil)
            $row->unit_child,               // Inventory Unit (child) (Inputan User)
            'No',                           // Customizable
            'No',                           // Customized
            'No',                           // With PCS
            'Yes',                          // Use Global Item
            '',
            '',                         // Product Group & child
            'Purchase',                     // Default Supply Source
            'Purchase',                     // Actual Supply Source
            'No',                           // Source by Date
            '',                             // Source by Date (child)
            'No',                           // Sales
            'Yes',                          // Ordering
            'No',                           // Production
            'Yes',                          // Purchase
            'Yes',                          // Warehousing
            'Yes',                          // Service
            'No',                           // Planning
            'No',                           // Item Text
            '',                             // Item Signal
            '',                             // Item Signal (child)
            $row->item_code,                // Search Key I
            '',                             // Search Key II
            'Not Applicable',               // List Type
            now()->format('Y-m-d'),         // Creation Date
            now()->format('H:i:s'),         // Creation Date (child)
            now()->format('Y-m-d'),         // Last Modification Date
            now()->format('H:i:s'),         // Last Modification Date (child)
            'No',
            'No',
            '',
            'No',
            '',
            'No',
            'No',
            'Not Applicable',               // Demand Pegging Type
            'No',
            '',
            '',
            'Enterprise Unit',              // Standard Costs at Level
            'No',                           // Lot Controlled
            optional($row->serialization)->is_serialized ? 'Yes' : 'No', // Serialized
            '',
            '',                         // Derived Item
            'Not Applicable',               // Type of Replacement
            'No',                           // Revision Controlled
            '',
            '',
            '',                     // Revision & Dept
            'No',                           // Critical Safety Item
            optional($row->warehouse)->weight ?: 0, // Weight
            'kg',                           // Weight Unit
            '',
            '',
            '',
            '',
            'No',                           // Contains Material
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
            '',
            '',
            '',
            '',
            '',
            'Not Relevant'                  // Environmental Compliance
        ];
    }
    public function title(): string
    {
        // GANTI INI dengan nama sheet yang sama PERSIS dengan file asli ERP
        return 'data';
    }
}
