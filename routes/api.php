<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\BitrixController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::get('filter-data',[\App\Http\Controllers\FilterDataController::class,'index']);
//Route::any('export-data',[\App\Http\Controllers\FilterDataController::class,'exportData']);

Route::post('webhook/smart-process', [BitrixController::class, 'smartProcess']);
Route::post('webhook/lead', [BitrixController::class, 'lead']);

Route::post('webhook/smart-stage', [BitrixController::class, 'smartStage']);

Route::get('activities', [ActivityController::class, 'index']);


//Route::post('tickets', [TicketController::class, 'index']);


