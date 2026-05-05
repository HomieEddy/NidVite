<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/signaler', function () {
    return view('report');
})->name('report.create');

Route::get('/admin', function () {
    return redirect('/admin');
});
