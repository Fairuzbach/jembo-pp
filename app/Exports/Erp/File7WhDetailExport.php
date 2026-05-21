<?php

namespace App\Exports\Erp;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class File7WhDetailExport implements FromCollection, WithHeadings, WithMapping
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
        $headerString = "Import Status,Import Code,Import Message,Company,Warehouse,Warehouse (child),Item,Item (child),Item (child) (child),Item Warehouse Status,Storage Zone,Storage Zone (child),Default Location Type for Inbound Advice,Package Definition,Package Definition (child),Order Costs,Order Costs (child),Inventory Carrying Costs per Year,Allocation Level,Net Change,Text,Registration Level,Issue Priority,Ownership for Return to Warehouse,Usage at Warehouse Transfer,Inventory Inspection,Frequency for Inventory Inspection,Allow Stock Point Issue during Inventory Inspection,Use Item Ordering Data by Site  ,Update Inventory/Order Data,Exclude from Cycle Counting,Process Inventory Variances Automatically,QM Overrules Warehouse Inbound Order Type,Use Item Ordering Data  ,Order Interval,whwmd210.oivu,First Allowed Order Date,First Allowed Order Date (child),Last Allowed Order Date,Last Allowed Order Date (child),Inbound,whwmd210.iltu,Outbound,whwmd210.oltu,Cross-dock,whwmd210.cdlu,Safety,whwmd210.sftu,Use Item Ordering Data,Forecast Method,Forecast Method (child),Expected Annual Issue,Expected Annual Issue (child),Period,whwmd210.ptyp,Season Pattern for Demand,Season Pattern for Demand (child),Service Level,Slow-Moving Percentage,ABC Code,Manually Entered ABC Code,Use Item Ordering Data,Order System  ,Order Method  ,Order Quantity Increment,Order Quantity Increment (child),Minimum Order Quantity,Minimum Order Quantity (child),Minimum Order Quantity (child) (child),Maximum Order Quantity,Maximum Order Quantity (child),Fixed Order Quantity,Fixed Order Quantity (child),Maximum Inventory,Maximum Inventory (child),Seasonal Pattern,Seasonal Pattern (child),whwmd210.vlti,Default Supply System  ,Handle Subcontracted Items,Supplier,Supplier (child),Supply Time,whwmd210.sutu,Supply Time for Internal Subcontracting,whwmd210.situ,Minimum Time to Delivery,whwmd210.mitu,Maximum Time to Delivery,whwmd210.matu,Minimum Time until Expiration,whwmd210.mutu,Multiple Suppliers,Use Item Ordering Data,Default Replenishment System,Minimum Order Quantity,Minimum Order Quantity (child),Order Quantity Increment,Order Quantity Increment (child),Maximum Order Quantity,Maximum Order Quantity (child),Print kanban automatically,Maximum Number of Kanbans,Use Item Ordering Data,Use Item Ordering Data,Automatically Release Production Orders  ,Generate Order Advice  ,Combine Order Advice  ,Use Item Ordering Data,Automatically Confirm Advice  ,Safety Stock,Safety Stock (child),Reorder Point,Reorder Point (child),Seasonal Pattern,Seasonal Pattern (child),Planner,Planner (child),Buyer,Buyer (child),Inventory Valuation Method  ,Last Modification Date,Last Modification Date (child),Issue Strategy,Specific Issue Sequence,Setup Cost,Setup Cost (child),Valuation Price,Valuation Price (child),Valuation Price Unit,Valuation Price Unit (child),Moving Average Unit Cost,Moving Average Unit Cost (child),Base Price for MAUC calculation,Base Price for MAUC calculation (child),Time Base Price Evaluated,Time Base Price Evaluated (child),Surcharge Base,Surcharge Base (child),Surcharge Amount,Surcharge Amount (child),Surcharge Percentage,ABC Code,Manually Entered ABC Code,ABC Code,Manually Entered ABC Code,Item Warehouse Signal,Item Warehouse Signal (child),Include Non-allocated Inventory in CTP,Include Allocated Inventory in CTP,Last Count Date,Last Count Date (child),Standard Cost,Standard Cost (child),Allocation Horizon,Allocation Horizon (child),Cost Component,Cost Component (child),Date of Base Price Evaluation,Date of Base Price Evaluation (child),Use Item Ordering Data,First Allowed Order Date,First Allowed Order Date (child),Last allowed Order Date,Last allowed Order Date (child),Economic Order Quantity,Economic Order Quantity (child),Order Costs,Order Costs (child),First Allowed Order Date,First Allowed Order Date (child),Last allowed Order Date,Last allowed Order Date (child),Fixed Order Quantity,Fixed Order Quantity (child),Setup Cost,Setup Cost (child),Minimum Inventory Tolerance,Minimum Inventory Tolerance (child),Maximum Inventory Tolerance,Maximum Inventory Tolerance (child),Replenishment Signal Horizon,Replenishment Signal Horizon (child),Issue Ownership Registration,Receipts Ownership Registration,Exclude from Cycle Counting,Perform Physical Inventory if Inventory becomes Zero";
        return explode(',', $headerString);
    }

    public function map($row): array
    {
        $whCode = $row->warehouse?->warehouse_code ?: 'WHSPT';
        $unit = strtolower($row->unit ?: 'pcs');
        $unitChild = $row->unit_child ?: 'Pieces buah';

        $data = [
            '',
            '',
            '',
            400,
            $whCode,
            'Warehouse Sparepart',          // Warehouse (child)
            '',                             // Item (Dikosongkan)
            $row->item_code,                // Item (child)
            $row->name,                     // Item (child) (child)
            'Active',                       // Status
            '',
            '',
            'Not Applicable',
            '',
            '',
            0,
            'IDR',
            0,                    // Order costs & Carrying costs
            'Warehouse',
            'No',
            'No',
            'Warehouse',
            'Owned Inventory First',
            'Company Owned',
            'Always',
            'No',
            0,
            'No',                  // Inspection fields
            'Yes',
            'No',
            'No',
            'Yes',
            'Yes',
            'Yes',
            0,
            'Days',                      // Order interval
            '',
            '',
            '9999-12-31',
            '07:00:00', // Allowed dates
            0,
            'Hours',
            0,
            'Hours',
            0,
            'Hours',
            0,
            'Hours',
            'Yes',                          // Use Item Ordering Data
            '',
            '',
            0,
            $unitChild,                     // Expected Annual Issue (child)
            1,
            'Month',
            '',
            '',
            0,
            0,
            '',
            'No',
            'Yes',                          // Use Item Ordering Data
            'Planned',
            'Lot for Lot',
            1,
            $unit,                       // Qty increment
            0,
            $unit,
            $unit,                // Min order qty
            99999999,
            $unit,                // Max order qty
            1,
            $unit,                       // Fixed order qty
            1,
            $unit,                       // Max inventory
            '',
            '',
            0,                      // Seasonal
            'Standard Cost',                // Default Supply System
            'No',
            '',
            '',
            0,
            'Hours',
            0,
            'Hours',
            0,
            'Hours',
            0,
            'Hours',
            0,
            'Hours',
            'No',
            'Yes',
            'None',
            0,
            $unit,
            1,
            $unit,
            99999999,
            $unit,
            'No',
            0,
            'Yes',
            'Yes',
            'Yes',
            'Yes',
            'No',
            'Yes',
            'No',
            0,
            $unit,                       // Safety Stock
            0,
            $unit,                       // Reorder Point
            '',
            '',
            '',
            '',
            '',
            '',         // Planner & Buyer
            'Standard Cost',                // Inventory Valuation Method
            now()->format('Y-m-d'),
            now()->format('H:i:s'),
            'No',
            'None',
            'No'
        ];

        // Memastikan total array adalah 196 kolom dengan mengisi sisa kolom menggunakan string kosong
        return array_pad($data, 196, '');
    }
}
