<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting; // WAJIB DITAMBAHKAN
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date; // WAJIB DITAMBAHKAN
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // WAJIB DITAMBAHKAN

class File1MasterItemExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithColumnFormatting
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
    public function title(): string
    {
        return 'data';
    }
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Baris 1 Bold
            'A1:C1' => ['font' => ['italic' => true]], // Import Status, Code, Message Italic
        ];
    }

    // TEPAT 89 KOLOM DARI FILE BARU
    public function headings(): array
    {
        return [
            'Import Status',
            'Import Code',
            'Import Message',
            'Company',
            'Item',
            'Item (child)',
            'Description',
            'Item Type',
            'Item Group',
            'Item Group (child)',
            'Order System',
            'Unit Set',
            'Unit Set (child)',
            'Unit',
            'Unit (child)',
            'Customizable',
            'Customized',
            'With PCS',
            'With PCS (child)',
            'Sales  ',
            'Ordering  ',
            'Production  ',
            'Purchase  ',
            'Warehousing  ',
            'Service  ',
            'Quality  ',
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
            'Value',
            'Value (child)',
            '/',
            'Last Modification Date',
            'Last Modification Date (child)',
            'Based on Default',
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
            'HS Code',
            'HS Code (child)',
            'Environmental Compliance'
        ];
    }

    public function map($item): array
    {
        return [
            '',
            '',
            '',
            400,
            '',
            $item->item_code,
            $item->name,
            'Product',                         // (H) Item Type -> Dropdown
            $item->group_code ?: 'RM-001',
            $item->group_name ?: 'Raw Material',
            'Planned',                         // (K) Order System -> Dropdown
            'STD',
            'Standard Unit Set',
            $item->unit ?: 'pcs',
            '',
            'No',
            'No',
            'No',
            '',              // (P, Q, R) Yes/No -> Dropdown
            'No',
            'Yes',
            'No',
            'Yes',
            'Yes',
            'Yes',
            'No', // (T - Z) Modul Flags -> Dropdown
            'No',
            '',
            '',
            '',
            '',
            'Not Applicable',                  // (AF) List Type -> Dropdown
            Date::dateTimeToExcel(now()), // AG: Creation Date
            Date::dateTimeToExcel(now()), // AH: Creation Time
            Date::dateTimeToExcel(now()), // AI: Last Mod Date
            Date::dateTimeToExcel(now()), // AJ: Last Mod Time
            'No',
            'No',
            '',
            'No',
            '',
            'No',
            'No',
            'Not Applicable',                  // (AR) Demand Pegging Type -> Dropdown
            'No',
            '',
            '',
            'Enterprise Unit',                 // (AV) Std Cost At Level -> Dropdown
            0,
            '',
            '/',
            Date::dateTimeToExcel(now()), // BA: Last Mod Date (2)
            Date::dateTimeToExcel(now()), // BB: Last Mod Time (2)
            '',
            'No',
            'No',
            '',
            '',
            'Not Applicable',                  // (BG) Type of Replacement -> Dropdown
            'No',
            '',
            '',
            '',
            'No',
            0,
            '',
            '',
            '',
            '',
            '',
            'No',
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
            'Not Relevant'                     // (CK) Env Compliance -> Dropdown
        ];
    }

    /**
     * FUNGSI UNTUK MENGHUBUNGKAN KOLOM KE DROPDOWN ENUMS
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rowCount = count($this->items) + 1; // +1 untuk Header

                // Mapping Kolom Excel ke referensi kolom 'enums' (Description column)
                $dropdowns = [
                    'H'  => 'enums!$B$3:$B$17', // Item Type
                    'K'  => 'enums!$D$3:$D$6',  // Order System
                    'P'  => 'enums!$F$3:$F$4',  // Customizable (Yes/No)
                    'Q'  => 'enums!$F$3:$F$4',  // Customized
                    'R'  => 'enums!$F$3:$F$4',  // With PCS
                    'T'  => 'enums!$F$3:$F$4',  // Sales
                    'U'  => 'enums!$F$3:$F$4',  // Ordering
                    'V'  => 'enums!$F$3:$F$4',  // Production
                    'W'  => 'enums!$F$3:$F$4',  // Purchase
                    'X'  => 'enums!$F$3:$F$4',  // Warehousing
                    'Y'  => 'enums!$F$3:$F$4',  // Service
                    'Z'  => 'enums!$F$3:$F$4',  // Quality
                    'AA' => 'enums!$F$3:$F$4',  // Item Text
                    'AF' => 'enums!$H$3:$H$7',  // List Type
                    'AK' => 'enums!$F$3:$F$4',  // Change Controlled
                    'AN' => 'enums!$F$3:$F$4',  // Multiple COs
                    'AP' => 'enums!$F$3:$F$4',  // In Process by CHM
                    'AQ' => 'enums!$F$3:$F$4',  // Demand Pegged
                    'AR' => 'enums!$J$3:$J$8',  // Demand Pegging Type
                    'AS' => 'enums!$F$3:$F$4',  // Use Unallocated Inv
                    'AV' => 'enums!$L$3:$L$5',  // Standard Costs at Level
                    'BD' => 'enums!$F$3:$F$4',  // Lot Controlled
                    'BE' => 'enums!$F$3:$F$4',  // Serialized
                    'BG' => 'enums!$N$3:$N$6',  // Type of Replacement
                    'BH' => 'enums!$F$3:$F$4',  // Revision Controlled
                    'BL' => 'enums!$F$3:$F$4',  // Critical Safety Item
                    'BR' => 'enums!$F$3:$F$4',  // Contains Material
                    'CK' => 'enums!$P$3:$P$10', // Environmental Compliance
                ];

                foreach ($dropdowns as $col => $formula) {
                    $validation = $sheet->getCell("{$col}2")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1($formula);

                    // Replikasi Dropdown ini sampai 100 baris ke bawah (atau sebanyak data item)
                    for ($i = 2; $i <= ($rowCount + 100); $i++) {
                        $sheet->getCell("{$col}{$i}")->setDataValidation(clone $validation);
                    }
                }
                $sheet->getComment('A1')->getText()->createTextRun("Field: Import Status\nField: tcexp001.stat\nDomain: tcyesno");
                $sheet->getComment('B1')->getText()->createTextRun("Field: Import Code\nField: tcexp001.idco\nDomain: tcexp.idco");
                $sheet->getComment('C1')->getText()->createTextRun("Field: Import Message\nField: tcexp001.mess\nDomain: tcexp.mess");
                $sheet->getComment('E1')->getText()->createTextRun("Field: Item\nField: tcibd001.item\nDomain: tcitem");
            },
        ];
    }
    public function columnFormats(): array
    {
        return [
            'AG' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'AH' => NumberFormat::FORMAT_DATE_TIME4,
            'AI' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'AJ' => NumberFormat::FORMAT_DATE_TIME4,
            'BA' => NumberFormat::FORMAT_DATE_YYYYMMDD,
            'BB' => NumberFormat::FORMAT_DATE_TIME4,
        ];
    }
}
