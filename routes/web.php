<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

require __DIR__ . '/panel.php';
require __DIR__ . '/auth.php';
