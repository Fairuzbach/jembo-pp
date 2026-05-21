<?php

namespace App\Http\Controllers;

use App\Models\MasterItem;
use App\Models\ItemRequest;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

use ZipArchive;

class MasterItemExportController extends Controller
{
    public function exportZip()
    {
        // 1. Ambil data dari database yang belum di-sync
        // Tambahkan relasi 'group' di dalam fungsi with()
        $items = MasterItem::with(['warehouse', 'procurement', 'orderRule', 'serialization', 'group'])
            ->where('is_synced', false)
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Tidak ada data baru untuk di-export.');
        }

        // 2. Persiapkan ZIP
        $zipFileName = 'ERP_IMPORT_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {

            if (!Storage::disk('public')->exists('temp')) {
                Storage::disk('public')->makeDirectory('temp');
            }

            // =============================================================
            // PROSES FILE 1: Master Data - Item (Metode Template)
            // =============================================================

            $templatePath = storage_path('app/templates/1. Master Data - Item.xlsx');

            if (!file_exists($templatePath)) {
                return back()->with('error', 'Template file tidak ditemukan di storage/app/templates/');
            }

            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getSheetByName('data');

            if (!$sheet) {
                return back()->with('error', 'Sheet bernama "data" tidak ditemukan pada template.');
            }

            $rowNum = 2;
            $excelDate = Date::dateTimeToExcel(now());

            foreach ($items as $item) {

                // --- INFORMASI UTAMA ---
                $sheet->setCellValue('D' . $rowNum, 400);
                $sheet->setCellValue('F' . $rowNum, $item->item_code);
                $sheet->setCellValue('G' . $rowNum, $item->name);

                // ============================================================
                // UPDATE: MENGAMBIL DARI TABEL ITEM_GROUPS
                // ============================================================
                $sheet->setCellValue('I' . $rowNum, $item->group ? $item->group->code : '');
                $sheet->setCellValue('J' . $rowNum, $item->group ? $item->group->description : '');
                // ============================================================

                $sheet->setCellValue('N' . $rowNum, $item->unit ?: 'pcs');

                // --- DEFAULT VALUE (HIGHLIGHT HIJAU) ---
                $sheet->setCellValue('H' . $rowNum, 'Product'); // Item Type
                $sheet->setCellValue('K' . $rowNum, 'Planned'); // Order System
                $sheet->setCellValue('L' . $rowNum, 'STD'); // Unit Set
                $sheet->setCellValue('M' . $rowNum, 'Standard Unit Set'); // Unit Set (child)

                $sheet->setCellValue('P' . $rowNum, 'No'); // Customizable
                $sheet->setCellValue('Q' . $rowNum, 'No'); // Customized
                $sheet->setCellValue('R' . $rowNum, 'No'); // With PCS

                $sheet->setCellValue('T' . $rowNum, 'No');  // Sales
                $sheet->setCellValue('U' . $rowNum, 'Yes'); // Ordering
                $sheet->setCellValue('V' . $rowNum, 'No');  // Production
                $sheet->setCellValue('W' . $rowNum, 'Yes'); // Purchase
                $sheet->setCellValue('X' . $rowNum, 'Yes'); // Warehousing
                $sheet->setCellValue('Y' . $rowNum, 'Yes'); // Service
                $sheet->setCellValue('Z' . $rowNum, 'No');  // Quality
                $sheet->setCellValue('AA' . $rowNum, 'No'); // Item Text

                $sheet->setCellValue('AD' . $rowNum, $item->name); // Search Key I (G2)
                $sheet->setCellValue('AE' . $rowNum, $item->item_code); // Search Key II (F2)

                $sheet->setCellValue('AF' . $rowNum, 'Not Applicable');

                // TANGGAL INPUT
                $sheet->setCellValue('AG' . $rowNum, $excelDate);
                $sheet->setCellValue('AH' . $rowNum, $excelDate);
                $sheet->setCellValue('AI' . $rowNum, $excelDate);
                $sheet->setCellValue('AJ' . $rowNum, $excelDate);

                $sheet->setCellValue('AK' . $rowNum, 'No');
                $sheet->setCellValue('AL' . $rowNum, 'No');
                $sheet->setCellValue('AN' . $rowNum, 'No');
                $sheet->setCellValue('AP' . $rowNum, 'No');
                $sheet->setCellValue('AQ' . $rowNum, 'No');
                $sheet->setCellValue('AR' . $rowNum, 'Not Applicable');
                $sheet->setCellValue('AS' . $rowNum, 'Yes');

                $sheet->setCellValue('AV' . $rowNum, 'Enterprise Unit');
                $sheet->setCellValue('AW' . $rowNum, 0);
                $sheet->setCellValue('AY' . $rowNum, '/');

                $sheet->setCellValue('BB' . $rowNum, 'No');
                $sheet->setCellValue('BC' . $rowNum, 'No');
                $sheet->setCellValue('BD' . $rowNum, 'No');

                $sheet->setCellValue('BF' . $rowNum, $item->item_code); // Derived-from Item child (F2)

                $sheet->setCellValue('BG' . $rowNum, 'Not Applicable');
                $sheet->setCellValue('BH' . $rowNum, 'No');
                $sheet->setCellValue('BL' . $rowNum, 'No');

                $sheet->setCellValue('BS' . $rowNum, 'No');
                $sheet->setCellValue('CK' . $rowNum, 'Not Relevant');

                $rowNum++;
            }

            $outputFileName = '1. Master Data - Item.xlsx';
            $outputPath = storage_path('app/public/temp/' . $outputFileName);

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->setPreCalculateFormulas(false);
            $writer->save($outputPath);

            $zip->addFile($outputPath, $outputFileName);

