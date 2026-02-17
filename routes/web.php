<?php

use App\Http\Controllers\PayslipPdfController;
use App\Http\Controllers\SaleThermalPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kasir/payslips/{payslip}/pdf', PayslipPdfController::class)->name('payslips.pdf');
    Route::get('/kasir/sales/{sale}/print-thermal', SaleThermalPrintController::class)->name('sales.print.thermal');
});
