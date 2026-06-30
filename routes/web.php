<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

require __DIR__ . '/panel.php';
require __DIR__ . '/auth.php';