            // =============================================================
            // PROSES FILE 2: Master Data - Item By Site
            // =============================================================
            $templatePath2 = storage_path('app/templates/2. Master Data - Item By Site.xlsx');
            if (file_exists($templatePath2)) {
                $spreadsheet2 = IOFactory::load($templatePath2);
                $sheet2 = $spreadsheet2->getActiveSheet();

                // Deteksi Header Dinamis
                $highestCol2 = $sheet2->getHighestColumn();
                $headers2 = $sheet2->rangeToArray('A1:' . $highestCol2 . '1', NULL, TRUE, FALSE)[0];

                $getCol2 = function ($headerName) use ($headers2) {
                    $index = array_search($headerName, $headers2);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                $row2 = 2;
                foreach ($items as $item) {
                    // Data Dinamis
                    if ($col = $getCol2('Item (child)')) $sheet2->setCellValue($col . $row2, $item->item_code);
                    if ($col = $getCol2('Item (child) (child)')) $sheet2->setCellValue($col . $row2, $item->name);
                    if ($col = $getCol2('Site')) $sheet2->setCellValue($col . $row2, $item->warehouse->site_code ?? 'JCC1');
                    if ($col = $getCol2('Item Group')) $sheet2->setCellValue($col . $row2, $item->group->code ?? '');
                    if ($col = $getCol2('Item Group (child)')) $sheet2->setCellValue($col . $row2, $item->group->description ?? '');
                    if ($col = $getCol2('Inventory Unit')) $sheet2->setCellValue($col . $row2, $item->unit);

                    $isSerialized = $item->serialization->is_serialized ?? false;
                    if ($col = $getCol2('Serialized')) $sheet2->setCellValue($col . $row2, $isSerialized ? 'Yes' : 'No');
                    if ($col = $getCol2('Weight')) $sheet2->setCellValue($col . $row2, $item->warehouse->weight ?? 0);

                    // Data Statis (Default ERP)
                    if ($col = $getCol2('Company')) $sheet2->setCellValue($col . $row2, '400');
                    if ($col = $getCol2('Site (child)')) $sheet2->setCellValue($col . $row2, 'Pt Jembo Factory (Main)');
                    if ($col = $getCol2('Item Type')) $sheet2->setCellValue($col . $row2, 'Product');
                    if ($col = $getCol2('Order System')) $sheet2->setCellValue($col . $row2, 'Planned');
                    if ($col = $getCol2('Unit Set')) $sheet2->setCellValue($col . $row2, 'STD');
                    if ($col = $getCol2('Unit Set (child)')) $sheet2->setCellValue($col . $row2, 'Standard Unit Set');
                    if ($col = $getCol2('Customizable')) $sheet2->setCellValue($col . $row2, 'No');
                    if ($col = $getCol2('Customized')) $sheet2->setCellValue($col . $row2, 'No');
                    if ($col = $getCol2('Use Global Item')) $sheet2->setCellValue($col . $row2, 'Yes');
                    if ($col = $getCol2('Default Supply Source')) $sheet2->setCellValue($col . $row2, 'Purchase');
                    if ($col = $getCol2('Actual Supply Source')) $sheet2->setCellValue($col . $row2, 'Purchase');

                    // Kolom dengan spasi ekstra sesuai bawaan template Infor LN
                    if ($col = $getCol2('Sales  ')) $sheet2->setCellValue($col . $row2, 'No');
                    if ($col = $getCol2('Ordering  ')) $sheet2->setCellValue($col . $row2, 'Yes');
                    if ($col = $getCol2('Production  ')) $sheet2->setCellValue($col . $row2, 'No');
                    if ($col = $getCol2('Purchase  ')) $sheet2->setCellValue($col . $row2, 'Yes');
                    if ($col = $getCol2('Warehousing  ')) $sheet2->setCellValue($col . $row2, 'Yes');
                    if ($col = $getCol2('Service  ')) $sheet2->setCellValue($col . $row2, 'Yes');
                    if ($col = $getCol2('Lot Controlled')) $sheet2->setCellValue($col . $row2, 'No');
                    if ($col = $getCol2('Environmental Compliance')) $sheet2->setCellValue($col . $row2, 'Not Relevant');

                    $row2++;
                }

                $fileName2 = '2. Master Data - Item By Site.xlsx';
                $filePath2 = storage_path('app/public/temp/' . $fileName2);
                $writer2 = IOFactory::createWriter($spreadsheet2, 'Xlsx');
                $writer2->save($filePath2);
                $zip->addFile($filePath2, $fileName2);

                $spreadsheet2->disconnectWorksheets();
                unset($spreadsheet2);
            }
            // =============================================================
            // PROSES FILE 3: Warehousing - Master Data - Item - Detail - Footer
            // =============================================================
            $templatePath3 = storage_path('app/templates/3. Warehousing - Master Data - Item - Detail - Footer.xlsx');
            if (file_exists($templatePath3)) {
                $spreadsheet3 = IOFactory::load($templatePath3);
                $sheet3 = $spreadsheet3->getActiveSheet();

                $highestCol3 = $sheet3->getHighestColumn();
                $headers3 = $sheet3->rangeToArray('A1:' . $highestCol3 . '1', NULL, TRUE, FALSE)[0];

                // Fungsi tunggal untuk mencari 1 kolom
                $getCol3 = function ($headerName) use ($headers3) {
                    $index = array_search($headerName, $headers3);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                // Fungsi jamak untuk mencari kolom kembar (seperti 'Company')
                $getCols3 = function ($headerName) use ($headers3) {
                    $cols = [];
                    foreach ($headers3 as $index => $name) {
                        if ($name === $headerName) {
                            $cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return $cols;
                };

                $row3 = 2;
                foreach ($items as $item) {
                    // 1. Data Dinamis
                    if ($col = $getCol3('Item (child)')) $sheet3->setCellValue($col . $row3, $item->item_code);
                    if ($col = $getCol3('Site')) $sheet3->setCellValue($col . $row3, $item->warehouse->site_code ?? 'JCC1');

                    // 2. Data Statis
                    // Mengisi semua kolom 'Company' yang mungkin berulang
                    foreach ($getCols3('Company') as $col) {
                        $sheet3->setCellValue($col . $row3, '400');
                    }

                    if ($col = $getCol3('Item Type')) $sheet3->setCellValue($col . $row3, 'Product');
                    if ($col = $getCol3('Outbound Method')) $sheet3->setCellValue($col . $row3, 'By Location');
                    if ($col = $getCol3('Location Controlled')) $sheet3->setCellValue($col . $row3, 'Yes');
                    if ($col = $getCol3('Lot Controlled')) $sheet3->setCellValue($col . $row3, 'No');
                    if ($col = $getCol3('Lots in Inventory')) $sheet3->setCellValue($col . $row3, 'No');
                    if ($col = $getCol3('Lot Tracking')) $sheet3->setCellValue($col . $row3, 'No');

                    $row3++;
                }

                // 3. Hapus baris hantu (Ghost Rows) otomatis dari PHP untuk keamanan
                $highestRow3 = $sheet3->getHighestRow();
                if ($highestRow3 >= $row3) {
                    $sheet3->removeRow($row3, $highestRow3 - $row3 + 1);
                }

                // 4. Simpan & Masukkan ke ZIP
                $fileName3 = '3. Warehousing - Master Data - Item - Detail - Footer.xlsx';
                $filePath3 = storage_path('app/public/temp/' . $fileName3);
                $writer3 = IOFactory::createWriter($spreadsheet3, 'Xlsx');
                $writer3->save($filePath3);
                $zip->addFile($filePath3, $fileName3);

                $spreadsheet3->disconnectWorksheets();
                unset($spreadsheet3);
            }
            // =============================================================
            // PROSES FILE 4: Warehousing - Master Data - Item - Detail - Header
            // =============================================================
            $templatePath4 = storage_path('app/templates/4. Warehousing - Master Data - Item - Detail - Header.xlsx');
            if (file_exists($templatePath4)) {
                $spreadsheet4 = IOFactory::load($templatePath4);
                $sheet4 = $spreadsheet4->getActiveSheet();

                $highestCol4 = $sheet4->getHighestColumn();
                $headers4 = $sheet4->rangeToArray('A1:' . $highestCol4 . '1', NULL, TRUE, FALSE)[0];

                $getCol4 = function ($headerName) use ($headers4) {
                    $index = array_search($headerName, $headers4);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                $row4 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS DARI WIZARD ---
                    if ($col = $getCol4('Item (child)')) $sheet4->setCellValue($col . $row4, $item->item_code);
                    if ($col = $getCol4('Item (child) (child)')) $sheet4->setCellValue($col . $row4, $item->name);
                    if ($col = $getCol4('Item Group')) $sheet4->setCellValue($col . $row4, $item->group->code ?? '');
                    if ($col = $getCol4('Item Group (child)')) $sheet4->setCellValue($col . $row4, $item->group->description ?? '');

                    // Logika Karakteristik Gudang & Fisik Barang
                    $isHazard = $item->warehouse->hazardous_material ?? false;
                    if ($col = $getCol4('Hazardous Material')) $sheet4->setCellValue($col . $row4, $isHazard ? 'Yes' : 'No');
                    if ($col = $getCol4('Class of Risk')) $sheet4->setCellValue($col . $row4, $isHazard ? ($item->warehouse->class_of_risk ?? '') : '');

                    if ($col = $getCol4('Length')) $sheet4->setCellValue($col . $row4, $item->warehouse->length ?? 0);
                    if ($col = $getCol4('Width')) $sheet4->setCellValue($col . $row4, $item->warehouse->width ?? 0);
                    if ($col = $getCol4('Height')) $sheet4->setCellValue($col . $row4, $item->warehouse->height ?? 0);
                    if ($col = $getCol4('Weight')) $sheet4->setCellValue($col . $row4, $item->warehouse->weight ?? 0);

                    // 1. Array pemetaan kode ke deskripsi gudang (Sesuai dengan HTML Select Anda)
                    $warehouseMapping = [
                        'FG0'   => 'Wh. Finished Goods 0',
                        'FGS'   => 'Wh. Finished Goods Scrapt',
                        'FOSF'  => 'Fiber Optic Shopfloor',
                        'LVSF'  => 'Low Voltage Shopfloor',
                        'MVSF'  => 'Medium Voltage Shopfloor',
                        'WHQ1'  => 'Wh. Quarantine Jembo',
                        'WHRM'  => 'Wh. Raw Material',
                        'WHRM1' => 'Wh. Raw Material 1',
                        'WHRM2' => 'WHRM2 Transit (Peminjaman)',
                        'WHRM3' => 'WHRM3',
                        'WHRM4' => 'WHRM4',
                        'WHSPT' => 'Warehouse Spareparts',
                    ];

                    // 2. Ambil kode dari database, berikan default 'WHSPT' jika kosong
                    $whCode = $item->warehouse->warehouse_code ?? 'WHSPT';

                    // 3. Ambil deskripsinya, jika kodenya tidak terdaftar, gunakan kodenya sendiri
                    $whDesc = $warehouseMapping[$whCode] ?? $whCode;

                    // 4. Set nilainya ke Excel
                    if ($col = $getCol4('Item Valuation Group')) {
                        $sheet4->setCellValue($col . $row4, $whCode);
                    }
                    if ($col = $getCol4('Item Valuation Group (child)')) {
                        $sheet4->setCellValue($col . $row4, $whDesc);
                    }


                    // --- 2. DATA STATIS (Default ERP Sistem) ---
                    if ($col = $getCol4('Company')) $sheet4->setCellValue($col . $row4, '400');
                    if ($col = $getCol4('Item Type')) $sheet4->setCellValue($col . $row4, 'Product');
                    if ($col = $getCol4('Allocation Level')) $sheet4->setCellValue($col . $row4, 'Warehouse');
                    if ($col = $getCol4('Location Controlled')) $sheet4->setCellValue($col . $row4, 'Yes');
                    if ($col = $getCol4('Process Inventory Variances Automatically')) $sheet4->setCellValue($col . $row4, 'Yes');
                    if ($col = $getCol4('Packing Instructions')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Item Text')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Use Item Ordering Data')) $sheet4->setCellValue($col . $row4, 'Yes');
                    if ($col = $getCol4('Default Supply System')) $sheet4->setCellValue($col . $row4, 'None');
                    if ($col = $getCol4('Automatically Release Production Orders')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Generate Order Advice')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Combine Order Advice')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Automatically Confirm Advice')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Registration Level')) $sheet4->setCellValue($col . $row4, 'Warehouse');
                    if ($col = $getCol4('Issue Priority')) $sheet4->setCellValue($col . $row4, 'Owned Inventory First');
                    if ($col = $getCol4('Ownership for Return to Warehouse')) $sheet4->setCellValue($col . $row4, 'Company Owned');
                    if ($col = $getCol4('Exclude from Cycle Counting')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('If Inventory becomes Zero')) $sheet4->setCellValue($col . $row4, 'No Cycle Count');
                    if ($col = $getCol4('Floor Stock')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Inventory Carrying Costs')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('Inventory Carrying Costs (child)')) $sheet4->setCellValue($col . $row4, 'IDR');
                    if ($col = $getCol4('Inventory Inspection')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Allow Stock Point Issue during Inventory Inspection')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Frequency for Inventory Inspection')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('Length (child)')) $sheet4->setCellValue($col . $row4, 'm'); // Default satuan m
                    if ($col = $getCol4('Floor Space')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('Floor Space (child)')) $sheet4->setCellValue($col . $row4, 'm2');
                    if ($col = $getCol4('Volume')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('Volume (child)')) $sheet4->setCellValue($col . $row4, 'm3');
                    if ($col = $getCol4('Slow-Moving Percentage')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('Expected Annual Issue')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('Expected Annual Issue (child)')) $sheet4->setCellValue($col . $row4, 'pcs');
                    if ($col = $getCol4('Forecast Method')) $sheet4->setCellValue($col . $row4, '');
                    if ($col = $getCol4('Manually Entered ABC Code')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Period Length')) $sheet4->setCellValue($col . $row4, 1);
                    if ($col = $getCol4('whwmd400.ptyp')) $sheet4->setCellValue($col . $row4, 'Month');
                    if ($col = $getCol4('whwmd400.ptyp (child)')) $sheet4->setCellValue($col . $row4, 'Not Applicable');
                    if ($col = $getCol4('Shelf Life [Periods]')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('Shelf Life [Periods] (child)')) $sheet4->setCellValue($col . $row4, 'By Location');
                    if ($col = $getCol4('Disposition Due Lead Time')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('whwmd400.tmun')) $sheet4->setCellValue($col . $row4, 'Days');
                    if ($col = $getCol4('whwmd400.tmun (child)')) $sheet4->setCellValue($col . $row4, 'Scrap and/or Quarantine');
                    if ($col = $getCol4('Lot Tracking')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Lots in Inventory')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Default Inventory Lot Size')) $sheet4->setCellValue($col . $row4, 0);
                    if ($col = $getCol4('Lot Price')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Lot Entry for Direct Delivery')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Lot Entry During Receipt')) $sheet4->setCellValue($col . $row4, 'Not Applicable');
                    if ($col = $getCol4('Lot Entry During Transfer')) $sheet4->setCellValue($col . $row4, 'Not Applicable');
                    if ($col = $getCol4('Register Lot Issue During As Built')) $sheet4->setCellValue($col . $row4, 'Not Applicable');
                    if ($col = $getCol4('Register Lot Issue in Service & Maintenance')) $sheet4->setCellValue($col . $row4, 'Not Applicable');
                    if ($col = $getCol4('Print Stock Point Details on Shipment Documents')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Handling Units in Use')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Handling Unit Version Controlled')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Log Version History')) $sheet4->setCellValue($col . $row4, 'No');
                    if ($col = $getCol4('Track Handling Unit Status')) $sheet4->setCellValue($col . $row4, 'No');

                    $row4++;
                }

                $highestRow4 = $sheet4->getHighestRow();
                if ($highestRow4 >= $row4) {
                    $sheet4->removeRow($row4, $highestRow4 - $row4 + 1);
                }

                $fileName4 = '4. Warehousing - Master Data - Item - Detail - Header.xlsx';
                $filePath4 = storage_path('app/public/temp/' . $fileName4);
                $writer4 = IOFactory::createWriter($spreadsheet4, 'Xlsx');
                $writer4->save($filePath4);
                $zip->addFile($filePath4, $fileName4);

                $spreadsheet4->disconnectWorksheets();
                unset($spreadsheet4);
            }
            // =============================================================
            // PROSES FILE 5: Warehousing - Master Data - Item Data By Site
            // =============================================================
            $templatePath5 = storage_path('app/templates/5. Warehousing - Master Data - Item Data By Site.xlsx');
            if (file_exists($templatePath5)) {
                $spreadsheet5 = IOFactory::load($templatePath5);
                $sheet5 = $spreadsheet5->getActiveSheet();

                $highestCol5 = $sheet5->getHighestColumn();
                $headers5 = $sheet5->rangeToArray('A1:' . $highestCol5 . '1', NULL, TRUE, FALSE)[0];

                $getCol5 = function ($headerName) use ($headers5) {
                    $index = array_search($headerName, $headers5);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                // Array pemetaan gudang (sama seperti File 4)
                $warehouseMapping = [
                    'FG0'   => 'Wh. Finished Goods 0',
                    'FGS'   => 'Wh. Finished Goods Scrapt',
                    'FOSF'  => 'Fiber Optic Shopfloor',
                    'LVSF'  => 'Low Voltage Shopfloor',
                    'MVSF'  => 'Medium Voltage Shopfloor',
                    'WHQ1'  => 'Wh. Quarantine Jembo',
                    'WHRM'  => 'Wh. Raw Material',
                    'WHRM1' => 'Wh. Raw Material 1',
                    'WHRM2' => 'WHRM2 Transit (Peminjaman)',
                    'WHRM3' => 'WHRM3',
                    'WHRM4' => 'WHRM4',
                    'WHSPT' => 'Warehouse Spareparts',
                ];

                $row5 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS ---
                    if ($col = $getCol5('Item (child)')) $sheet5->setCellValue($col . $row5, $item->item_code);
                    if ($col = $getCol5('Item (child) (child)')) $sheet5->setCellValue($col . $row5, $item->name);
                    if ($col = $getCol5('Site')) $sheet5->setCellValue($col . $row5, $item->warehouse->site_code ?? 'JCC1');
                    if ($col = $getCol5('Item Group')) $sheet5->setCellValue($col . $row5, $item->group->code ?? '');
                    if ($col = $getCol5('Item Group (child)')) $sheet5->setCellValue($col . $row5, $item->group->description ?? '');

                    // Setup Deskripsi Gudang
                    $whCode = $item->warehouse->warehouse_code ?? 'WHSPT';
                    $whDesc = $warehouseMapping[$whCode] ?? $whCode;

                    if ($col = $getCol5('Item Valuation Group')) $sheet5->setCellValue($col . $row5, $whCode);
                    if ($col = $getCol5('Item Valuation Group (child)')) $sheet5->setCellValue($col . $row5, $whDesc);

                    // Dimensi dan Karakteristik Fisik
                    $isHazard = $item->warehouse->hazardous_material ?? false;
                    if ($col = $getCol5('Hazardous Material')) $sheet5->setCellValue($col . $row5, $isHazard ? 'Yes' : 'No');
                    if ($col = $getCol5('Class of Risk')) $sheet5->setCellValue($col . $row5, $isHazard ? ($item->warehouse->class_of_risk ?? '') : '');
                    if ($col = $getCol5('Length')) $sheet5->setCellValue($col . $row5, $item->warehouse->length ?? 0);
                    if ($col = $getCol5('Width')) $sheet5->setCellValue($col . $row5, $item->warehouse->width ?? 0);
                    if ($col = $getCol5('Height')) $sheet5->setCellValue($col . $row5, $item->warehouse->height ?? 0);
                    if ($col = $getCol5('Weight')) $sheet5->setCellValue($col . $row5, $item->warehouse->weight ?? 0);


                    // --- 2. DATA STATIS BAWAAN ERP ---
                    if ($col = $getCol5('Company')) $sheet5->setCellValue($col . $row5, '400');
                    if ($col = $getCol5('Site (child)')) $sheet5->setCellValue($col . $row5, 'Pt Jembo Factory (Main)');
                    if ($col = $getCol5('Use Global Item Warehousing')) $sheet5->setCellValue($col . $row5, 'Yes');
                    if ($col = $getCol5('Item Type')) $sheet5->setCellValue($col . $row5, 'Product');
                    if ($col = $getCol5('Allocation Level')) $sheet5->setCellValue($col . $row5, 'Warehouse');
                    if ($col = $getCol5('Location Controlled')) $sheet5->setCellValue($col . $row5, 'Yes');
                    if ($col = $getCol5('Process Inventory Variances Automatically')) $sheet5->setCellValue($col . $row5, 'Yes');
                    if ($col = $getCol5('Packing Instructions')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Item Text')) $sheet5->setCellValue($col . $row5, 'No');

                    // Perhatikan perbedaan nama kolom di File 5
                    if ($col = $getCol5('Use Item Ordering Data by Site')) $sheet5->setCellValue($col . $row5, 'Yes');
                    if ($col = $getCol5('Ownership Registration Level')) $sheet5->setCellValue($col . $row5, 'Warehouse');
                    if ($col = $getCol5('Ownership Issue Priority')) $sheet5->setCellValue($col . $row5, 'Owned Inventory First');

                    if ($col = $getCol5('Default Supply System')) $sheet5->setCellValue($col . $row5, 'None');
                    if ($col = $getCol5('Automatically Release Production Orders')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Generate Order Advice')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Combine Order Advice')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Automatically Confirm Advice')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Ownership for Return to Warehouse')) $sheet5->setCellValue($col . $row5, 'Company Owned');
                    if ($col = $getCol5('Exclude from Cycle Counting')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('If Inventory becomes Zero')) $sheet5->setCellValue($col . $row5, 'No Cycle Count');
                    if ($col = $getCol5('Floor Stock')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Inventory Carrying Costs')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('Inventory Carrying Costs (child)')) $sheet5->setCellValue($col . $row5, 'IDR');
                    if ($col = $getCol5('Inventory Inspection')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Allow Stock Point Issue during Inventory Inspection')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Frequency for Inventory Inspection')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('Length (child)')) $sheet5->setCellValue($col . $row5, 'm');
                    if ($col = $getCol5('Floor Space')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('Floor Space (child)')) $sheet5->setCellValue($col . $row5, 'm2');
                    if ($col = $getCol5('Volume')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('Volume (child)')) $sheet5->setCellValue($col . $row5, 'm3');
                    if ($col = $getCol5('Slow-Moving Percentage')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('Expected Annual Issue')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('Expected Annual Issue (child)')) $sheet5->setCellValue($col . $row5, 'pcs');
                    if ($col = $getCol5('Forecast Method')) $sheet5->setCellValue($col . $row5, '');
                    if ($col = $getCol5('Manually Entered ABC Code')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Period Length')) $sheet5->setCellValue($col . $row5, 1);

                    // Parameter teknis File 5 menggunakan whwmd404 (bukan 400)
                    if ($col = $getCol5('whwmd404.ptyp')) $sheet5->setCellValue($col . $row5, 'Month');
                    if ($col = $getCol5('whwmd404.ptyp (child)')) $sheet5->setCellValue($col . $row5, 'Not Applicable');
                    if ($col = $getCol5('Shelf Life [Periods]')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('Shelf Life [Periods] (child)')) $sheet5->setCellValue($col . $row5, 'By Location');
                    if ($col = $getCol5('Disposition Due Lead Time')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('whwmd404.tmun')) $sheet5->setCellValue($col . $row5, 'Days');
                    if ($col = $getCol5('whwmd404.tmun (child)')) $sheet5->setCellValue($col . $row5, 'Scrap and/or Quarantine');

                    if ($col = $getCol5('Lot Tracking')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Lots in Inventory')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Default Inventory Lot Size')) $sheet5->setCellValue($col . $row5, 0);
                    if ($col = $getCol5('Lot Price')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Lot Entry for Direct Delivery')) $sheet5->setCellValue($col . $row5, 'Not Applicable');
                    if ($col = $getCol5('Lot Entry During Receipt')) $sheet5->setCellValue($col . $row5, 'Not Applicable');
                    if ($col = $getCol5('Lot Entry During Transfer')) $sheet5->setCellValue($col . $row5, 'Not Applicable');
                    if ($col = $getCol5('Register Lot Issue During As Built')) $sheet5->setCellValue($col . $row5, 'Not Applicable');
                    if ($col = $getCol5('Register Lot Issue in Service & Maintenance')) $sheet5->setCellValue($col . $row5, 'Not Applicable');
                    if ($col = $getCol5('Handling Units in Use')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Handling Unit Version Controlled')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Log Version History')) $sheet5->setCellValue($col . $row5, 'No');
                    if ($col = $getCol5('Track Handling Unit Status')) $sheet5->setCellValue($col . $row5, 'No');

                    $row5++;
                }

