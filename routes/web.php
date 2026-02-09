<?php

use App\Http\Controllers\PayslipPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kasir/payslips/{payslip}/pdf', PayslipPdfController::class)->name('payslips.pdf');
});
