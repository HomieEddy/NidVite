<?php

use App\Http\Controllers\ReportTrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/signaler', function () {
    return view('report');
})->name('report.create');

Route::get('/suivi/{uuid}', [ReportTrackingController::class, 'show'])
    ->name('report.tracking')
    ->whereUuid('uuid');