                // Proteksi Ghost Rows
                $highestRow5 = $sheet5->getHighestRow();
                if ($highestRow5 >= $row5) {
                    $sheet5->removeRow($row5, $highestRow5 - $row5 + 1);
                }

                $fileName5 = '5. Warehousing - Master Data - Item Data By Site.xlsx';
                $filePath5 = storage_path('app/public/temp/' . $fileName5);
                $writer5 = IOFactory::createWriter($spreadsheet5, 'Xlsx');
                $writer5->save($filePath5);
                $zip->addFile($filePath5, $fileName5);

                $spreadsheet5->disconnectWorksheets();
                unset($spreadsheet5);
            }
            // =============================================================
            // PROSES FILE 6: Warehousing - Item Data By Warehouse
            // =============================================================
            $templatePath6 = storage_path('app/templates/6. Warehousing - Item Data By Warehouse.xlsx');
            if (file_exists($templatePath6)) {
                $spreadsheet6 = IOFactory::load($templatePath6);
                $sheet6 = $spreadsheet6->getActiveSheet();

                $highestCol6 = $sheet6->getHighestColumn();
                $headers6 = $sheet6->rangeToArray('A1:' . $highestCol6 . '1', NULL, TRUE, FALSE)[0];

                $getCol6 = function ($headerName) use ($headers6) {
                    $index = array_search($headerName, $headers6);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                $row6 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS ---
                    if ($col = $getCol6('Item (child)')) $sheet6->setCellValue($col . $row6, $item->item_code);

                    // Ambil langsung kode gudang yang dipilih (misal: WHSPT)
                    $whCode = $item->warehouse->warehouse_code ?? 'WHSPT';
                    if ($col = $getCol6('Warehouse')) $sheet6->setCellValue($col . $row6, $whCode);

                    if ($col = $getCol6('Item Group')) $sheet6->setCellValue($col . $row6, $item->group->code ?? '');

                    // --- 2. DATA STATIS BAWAAN ERP ---
                    if ($col = $getCol6('Company')) $sheet6->setCellValue($col . $row6, '400');
                    if ($col = $getCol6('Status')) $sheet6->setCellValue($col . $row6, 'Active');
                    if ($col = $getCol6('Use Item Ordering Data by Site')) $sheet6->setCellValue($col . $row6, 'Yes');
                    if ($col = $getCol6('Inventory Valuation Method')) $sheet6->setCellValue($col . $row6, 'Standard Cost');
                    if ($col = $getCol6('Order System')) $sheet6->setCellValue($col . $row6, 'Planned');
                    if ($col = $getCol6('Order Method')) $sheet6->setCellValue($col . $row6, 'Lot for Lot');
                    if ($col = $getCol6('Supply System')) $sheet6->setCellValue($col . $row6, 'None');
                    if ($col = $getCol6('Handling Units in Use')) $sheet6->setCellValue($col . $row6, 'No');
                    if ($col = $getCol6('Text')) $sheet6->setCellValue($col . $row6, 'No');

                    $row6++;
                }

                // Proteksi dari Ghost Rows
                $highestRow6 = $sheet6->getHighestRow();
                if ($highestRow6 >= $row6) {
                    $sheet6->removeRow($row6, $highestRow6 - $row6 + 1);
                }

                // Simpan & Masukkan ke ZIP
                $fileName6 = '6. Warehousing - Item Data By Warehouse.xlsx';
                $filePath6 = storage_path('app/public/temp/' . $fileName6);
                $writer6 = IOFactory::createWriter($spreadsheet6, 'Xlsx');
                $writer6->save($filePath6);
                $zip->addFile($filePath6, $fileName6);

                $spreadsheet6->disconnectWorksheets();
                unset($spreadsheet6);
            }
            // =============================================================
            // PROSES FILE 7: Warehousing - Item Data By Warehouse - Detail
            // =============================================================
            $templatePath7 = storage_path('app/templates/7. Warehousing - Item Data By Warehouse - Detail.xlsx');
            if (file_exists($templatePath7)) {
                $spreadsheet7 = IOFactory::load($templatePath7);
                $sheet7 = $spreadsheet7->getActiveSheet();

                $highestCol7 = $sheet7->getHighestColumn();
                $headers7 = $sheet7->rangeToArray('A1:' . $highestCol7 . '1', NULL, TRUE, FALSE)[0];

                // 1. Fungsi Pencari Kolom Anti-Spasi (Kebal Typo)
                $getCol7 = function ($headerName) use ($headers7) {
                    $targetName = strtolower(trim($headerName));
                    foreach ($headers7 as $index => $actualHeaderName) {
                        if (strtolower(trim((string)$actualHeaderName)) === $targetName) {
                            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return null;
                };

                // Fungsi jamak untuk kolom bernama sama (contoh: Use Item Ordering Data by Site)
                $getCols7 = function ($headerName) use ($headers7) {
                    $cols = [];
                    $targetName = strtolower(trim($headerName));
                    foreach ($headers7 as $index => $actualHeaderName) {
                        if (strtolower(trim((string)$actualHeaderName)) === $targetName) {
                            $cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return $cols;
                };

                $row7 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS ---

                    // PENYESUAIAN TEMPLATE BARU: Item (child) untuk Kode, Item (child) (child) untuk Nama
                    if ($col = $getCol7('Item (child)')) $sheet7->setCellValue($col . $row7, $item->item_code);
                    if ($col = $getCol7('Item (child) (child)')) $sheet7->setCellValue($col . $row7, $item->name);

                    $whCode = $item->warehouse->warehouse_code ?? 'WHSPT';
                    // (Opsional: Jika ada mapping deskripsi gudang)
                    $whDesc = $whCode === 'WHSPT' ? 'Warehouse Sparepart' : $whCode;

                    if ($col = $getCol7('Warehouse')) $sheet7->setCellValue($col . $row7, $whCode);
                    if ($col = $getCol7('Warehouse (child)')) $sheet7->setCellValue($col . $row7, $whDesc);

                    // Map Ordering Rules
                    if ($col = $getCol7('Minimum Order Quantity')) $sheet7->setCellValue($col . $row7, $item->orderRule->min_order_qty ?? 0);
                    if ($col = $getCol7('Maximum Order Quantity')) $sheet7->setCellValue($col . $row7, $item->orderRule->max_order_qty ?? 99999999);

                    $itemUnit = strtolower($item->unit ?? 'pcs'); // Ubah satuan ke huruf kecil sesuai template (misal: 'm' atau 'pcs')

                    // --- 2. DATA STATIS BAWAAN ERP ---
                    if ($col = $getCol7('Company')) $sheet7->setCellValue($col . $row7, '400'); // Ubah ke 888 jika memang harus 888
                    if ($col = $getCol7('Item Warehouse Status')) $sheet7->setCellValue($col . $row7, 'Active');
                    if ($col = $getCol7('Default Location Type for Inbound Advice')) $sheet7->setCellValue($col . $row7, 'Not Applicable');

                    // --- TAMBAHAN BARU: Kolom Biaya & Teks yang sebelumnya kosong ---
                    if ($col = $getCol7('Order Costs')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('Order Costs (child)')) $sheet7->setCellValue($col . $row7, 'IDR');
                    if ($col = $getCol7('Inventory Carrying Costs per Year')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('Text')) $sheet7->setCellValue($col . $row7, 'No');

                    if ($col = $getCol7('Allocation Level')) $sheet7->setCellValue($col . $row7, 'Warehouse');
                    if ($col = $getCol7('Net Change')) $sheet7->setCellValue($col . $row7, 'Yes');
                    if ($col = $getCol7('Registration Level')) $sheet7->setCellValue($col . $row7, 'Warehouse');
                    if ($col = $getCol7('Issue Priority')) $sheet7->setCellValue($col . $row7, 'Owned Inventory First');
                    if ($col = $getCol7('Ownership for Return to Warehouse')) $sheet7->setCellValue($col . $row7, 'Company Owned');
                    if ($col = $getCol7('Usage at Warehouse Transfer')) $sheet7->setCellValue($col . $row7, 'Always');
                    if ($col = $getCol7('Inventory Inspection')) $sheet7->setCellValue($col . $row7, 'Yes');
                    if ($col = $getCol7('Frequency for Inventory Inspection')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('Allow Stock Point Issue during Inventory Inspection')) $sheet7->setCellValue($col . $row7, 'No');
                    if ($col = $getCol7('Service Level')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('Season Pattern Safety Stock (child) (child)')) $sheet7->setCellValue($col . $row7, 'Standard Cost');
                    if ($col = $getCol7('Valuation by Warehouse Valuation Group')) $sheet7->setCellValue($col . $row7, 'No');
                    if ($col = $getCol7('Supply System')) $sheet7->setCellValue($col . $row7, 'None');
                    if ($col = $getCol7('Supply from Warehouse')) $sheet7->setCellValue($col . $row7, 'No');
                    if ($col = $getCol7('Supply Company')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('Automatically Release Production Orders')) $sheet7->setCellValue($col . $row7, 'No');
                    if ($col = $getCol7('Generate Order Advice')) $sheet7->setCellValue($col . $row7, 'No');
                    if ($col = $getCol7('Combine Order Advice')) $sheet7->setCellValue($col . $row7, 'No');
                    if ($col = $getCol7('Automatically Confirm Advice')) $sheet7->setCellValue($col . $row7, 'No');
                    if ($col = $getCol7('Horizon for Historical Demand')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('Horizon for Future Demand')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('Offset Date')) $sheet7->setCellValue($col . $row7, 'No');


                    // --- PERBAIKAN: Ubah menjadi 'No' sesuai template asli ---
                    foreach ($getCols7('Use Item Ordering Data by Site') as $col) {
                        $sheet7->setCellValue($col . $row7, 'No');
                    }

                    if ($col = $getCol7('Update Inventory/Order Data')) $sheet7->setCellValue($col . $row7, 'No');
                    if ($col = $getCol7('Exclude from Cycle Counting')) $sheet7->setCellValue($col . $row7, 'No');

                    // --- PERBAIKAN: Ubah menjadi 'Yes' sesuai template asli ---
                    if ($col = $getCol7('Process Inventory Variances Automatically')) $sheet7->setCellValue($col . $row7, 'Yes');
                    if ($col = $getCol7('QM Overrules Warehouse Inbound Order Type')) $sheet7->setCellValue($col . $row7, 'Yes');

                    // --- TAMBAHAN BARU: Order interval yang sebelumnya kosong ---
                    if ($col = $getCol7('Order Interval')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('whwmd210.oivu')) $sheet7->setCellValue($col . $row7, 'Days');

                    // --- 3. PARSING TANGGAL (Tetap pertahankan yang ini) ---
                    $lastAllowedDate = \Carbon\Carbon::create(9999, 12, 31, 7, 0, 0);
                    $excelLastAllowedDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($lastAllowedDate);

                    if ($col = $getCol7('Last Allowed Order Date')) {
                        $sheet7->setCellValue($col . $row7, $excelLastAllowedDate);
                        $sheet7->getStyle($col . $row7)->getNumberFormat()->setFormatCode('m/d/yyyy');
                    }
                    if ($col = $getCol7('Last Allowed Order Date (child)')) {
                        $sheet7->setCellValue($col . $row7, $excelLastAllowedDate);
                        $sheet7->getStyle($col . $row7)->getNumberFormat()->setFormatCode('h:mm:ss');
                    }

                    // Lead times
                    if ($col = $getCol7('Inbound')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('whwmd210.iltu')) $sheet7->setCellValue($col . $row7, 'Hours');
                    if ($col = $getCol7('Outbound')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('whwmd210.oltu')) $sheet7->setCellValue($col . $row7, 'Hours');
                    if ($col = $getCol7('Cross-dock')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('whwmd210.cdlu')) $sheet7->setCellValue($col . $row7, 'Hours');
                    if ($col = $getCol7('Safety')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('whwmd210.sftu')) $sheet7->setCellValue($col . $row7, 'Hours');

                    // Forecast & Issue
                    if ($col = $getCol7('Expected Annual Issue')) $sheet7->setCellValue($col . $row7, 0);
                    if ($col = $getCol7('Expected Annual Issue (child)')) $sheet7->setCellValue($col . $row7, $itemUnit);

                    if ($col = $getCol7('Period')) $sheet7->setCellValue($col . $row7, 1);
                    if ($col = $getCol7('whwmd210.ptyp')) $sheet7->setCellValue($col . $row7, 'Month');

                    if ($col = $getCol7('Order System')) $sheet7->setCellValue($col . $row7, 'Planned');
                    if ($col = $getCol7('Order Method')) $sheet7->setCellValue($col . $row7, 'Lot for Lot');
                    if ($col = $getCol7('Order Quantity Increment')) $sheet7->setCellValue($col . $row7, 1);
                    if ($col = $getCol7('Order Quantity Increment (child)')) $sheet7->setCellValue($col . $row7, $itemUnit);

                    // Isi child untuk min/max order quantity menyesuaikan satuan
                    if ($col = $getCol7('Minimum Order Quantity (child)')) $sheet7->setCellValue($col . $row7, $itemUnit);
                    if ($col = $getCol7('Minimum Order Quantity (child) (child)')) $sheet7->setCellValue($col . $row7, 1);
                    if ($col = $getCol7('Maximum Order Quantity (child)')) $sheet7->setCellValue($col . $row7, $itemUnit);

                    $row7++;
                }

                // Proteksi Ghost Rows
                $highestRow7 = $sheet7->getHighestRow();
                if ($highestRow7 >= $row7) {
                    $sheet7->removeRow($row7, $highestRow7 - $row7 + 1);
                }

                $fileName7 = '7. Warehousing - Item Data By Warehouse - Detail.xlsx';
                $filePath7 = storage_path('app/public/temp/' . $fileName7);
                $writer7 = IOFactory::createWriter($spreadsheet7, 'Xlsx');
                $writer7->save($filePath7);
                $zip->addFile($filePath7, $fileName7);

                $spreadsheet7->disconnectWorksheets();
                unset($spreadsheet7);
            }
            // =============================================================
            // PROSES FILE 8: Procurement - Master Data - Item - Item Purchase - Header
            // =============================================================
            $templatePath8 = storage_path('app/templates/8. Procurement - Master Data - Item - Item Purchase - Header.xlsx');
            if (file_exists($templatePath8)) {
                $spreadsheet8 = IOFactory::load($templatePath8);
                $sheet8 = $spreadsheet8->getActiveSheet();

                $highestCol8 = $sheet8->getHighestColumn();
                $headers8 = $sheet8->rangeToArray('A1:' . $highestCol8 . '1', NULL, TRUE, FALSE)[0];

                // Fungsi tunggal
                $getCol8 = function ($headerName) use ($headers8) {
                    $index = array_search($headerName, $headers8);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                // Fungsi jamak untuk menangani header dengan nama yang sama persis
                $getCols8 = function ($headerName) use ($headers8) {
                    $cols = [];
                    foreach ($headers8 as $index => $name) {
                        if ($name === $headerName) {
                            $cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return $cols;
                };

                $row8 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS DARI WIZARD ---
                    if ($col = $getCol8('Item (child)')) $sheet8->setCellValue($col . $row8, $item->item_code);

                    // Dari Tahap 3: Procurement
                    if ($col = $getCol8('Buy-from BP')) $sheet8->setCellValue($col . $row8, $item->procurement->buy_from_bp ?? '');
                    if ($col = $getCol8('Purchase Unit')) $sheet8->setCellValue($col . $row8, $item->unit ?? '');
                    if ($col = $getCol8('Currency')) $sheet8->setCellValue($col . $row8, $item->procurement->currency ?? 'IDR');
                    if ($col = $getCol8('Purchase Price Unit')) $sheet8->setCellValue($col . $row8, $item->unit ?? '');
                    if ($col = $getCol8('Purchase Price')) $sheet8->setCellValue($col . $row8, $item->procurement->standard_cost ?? 0);
                    if ($col = $getCol8('Tax Code')) $sheet8->setCellValue($col . $row8, $item->procurement->tax_code ?? '');

                    // --- 2. DATA STATIS BAWAAN ERP ---
                    if ($col = $getCol8('Company')) $sheet8->setCellValue($col . $row8, '400');
                    if ($col = $getCol8('Item Type')) $sheet8->setCellValue($col . $row8, 'Product');
                    if ($col = $getCol8('Purchase Stat. Group')) $sheet8->setCellValue($col . $row8, 'PSG');
                    if ($col = $getCol8('Purchase Office')) $sheet8->setCellValue($col . $row8, '400');
                    if ($col = $getCol8('Purchase Price Group')) $sheet8->setCellValue($col . $row8, 'PPG');
                    if ($col = $getCol8('Invoice by Stage Payments')) $sheet8->setCellValue($col . $row8, 'No');
                    if ($col = $getCol8('VAT Based on')) $sheet8->setCellValue($col . $row8, 'Goods');

                    // Penanganan kolom duplikat (menggunakan array index dari fungsi jamak)
                    $sourceOfPriceCols = $getCols8('Source of Price');
                    if (isset($sourceOfPriceCols[0])) $sheet8->setCellValue($sourceOfPriceCols[0] . $row8, 'Subcontracting Rate');
                    if (isset($sourceOfPriceCols[1])) $sheet8->setCellValue($sourceOfPriceCols[1] . $row8, 'Reference Activity');

                    foreach ($getCols8('Subcontracting Purchase Price') as $col) {
                        $sheet8->setCellValue($col . $row8, 0);
                    }
                    foreach ($getCols8('Price in Home Currency') as $col) {
                        $sheet8->setCellValue($col . $row8, 0);
                    }
                    foreach ($getCols8('Requisition Mandatory') as $col) {
                        $sheet8->setCellValue($col . $row8, 'No');
                    }

                    // Toleransi dan Parameter Teknis Lainnya
                    if ($col = $getCol8('Date Tolerance (-)')) $sheet8->setCellValue($col . $row8, 0);
                    if ($col = $getCol8('Date Tolerance (+)')) $sheet8->setCellValue($col . $row8, 0);
                    if ($col = $getCol8('Hard Stop on Date')) $sheet8->setCellValue($col . $row8, 'Warn');
                    if ($col = $getCol8('Quantity Tolerance (-)')) $sheet8->setCellValue($col . $row8, 0);
                    if ($col = $getCol8('Quantity Tolerance (+)')) $sheet8->setCellValue($col . $row8, 0);
                    if ($col = $getCol8('Hard Stop on Quantity')) $sheet8->setCellValue($col . $row8, 'Warn');
                    if ($col = $getCol8('Supply Time')) $sheet8->setCellValue($col . $row8, 0);
                    if ($col = $getCol8('tdipu001.sutu')) $sheet8->setCellValue($col . $row8, 'Days');
                    if ($col = $getCol8('Release to Warehousing')) $sheet8->setCellValue($col . $row8, 'Yes');
                    if ($col = $getCol8('Inspection')) $sheet8->setCellValue($col . $row8, 'No');
                    if ($col = $getCol8('Accessories Allowed')) $sheet8->setCellValue($col . $row8, 'No');
                    if ($col = $getCol8('Deliver by Specified Suppliers Only')) $sheet8->setCellValue($col . $row8, 'No');
                    if ($col = $getCol8('Specify Cost Optionally')) $sheet8->setCellValue($col . $row8, 'No');
                    if ($col = $getCol8('Purchase Text')) $sheet8->setCellValue($col . $row8, 'No');

                    $row8++;
                }

                // Proteksi dari Ghost Rows
                $highestRow8 = $sheet8->getHighestRow();
                if ($highestRow8 >= $row8) {
                    $sheet8->removeRow($row8, $highestRow8 - $row8 + 1);
                }

                $fileName8 = '8. Procurement - Master Data - Item - Item Purchase - Header.xlsx';
                $filePath8 = storage_path('app/public/temp/' . $fileName8);
                $writer8 = IOFactory::createWriter($spreadsheet8, 'Xlsx');
                $writer8->save($filePath8);
                $zip->addFile($filePath8, $fileName8);

                $spreadsheet8->disconnectWorksheets();
                unset($spreadsheet8);
            }
            // =============================================================
            // PROSES FILE 9: Procurement - Master Data - Item - Item Purchase - Footer
            // =============================================================
            $templatePath9 = storage_path('app/templates/9. Procurement - Master Data - Item - Item Purchase - Footer.xlsx');
            if (file_exists($templatePath9)) {
                $spreadsheet9 = IOFactory::load($templatePath9);
                $sheet9 = $spreadsheet9->getActiveSheet();

                $highestCol9 = $sheet9->getHighestColumn();
                $headers9 = $sheet9->rangeToArray('A1:' . $highestCol9 . '1', NULL, TRUE, FALSE)[0];

                // Fungsi tunggal
                $getCol9 = function ($headerName) use ($headers9) {
                    $index = array_search($headerName, $headers9);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                // Fungsi jamak untuk mengatasi duplikat header seperti 'Company'
                $getCols9 = function ($headerName) use ($headers9) {
                    $cols = [];
                    foreach ($headers9 as $index => $name) {
                        if ($name === $headerName) {
                            $cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return $cols;
                };

                $row9 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS ---
                    if ($col = $getCol9('Item (child)')) $sheet9->setCellValue($col . $row9, $item->item_code);
                    if ($col = $getCol9('Site')) $sheet9->setCellValue($col . $row9, $item->warehouse->site_code ?? 'JCC1');

                    if ($col = $getCol9('Buy-from Business Partner')) $sheet9->setCellValue($col . $row9, $item->procurement->buy_from_bp ?? '');
                    if ($col = $getCol9('Purchase Price')) $sheet9->setCellValue($col . $row9, $item->procurement->standard_cost ?? 0);
                    if ($col = $getCol9('Purchase Price (child)')) $sheet9->setCellValue($col . $row9, $item->procurement->currency ?? 'IDR');

                    // --- 2. DATA STATIS BAWAAN ERP ---
                    foreach ($getCols9('Company') as $col) {
                        $sheet9->setCellValue($col . $row9, '400');
                    }
                    if ($col = $getCol9('Site (child)')) $sheet9->setCellValue($col . $row9, 'Pt Jembo Factory (Main)');
                    if ($col = $getCol9('Use Global Item Purchase')) $sheet9->setCellValue($col . $row9, 'Yes');

                    $row9++;
                }

                // Proteksi dari Ghost Rows
                $highestRow9 = $sheet9->getHighestRow();
                if ($highestRow9 >= $row9) {
                    $sheet9->removeRow($row9, $highestRow9 - $row9 + 1);
                }

                // Simpan & Masukkan ke ZIP
                $fileName9 = '9. Procurement - Master Data - Item - Item Purchase - Footer.xlsx';
                $filePath9 = storage_path('app/public/temp/' . $fileName9);
                $writer9 = IOFactory::createWriter($spreadsheet9, 'Xlsx');
                $writer9->save($filePath9);
                $zip->addFile($filePath9, $fileName9);

                $spreadsheet9->disconnectWorksheets();
                unset($spreadsheet9);
            }
            // =============================================================
            // PROSES FILE 10: Service - Master Data - Item Service
            // =============================================================
            $templatePath10 = storage_path('app/templates/10. Service - Master Data - Item Service.xlsx');
            if (file_exists($templatePath10)) {
                $spreadsheet10 = IOFactory::load($templatePath10);
                $sheet10 = $spreadsheet10->getActiveSheet();

                $highestCol10 = $sheet10->getHighestColumn();
                $headers10 = $sheet10->rangeToArray('A1:' . $highestCol10 . '1', NULL, TRUE, FALSE)[0];

                $getCol10 = function ($headerName) use ($headers10) {
                    $index = array_search($headerName, $headers10);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                $row10 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS DARI WIZARD ---
                    if ($col = $getCol10('Item (child)')) $sheet10->setCellValue($col . $row10, $item->item_code);

                    // Logika Configuration Controlled (Berdasarkan status Serialization Tahap 5)
                    $isSerialized = $item->serialization->is_serialized ?? false;
                    if ($col = $getCol10('Configuration Controlled')) {
                        $sheet10->setCellValue($col . $row10, $isSerialized ? 'Serialized' : 'Anonymous');
                    }

                    if ($col = $getCol10('Currency')) $sheet10->setCellValue($col . $row10, $item->procurement->currency ?? 'IDR');

                    // PERBAIKAN: Penambahan Kolom Satuan Secara Dinamis
                    $itemUnit = $item->unit ?? 'pcs';
                    if ($col = $getCol10('Sales Unit')) $sheet10->setCellValue($col . $row10, $itemUnit);
                    if ($col = $getCol10('Sales Price Unit')) $sheet10->setCellValue($col . $row10, $itemUnit);


                    // --- 2. DATA STATIS BAWAAN ERP ---
                    if ($col = $getCol10('Company')) $sheet10->setCellValue($col . $row10, '400');
                    if ($col = $getCol10('Text')) $sheet10->setCellValue($col . $row10, 'No');
                    if ($col = $getCol10('Item Type')) $sheet10->setCellValue($col . $row10, 'Product');
                    if ($col = $getCol10('Logistic Company')) $sheet10->setCellValue($col . $row10, '400');
                    if ($col = $getCol10('Logistic Company (child)')) $sheet10->setCellValue($col . $row10, 'PT. JEMBO CABLE COMPANY TBK');

                    if ($col = $getCol10('Repairable')) $sheet10->setCellValue($col . $row10, 'No');
                    if ($col = $getCol10('Ownership')) $sheet10->setCellValue($col . $row10, 'Company Owned');
                    if ($col = $getCol10('Critical for Inventory Check')) $sheet10->setCellValue($col . $row10, 'No');
                    if ($col = $getCol10('Process to Service after Delivery')) $sheet10->setCellValue($col . $row10, 'No');
                    if ($col = $getCol10('Service Item Group')) $sheet10->setCellValue($col . $row10, 'SIG');
                    if ($col = $getCol10('Service Item Group (child)')) $sheet10->setCellValue($col . $row10, 'Service Item Group');

                    if ($col = $getCol10('Delivery Type')) $sheet10->setCellValue($col . $row10, 'From Warehouse');
                    if ($col = $getCol10('Use Residual Value')) $sheet10->setCellValue($col . $row10, 'No');
                    if ($col = $getCol10('Return unconsumed Items to Warehouse')) $sheet10->setCellValue($col . $row10, 'No');
                    if ($col = $getCol10('Quote Mandatory before Releasing Work Order')) $sheet10->setCellValue($col . $row10, 'No');

                    if ($col = $getCol10('Currency (child)')) $sheet10->setCellValue($col . $row10, 'Indonesia Rupiah');
                    if ($col = $getCol10('Sales Price')) $sheet10->setCellValue($col . $row10, 0);

                    if ($col = $getCol10('Serialized Item Warranty Terms')) $sheet10->setCellValue($col . $row10, 'No');
                    if ($col = $getCol10('Covered by Contract')) $sheet10->setCellValue($col . $row10, 'Covered');
                    if ($col = $getCol10('Repair Warranty Duration')) $sheet10->setCellValue($col . $row10, 0);
                    if ($col = $getCol10('Repair Warranty Duration (child)')) $sheet10->setCellValue($col . $row10, 'Day');
                    if ($col = $getCol10('Generate Planned Activities')) $sheet10->setCellValue($col . $row10, 'No');
                    if ($col = $getCol10('Life Cycle')) $sheet10->setCellValue($col . $row10, 0);

                    $row10++;
                }

                // Proteksi dari Ghost Rows
                $highestRow10 = $sheet10->getHighestRow();
                if ($highestRow10 >= $row10) {
                    $sheet10->removeRow($row10, $highestRow10 - $row10 + 1);
                }

                $fileName10 = '10. Service - Master Data - Item Service.xlsx';
                $filePath10 = storage_path('app/public/temp/' . $fileName10);
                $writer10 = IOFactory::createWriter($spreadsheet10, 'Xlsx');
                $writer10->save($filePath10);
                $zip->addFile($filePath10, $fileName10);

                $spreadsheet10->disconnectWorksheets();
                unset($spreadsheet10);
            }
            // =============================================================
            // PROSES FILE 11: Master Data - Item - Miscellaneous - Selliarized Item
            // =============================================================
            $templatePath11 = storage_path('app/templates/11. Master Data - Item - Miscellaneous - Selliarized Item.xlsx');
            if (file_exists($templatePath11)) {
                $spreadsheet11 = IOFactory::load($templatePath11);
                $sheet11 = $spreadsheet11->getActiveSheet();

                $highestCol11 = $sheet11->getHighestColumn();
                $headers11 = $sheet11->rangeToArray('A1:' . $highestCol11 . '1', NULL, TRUE, FALSE)[0];

                // 1. FUNGSI PENCARI KOLOM KEBAL SPASI & HURUF BESAR/KECIL
                $getCol11 = function ($headerName) use ($headers11) {
                    $targetName = strtolower(trim($headerName));
                    foreach ($headers11 as $index => $actualHeaderName) {
                        if (strtolower(trim((string)$actualHeaderName)) === $targetName) {
                            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return null;
                };

                $row11 = 2;
                $hasSerializedData = false;

                foreach ($items as $item) {
                    $isSerialized = $item->serialization->is_serialized ?? false;
                    if (!$isSerialized) {
                        continue;
                    }

                    $hasSerializedData = true;

                    // --- 1. DATA DINAMIS ---
                    if ($col = $getCol11('Item')) $sheet11->setCellValue($col . $row11, $item->item_code);
                    if ($col = $getCol11('Item (child)')) $sheet11->setCellValue($col . $row11, $item->name);

                    $serialNo = $item->serial_number ?? '';
                    if ($col = $getCol11('Serial Number')) $sheet11->setCellValue($col . $row11, $serialNo);

                    if ($col = $getCol11('Description')) $sheet11->setCellValue($col . $row11, $item->name);
                    if ($col = $getCol11('Search Argument')) $sheet11->setCellValue($col . $row11, $item->name);

                    // 2. PARSING TANGGAL AMAN (Mencegah error jika data terbaca sebagai string)
                    $exportTime = now();

                    // PHPToExcel akan mengubah waktu sekarang menjadi angka desimal (Contoh: 46162.473)
                    // Angka inilah yang sebenarnya dicari oleh sistem ERP (Java POI)
                    $excelDateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($exportTime);

                    if ($col = $getCol11('Creation Time')) {
                        // 1. Masukkan nilai angka murninya
                        $sheet11->setCellValue($col . $row11, $excelDateTime);
                        // 2. Format tampilannya menjadi m/d/yyyy (tanpa angka 0 di depan bulan/hari)
                        $sheet11->getStyle($col . $row11)->getNumberFormat()->setFormatCode('m/d/yyyy');
                    }

                    if ($col = $getCol11('Creation Time (child)')) {
                        // 1. Masukkan nilai angka murninya
                        $sheet11->setCellValue($col . $row11, $excelDateTime);
                        // 2. Format tampilannya menjadi h:mm:ss (tanpa angka 0 di depan jam)
                        $sheet11->getStyle($col . $row11)->getNumberFormat()->setFormatCode('h:mm:ss');
                    }

                    // --- 2. DATA STATIS ---
                    if ($col = $getCol11('Company')) $sheet11->setCellValue($col . $row11, '400');
                    if ($col = $getCol11('Track GPS Location')) $sheet11->setCellValue($col . $row11, 'No');

                    $row11++;
                }
                // Hanya masukkan file ke dalam ZIP jika ada minimal 1 barang berstatus Serialized
                if ($hasSerializedData) {
                    // Proteksi Ghost Rows
                    $highestRow11 = $sheet11->getHighestRow();
                    if ($highestRow11 >= $row11) {
                        $sheet11->removeRow($row11, $highestRow11 - $row11 + 1);
                    }

                    $fileName11 = '11. Master Data - Item - Miscellaneous - Selliarized Item.xlsx';
                    $filePath11 = storage_path('app/public/temp/' . $fileName11);
                    $writer11 = IOFactory::createWriter($spreadsheet11, 'Xlsx');
                    $writer11->save($filePath11);
                    $zip->addFile($filePath11, $fileName11);
                }

                $spreadsheet11->disconnectWorksheets();
                unset($spreadsheet11);
            }
            // =============================================================
            // PROSES FILE 12: Common - Standard Cost - Master Data - Item Costing
            // =============================================================
            $templatePath12 = storage_path('app/templates/12. Common - Standard Cost - Master Data - Item Costing.xlsx');
            if (file_exists($templatePath12)) {
                $spreadsheet12 = IOFactory::load($templatePath12);
                $sheet12 = $spreadsheet12->getActiveSheet();

                $highestCol12 = $sheet12->getHighestColumn();
                $headers12 = $sheet12->rangeToArray('A1:' . $highestCol12 . '1', NULL, TRUE, FALSE)[0];

                // Fungsi pencarian kolom tunggal
                $getCol12 = function ($headerName) use ($headers12) {
                    $index = array_search($headerName, $headers12);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                // Fungsi pencarian kolom jamak (untuk duplikat seperti Currency (child))
                $getCols12 = function ($headerName) use ($headers12) {
                    $cols = [];
                    foreach ($headers12 as $index => $name) {
                        if ($name === $headerName) {
                            $cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return $cols;
                };

                // Mapping deskripsi gudang (sama seperti sebelumnya)
                $warehouseMapping = [
                    'FG0'   => 'Wh. Finished Goods 0',
                    'FGS'   => 'Wh. Finished Goods Scrapt',
                    'FOSF'  => 'Fiber Optic Shopfloor',
                    'LVSF'  => 'Low Voltage Shopfloor',
                    'MVSF'  => 'Medium Voltage Shopfloor',
                    'WHQ1'  => 'Wh. Quarantine Jembo',
                    'WHRM'  => 'Wh. Raw Material',
                    'WHRM1' => 'Wh. Raw Material 1',
                    'WHRM2' => 'WHRM2 Transit (Peminjaman)',
                    'WHRM3' => 'WHRM3',
                    'WHRM4' => 'WHRM4',
                    'WHSPT' => 'Warehouse Spareparts',
                ];

                $row12 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS ---
                    if ($col = $getCol12('Item (child)')) $sheet12->setCellValue($col . $row12, $item->item_code);

                    // Kita juga mengisi Item (child) (child) jika ada di template, Infor LN terkadang menggunakannya untuk alias
                    $itemChildCols = $getCols12('Item (child) (child)');
                    if (!empty($itemChildCols)) {
                        $sheet12->setCellValue($itemChildCols[0] . $row12, $item->name);
                    }

                    // Penentuan Gudang
                    $whCode = $item->warehouse->warehouse_code ?? 'WHSPT';
                    $whDesc = $warehouseMapping[$whCode] ?? $whCode;

                    if ($col = $getCol12('Warehouse')) $sheet12->setCellValue($col . $row12, $whCode);
                    if ($col = $getCol12('Warehouse (child)')) $sheet12->setCellValue($col . $row12, $whDesc);

                    if ($col = $getCol12('Currency')) $sheet12->setCellValue($col . $row12, $item->procurement->currency ?? 'IDR');

                    // --- 2. DATA STATIS BAWAAN ERP ---
                    if ($col = $getCol12('Company')) $sheet12->setCellValue($col . $row12, '400');
                    if ($col = $getCol12('Enterprise Unit')) $sheet12->setCellValue($col . $row12, 'JEC001');
                    if ($col = $getCol12('Enterprise Unit (child)')) $sheet12->setCellValue($col . $row12, 'PT. Jembo Cable Company Tbk.');

                    if ($col = $getCol12('Costing Type')) $sheet12->setCellValue($col . $row12, 'Logistics');
                    if ($col = $getCol12('Standard Costs Base')) $sheet12->setCellValue($col . $row12, 'Yes');
                    if ($col = $getCol12('Costing Source')) $sheet12->setCellValue($col . $row12, 'Purchase');

                    if ($col = $getCol12('Scheme')) $sheet12->setCellValue($col . $row12, 'M00001');
                    if ($col = $getCol12('Scheme (child)')) $sheet12->setCellValue($col . $row12, 'Material');

                    // Atribut Cost Component Statis
                    $currencyChildCols = $getCols12('Currency (child)');
                    if (isset($currencyChildCols[0])) $sheet12->setCellValue($currencyChildCols[0] . $row12, 'Indonesia Rupiah');

                    $currencyChildChildCols = $getCols12('Currency (child) (child)');
                    if (isset($currencyChildChildCols[0])) $sheet12->setCellValue($currencyChildChildCols[0] . $row12, 'MMA00001');

                    // Setting Default Yes/No untuk perhitungan kalkulasi otomatis di Infor LN
                    if ($col = $getCol12('By Item')) $sheet12->setCellValue($col . $row12, 'Yes');
                    if ($col = $getCol12('By Warehouse')) $sheet12->setCellValue($col . $row12, 'Yes');
                    if ($col = $getCol12('Include Landed Costs')) $sheet12->setCellValue($col . $row12, 'Yes');

                    $row12++;
                }

                // Proteksi dari Ghost Rows
                $highestRow12 = $sheet12->getHighestRow();
                if ($highestRow12 >= $row12) {
                    $sheet12->removeRow($row12, $highestRow12 - $row12 + 1);
                }

                $fileName12 = '12. Common - Standard Cost - Master Data - Item Costing.xlsx';
                $filePath12 = storage_path('app/public/temp/' . $fileName12);
                $writer12 = IOFactory::createWriter($spreadsheet12, 'Xlsx');
                $writer12->save($filePath12);
                $zip->addFile($filePath12, $fileName12);

                $spreadsheet12->disconnectWorksheets();
                unset($spreadsheet12);
            }
            // =============================================================
            // PROSES FILE 13: Master Data - Item - Miscellaneous - Item Ordering - Footer
            // =============================================================
            $templatePath13 = storage_path('app/templates/13. Master Data - Item - Miscellaneous - Item Ordering - Footer.xlsx');
            if (file_exists($templatePath13)) {
                $spreadsheet13 = IOFactory::load($templatePath13);
                $sheet13 = $spreadsheet13->getActiveSheet();

                $highestCol13 = $sheet13->getHighestColumn();
                $headers13 = $sheet13->rangeToArray('A1:' . $highestCol13 . '1', NULL, TRUE, FALSE)[0];

                // Fungsi pencarian kolom tunggal
                $getCol13 = function ($headerName) use ($headers13) {
                    $index = array_search($headerName, $headers13);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                // Fungsi pencarian kolom jamak (untuk duplikat seperti Company)
                $getCols13 = function ($headerName) use ($headers13) {
                    $cols = [];
                    foreach ($headers13 as $index => $name) {
                        if ($name === $headerName) {
                            $cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return $cols;
                };

                // Mapping deskripsi gudang
                $warehouseMapping = [
                    'FG0'   => 'Wh. Finished Goods 0',
                    'FGS'   => 'Wh. Finished Goods Scrapt',
                    'FOSF'  => 'Fiber Optic Shopfloor',
                    'LVSF'  => 'Low Voltage Shopfloor',
                    'MVSF'  => 'Medium Voltage Shopfloor',
                    'WHQ1'  => 'Wh. Quarantine Jembo',
                    'WHRM'  => 'Wh. Raw Material',
                    'WHRM1' => 'Wh. Raw Material 1',
                    'WHRM2' => 'WHRM2 Transit (Peminjaman)',
                    'WHRM3' => 'WHRM3',
                    'WHRM4' => 'WHRM4',
                    'WHSPT' => 'Warehouse Spareparts',
                ];

                $row13 = 2;
                foreach ($items as $item) {
                    // --- 1. DATA DINAMIS ---
                    if ($col = $getCol13('Item (child)')) $sheet13->setCellValue($col . $row13, $item->item_code);

                    // Penentuan Gudang
                    $whCode = $item->warehouse->warehouse_code ?? 'WHSPT';
                    $whDesc = $warehouseMapping[$whCode] ?? $whCode;

                    if ($col = $getCol13('Warehouse')) $sheet13->setCellValue($col . $row13, $whCode);
                    if ($col = $getCol13('Warehouse (child)')) $sheet13->setCellValue($col . $row13, $whDesc);

                    // --- 2. DATA STATIS BAWAAN ERP ---
                    foreach ($getCols13('Company') as $col) {
                        $sheet13->setCellValue($col . $row13, '400');
                    }

                    if ($col = $getCol13('Site')) $sheet13->setCellValue($col . $row13, $item->warehouse->site_code ?? 'JCC1');
                    if ($col = $getCol13('Site (child)')) $sheet13->setCellValue($col . $row13, 'Pt Jembo Factory (Main)');
                    if ($col = $getCol13('Order Method')) $sheet13->setCellValue($col . $row13, 'Lot for Lot');

                    $row13++;
                }

                // Proteksi dari Ghost Rows
                $highestRow13 = $sheet13->getHighestRow();
                if ($highestRow13 >= $row13) {
                    $sheet13->removeRow($row13, $highestRow13 - $row13 + 1);
                }

                $fileName13 = '13. Master Data - Item - Miscellaneous - Item Ordering - Footer.xlsx';
                $filePath13 = storage_path('app/public/temp/' . $fileName13);
                $writer13 = IOFactory::createWriter($spreadsheet13, 'Xlsx');
                $writer13->save($filePath13);
                $zip->addFile($filePath13, $fileName13);

                $spreadsheet13->disconnectWorksheets();
                unset($spreadsheet13);
            }
            // =============================================================
            // PROSES FILE 14: Master Data - Item - Miscellaneous - Item Ordering - Header
            // =============================================================
            $templatePath14 = storage_path('app/templates/14. Master Data - Item - Miscellaneous - Item Ordering - Header.xlsx');
            if (file_exists($templatePath14)) {
                $spreadsheet14 = IOFactory::load($templatePath14);
                $sheet14 = $spreadsheet14->getActiveSheet();

                $highestCol14 = $sheet14->getHighestColumn();
                $headers14 = $sheet14->rangeToArray('A1:' . $highestCol14 . '1', NULL, TRUE, FALSE)[0];

                // Fungsi tunggal
                $getCol14 = function ($headerName) use ($headers14) {
                    $index = array_search($headerName, $headers14);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                // Fungsi jamak untuk menangani kolom kembar (seperti Item (child) atau Seasonal Pattern)
                $getCols14 = function ($headerName) use ($headers14) {
                    $cols = [];
                    foreach ($headers14 as $index => $name) {
                        if ($name === $headerName) {
                            $cols[] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                        }
                    }
                    return $cols;
                };

                $row14 = 2;
                foreach ($items as $item) {
                    $itemUnit = strtoupper($item->unit ?? 'pcs');

                    // --- 1. DATA DINAMIS (DARI TAHAP 1 & TAHAP 4 WIZARD) ---

                    // Mengisi semua variasi kolom nama/child item jika tersedia
                    foreach ($getCols14('Item (child)') as $col) {
                        $sheet14->setCellValue($col . $row14, $item->item_code);
                    }
                    foreach ($getCols14('Item (child) (child)') as $col) {
                        $sheet14->setCellValue($col . $row14, $item->name);
                    }

                    // Mapping Kuantitas Aturan Pemesanan (Ordering Rules)
                    if ($col = $getCol14('Safety Stock')) $sheet14->setCellValue($col . $row14, $item->orderRule->safety_stock ?? 0);
                    if ($col = $getCol14('Safety Stock (child)')) $sheet14->setCellValue($col . $row14, $itemUnit);

                    if ($col = $getCol14('Reorder Point')) $sheet14->setCellValue($col . $row14, $item->orderRule->reorder_point ?? 0);
                    if ($col = $getCol14('Reorder Point (child)')) $sheet14->setCellValue($col . $row14, $itemUnit);

                    if ($col = $getCol14('Minimum')) $sheet14->setCellValue($col . $row14, $item->orderRule->min_order_qty ?? 0);
                    if ($col = $getCol14('Minimum (child)')) $sheet14->setCellValue($col . $row14, $itemUnit);

                    if ($col = $getCol14('Maximum')) $sheet14->setCellValue($col . $row14, $item->orderRule->max_order_qty ?? 99999999);
                    if ($col = $getCol14('Maximum (child)')) $sheet14->setCellValue($col . $row14, $itemUnit);


                    // --- 2. DATA STATIS CONTROL ERP (DEFAULT VALUE) ---
                    if ($col = $getCol14('Company')) $sheet14->setCellValue($col . $row14, '400');
                    if ($col = $getCol14('Method')) $sheet14->setCellValue($col . $row14, 'Lot for Lot');
                    if ($col = $getCol14('Interval')) $sheet14->setCellValue($col . $row14, 0);
                    if ($col = $getCol14('tcibd200.oivu')) $sheet14->setCellValue($col . $row14, 'Days');
                    if ($col = $getCol14('Safety Time')) $sheet14->setCellValue($col . $row14, 0);
                    if ($col = $getCol14('tcibd200.tuni')) $sheet14->setCellValue($col . $row14, 'Hours');

                    if ($col = $getCol14('Order Increment')) $sheet14->setCellValue($col . $row14, 1);
                    if ($col = $getCol14('Order Increment (child)')) $sheet14->setCellValue($col . $row14, $itemUnit);

                    if ($col = $getCol14('Fixed Order')) $sheet14->setCellValue($col . $row14, 0);
                    if ($col = $getCol14('Fixed Order (child)')) $sheet14->setCellValue($col . $row14, $itemUnit);

                    if ($col = $getCol14('Lot Size Calculation Allowed')) $sheet14->setCellValue($col . $row14, 'No');

                    if ($col = $getCol14('Maximum Inventory')) $sheet14->setCellValue($col . $row14, 999999999);
                    if ($col = $getCol14('Maximum Inventory (child)')) $sheet14->setCellValue($col . $row14, $itemUnit);
                    if ($col = $getCol14('Service Level')) $sheet14->setCellValue($col . $row14, 1);

                    // 1. Buat objek waktu untuk 31 Desember 9999, Jam 07:00:00
                    $lastAllowedDate = \Carbon\Carbon::create(9999, 12, 31, 7, 0, 0);

                    // 2. Ubah ke format Angka Serial Excel
                    $excelLastAllowedDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($lastAllowedDate);

                    if ($col = $getCol14('Last allowed Order Date')) {
                        // Set nilai angkanya, lalu format tampilannya menjadi m/d/yyyy
                        $sheet14->setCellValue($col . $row14, $excelLastAllowedDate);
                        $sheet14->getStyle($col . $row14)->getNumberFormat()->setFormatCode('m/d/yyyy');
                    }

                    if ($col = $getCol14('Last allowed Order Date (child)')) {
                        // Set nilai angkanya, lalu format tampilannya menjadi h:mm:ss
                        $sheet14->setCellValue($col . $row14, $excelLastAllowedDate);
                        $sheet14->getStyle($col . $row14)->getNumberFormat()->setFormatCode('h:mm:ss');
                    }

                    if ($col = $getCol14('Economic Order Quantity')) $sheet14->setCellValue($col . $row14, 1);
                    if ($col = $getCol14('Economic Order Quantity (child)')) $sheet14->setCellValue($col . $row14, $itemUnit);

                    if ($col = $getCol14('Use Recommended Quantity')) $sheet14->setCellValue($col . $row14, 'No');
                    if ($col = $getCol14('Order Costs')) $sheet14->setCellValue($col . $row14, 0);
                    if ($col = $getCol14('Order Costs (child)')) $sheet14->setCellValue($col . $row14, $item->procurement->currency ?? 'IDR');

                    $row14++;
                }

                // Proteksi dari Ghost Rows template
                $highestRow14 = $sheet14->getHighestRow();
                if ($highestRow14 >= $row14) {
                    $sheet14->removeRow($row14, $highestRow14 - $row14 + 1);
                }

                $fileName14 = '14. Master Data - Item - Miscellaneous - Item Ordering - Header.xlsx';
                $filePath14 = storage_path('app/public/temp/' . $fileName14);
                $writer14 = IOFactory::createWriter($spreadsheet14, 'Xlsx');
                $writer14->save($filePath14);
                $zip->addFile($filePath14, $fileName14);

                $spreadsheet14->disconnectWorksheets();
                unset($spreadsheet14);
            }
            // =============================================================
            // PROSES FILE 15: Input Serial Number Spareparts
            // =============================================================
            $templatePath15 = storage_path('app/templates/15. Input Serial Number Spareparts.xlsx');
            if (file_exists($templatePath15)) {
                $spreadsheet15 = IOFactory::load($templatePath15);
                $sheet15 = $spreadsheet15->getActiveSheet();

                $highestCol15 = $sheet15->getHighestColumn();
                $headers15 = $sheet15->rangeToArray('A1:' . $highestCol15 . '1', NULL, TRUE, FALSE)[0];

                // Fungsi pencarian kolom secara dinamis
                $getCol15 = function ($headerName) use ($headers15) {
                    $index = array_search($headerName, $headers15);
                    return $index !== false ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) : null;
                };

                $row15 = 2;
                foreach ($items as $item) {
                    // Cek status Serialization dari Tahap 5 Wizard
                    $isSerialized = $item->serialization->is_serialized ?? false;

                    // Jika bukan barang ber-serial, lewati baris ini
                    if (!$isSerialized) {
                        continue;
                    }

                    // --- 1. DATA DINAMIS ---
                    if ($col = $getCol15('Item (child)')) $sheet15->setCellValue($col . $row15, $item->item_code);

                    $serialNo = $item->serial_number ?? '';
                    if ($col = $getCol15('Serial Number')) $sheet15->setCellValue($col . $row15, $serialNo);

                    if ($col = $getCol15('Description')) $sheet15->setCellValue($col . $row15, $item->name);
                    if ($col = $getCol15('Search Argument')) $sheet15->setCellValue($col . $row15, $item->name);

                    // --- 2. DATA STATIS ---
                    if ($col = $getCol15('Company')) $sheet15->setCellValue($col . $row15, '400');
                    if ($col = $getCol15('Track GPS Location')) $sheet15->setCellValue($col . $row15, 'No');

                    // Waktu pendaftaran data ke sistem (Sinkron dengan File 11)
                    $exportTime = now();

                    // Mengonversi waktu PHP menjadi angka serial pecahan khas Excel
                    $excelDateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($exportTime);

                    if ($col = $getCol15('Creation Time')) {
                        // 1. Masukkan nilai angka murninya
                        $sheet15->setCellValue($col . $row15, $excelDateTime);
                        // 2. Format tampilan bebas nol di depan (m/d/yyyy)
                        $sheet15->getStyle($col . $row15)->getNumberFormat()->setFormatCode('m/d/yyyy');
                    }

                    if ($col = $getCol15('Creation Time (child)')) {
                        // 1. Masukkan nilai angka murninya
                        $sheet15->setCellValue($col . $row15, $excelDateTime);
                        // 2. Format tampilan bebas nol di depan jam (h:mm:ss)
                        $sheet15->getStyle($col . $row15)->getNumberFormat()->setFormatCode('h:mm:ss');
                    }

                    $row15++;
                }

                // Proteksi dari Ghost Rows (Menghapus sisa baris kosong bawaan template)
                $highestRow15 = $sheet15->getHighestRow();
                if ($highestRow15 >= $row15) {
                    $sheet15->removeRow($row15, $highestRow15 - $row15 + 1);
                }

                // Simpan berkas ke folder temporary dan bungkus ke dalam ZIP bundle
                $fileName15 = '15. Input Serial Number Spareparts.xlsx';
                $filePath15 = storage_path('app/public/temp/' . $fileName15);
                $writer15 = IOFactory::createWriter($spreadsheet15, 'Xlsx');
                $writer15->save($filePath15);
                $zip->addFile($filePath15, $fileName15);

                $spreadsheet15->disconnectWorksheets();
                unset($spreadsheet15);
            }
            $zip->close();

            MasterItem::whereIn('id', $items->pluck('id'))->update(['is_synced' => true]);
            Storage::disk('public')->deleteDirectory('temp');

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }
        return back()->with('error', 'Gagal membuat file ZIP.');
    }

    public function exportGroup($id)
    {
        $req = ItemRequest::findOrFail($id);

        // Pastikan ini adalah pengajuan grup baru
        if (!$req->new_group_code) {
            abort(404, 'Pengajuan ini tidak memiliki usulan Item Group baru.');
        }

        // 1. Tentukan path/lokasi file template Excel kosong Anda
        $templatePath = storage_path('app/templates/Item Group Template.xlsx');

        if (!file_exists($templatePath)) {
            abort(500, 'File template Excel tidak ditemukan di sistem.');
        }

        // 2. Load file template tersebut
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 3. Masukkan data ke sel yang kosong (Baris 2)
        // Berdasarkan template: Kolom E adalah "Item Group", Kolom F adalah "Item Group (child)"
        $sheet->setCellValue('E2', strtoupper($req->new_group_code));
        $sheet->setCellValue('F2', $req->new_group_desc);

        // (Opsional) Jika kolom Company (D), Currency (G) dll juga kosong di template asli Anda, 
        // Anda bisa mengisinya juga di sini:
        $sheet->setCellValue('D2', '400');
        $sheet->setCellValue('G2', 'IDR');
        $sheet->setCellValue('H2', 'Indonesia Rupiah');
        $sheet->setCellValue('I2', 'No');
        $sheet->setCellValue('J2', 'No');

        // 4. Siapkan file untuk didownload ke browser
        $filename = "Export_ItemGroup_{$req->new_group_code}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        // Simpan dan outputkan file yang sudah diisi
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
