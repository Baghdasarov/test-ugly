<?php

use App\Http\Controllers\CommissionController;
use Illuminate\Support\Facades\Route;

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('commission', [CommissionController::class, 'calculateCommission']);


