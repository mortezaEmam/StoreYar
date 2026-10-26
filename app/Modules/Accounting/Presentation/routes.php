<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use StoreYar\Modules\Accounting\Presentation\Http\Controllers\AccountingController;

Route::get('/accounts', [AccountingController::class, 'accounts']);
Route::post('/accounts', [AccountingController::class, 'storeAccount']);
Route::get('/journals', [AccountingController::class, 'journals']);
Route::post('/journals', [AccountingController::class, 'storeJournal']);
Route::get('/journals/{id}', [AccountingController::class, 'showJournal']);
Route::post('/journals/{id}/post', [AccountingController::class, 'postJournal']);
Route::get('/fiscal-years', [AccountingController::class, 'fiscalYears']);
Route::post('/fiscal-years', [AccountingController::class, 'storeFiscalYear']);
