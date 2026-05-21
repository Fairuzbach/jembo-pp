<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class File1ItemExportMaster implements WithMultipleSheets
{
    protected $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function sheets(): array
    {
        return [
            new File1MasterItemExport($this->items), // Sheet 1: 'data'
            new File1ItemEnumSheet()              // Sheet 2: 'enums'
        ];
    }
}
