<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\MasterItemExportController;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Volt::route('/dashboard', 'approval-dashboard')->name('dashboard');
    Volt::route('/master-item/create/{itemRequestId?}', 'master-item-wizard')->name('master-item.create');
    Volt::route('/purchase-request/create', 'purchase-request-form')->name('purchase-request.create');
    Volt::route('/katalog-barang', 'item-catalog')->name('katalog.index');
    Volt::route('/status-pengajuan-barang', 'item-request-dashboard')->name('item.requests');
    Route::get('/master-items/export-erp-bundle', [MasterItemExportController::class, 'exportZip'])->name('export.erp.bundle');
    Route::get('/master-items/export-group/{id}', [MasterItemExportController::class, 'exportGroup'])->name('export.group');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
