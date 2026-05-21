<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class File1ItemEnumSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'enums'; // Wajib huruf kecil
    }

    public function array(): array
    {
        return [
            // Baris 1: Kode Enum (Hidden References)
            ['tckitm', '', 'tcosys', '', 'tcyesno', '', 'tcitmt', '', 'tcpgtp', '', 'tccoeu', '', 'tcibd.repl', '', 'tcenvc', ''],
            // Baris 2: Header Label
            ['value', 'description', 'value', 'description', 'value', 'description', 'value', 'description', 'value', 'description', 'value', 'description', 'value', 'description', 'value', 'description'],
            // Baris 3 - 17: Data Dictionary
            ['1', 'Purchased', '1', 'SIC', '1', 'Yes', '10', 'Not Applicable', '5', 'Customer Based', '5', 'Company', '5', 'Not Applicable', '5', 'Not Applicable'],
            ['2', 'Manufactured', '2', 'Planned', '2', 'No', '20', 'Tool', '7', 'Customer Location Based', '10', 'Enterprise Unit', '10', 'Replaced', '10', 'No Data'],
            ['3', 'Generic (Obsolete)', '4', 'FAS', '', '', '30', 'Kit', '10', 'Order Based', '15', 'Not Applicable', '15', 'Substitute', '15', 'Pass'],
            ['4', 'Cost (Obsolete)', '9', 'Manual', '', '', '40', 'Option', '15', 'Customer Reference Based', '', '', '20', 'Replaced Substitute', '20', 'Pass with Exemptions'],
            ['5', 'Service (Obsolete)', '', '', '', '', '50', 'Menu', '20', 'Internal Reference Based', '', '', '', '', '25', 'Fail'],
            ['6', 'Subcontracted Service (Obsolete)', '', '', '', '', '', '', '90', 'Not Applicable', '', '', '', '', '30', 'Conditional Fail'],
            ['10', 'List (Obsolete)', '', '', '', '', '', '', '', '', '', '', '', '', '35', 'Partial Fail'],
            ['12', 'Tool (Obsolete)', '', '', '', '', '', '', '', '', '', '', '', '', '40', 'Not Relevant'],
            ['15', 'Equipment (Obsolete)', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['20', 'Engineering Module (Obsolete)', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['30', 'Product', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['35', 'Rental Product', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['40', 'Tool', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['50', 'Equipment', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['60', 'Subcontracted Service (Obsolete)', '', '', '', '', '', '', '', '', '', '', '', '', '', '']
        ];
    }
}
