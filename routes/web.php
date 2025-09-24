<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Show the Vue form page (Blade shell). URL: /deals/create
Route::get('/deals/create', function () {
    return view('deals.create');
})->name('deals.create');
