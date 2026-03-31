<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MediaController;

use App\Http\Controllers\Api\Client\ClientMasterController;
use App\Http\Controllers\Api\Client\ClientServisController;

use App\Http\Controllers\Api\Owner\OwnerAcUnitController;
use App\Http\Controllers\Api\Owner\OwnerFloorController;
use App\Http\Controllers\Api\Owner\OwnerMasterController;
use App\Http\Controllers\Api\Owner\OwnerRoomController;
use App\Http\Controllers\Api\Owner\OwnerServisController;

use App\Http\Controllers\Api\Teknisi\TeknisiServisController;

use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/media/service-item/{item}/photo', [MediaController::class, 'serviceItemPhoto']);

    /*
    |--------------------------------------------------------------------------
    | OWNER
    |--------------------------------------------------------------------------
    */
    Route::prefix('owner')->middleware('role:owner')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Clients
        |--------------------------------------------------------------------------
        */
        Route::prefix('clients')->group(function () {
            Route::get('/', [OwnerMasterController::class, 'clients']);
            Route::post('/', [OwnerMasterController::class, 'clientStore']);
            Route::get('/{id}', [OwnerMasterController::class, 'clientShow']);
            Route::get('/{id}/stats', [OwnerMasterController::class, 'clientStats']);
            Route::put('/{id}', [OwnerMasterController::class, 'clientUpdate']);
            Route::delete('/{id}', [OwnerMasterController::class, 'clientDestroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Technicians
        |--------------------------------------------------------------------------
        */
        Route::prefix('technicians')->group(function () {
            Route::get('/', [OwnerMasterController::class, 'teknisi']);
            Route::post('/', [OwnerMasterController::class, 'teknisiStore']);
            Route::get('/available', [OwnerMasterController::class, 'availableTeknisi']);
            Route::get('/{id}', [OwnerMasterController::class, 'teknisiShow']);
            Route::put('/{id}', [OwnerMasterController::class, 'teknisiUpdate']);
            Route::delete('/{id}', [OwnerMasterController::class, 'teknisiDestroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Locations
        |--------------------------------------------------------------------------
        */
        Route::prefix('locations')->group(function () {
            Route::get('/', [OwnerMasterController::class, 'lokasiIndex']);
            Route::post('/', [OwnerMasterController::class, 'lokasiStore']);
            Route::put('/{id}', [OwnerMasterController::class, 'lokasiUpdate']);
            Route::delete('/{id}', [OwnerMasterController::class, 'lokasiDestroy']);

            // Rooms under location
            Route::get('/{location}/rooms', [OwnerRoomController::class, 'byLocation']);
            Route::post('/{location}/rooms', [OwnerRoomController::class, 'store']);
        });

        /*
        |--------------------------------------------------------------------------
        | Floors (Master Umum)
        |--------------------------------------------------------------------------
        */
        Route::prefix('floors')->group(function () {
            Route::get('/', [OwnerFloorController::class, 'index']);
            Route::post('/', [OwnerFloorController::class, 'store']);
            Route::get('/{floor}', [OwnerFloorController::class, 'show']);
            Route::put('/{floor}', [OwnerFloorController::class, 'update']);
            Route::delete('/{floor}', [OwnerFloorController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Rooms
        |--------------------------------------------------------------------------
        */
        Route::prefix('rooms')->group(function () {
            Route::get('/{room}', [OwnerRoomController::class, 'show']);
            Route::put('/{room}', [OwnerRoomController::class, 'update']);
            Route::delete('/{room}', [OwnerRoomController::class, 'destroy']);

            // AC by room
            Route::get('/{room}/ac-units', [OwnerAcUnitController::class, 'byRoom']);
        });

        /*
        |--------------------------------------------------------------------------
        | AC Units
        |--------------------------------------------------------------------------
        */
        Route::prefix('ac-units')->group(function () {
            Route::get('/', [OwnerAcUnitController::class, 'index']);
            Route::post('/', [OwnerAcUnitController::class, 'store']);
            Route::get('/{acUnit}', [OwnerAcUnitController::class, 'show']);
            Route::put('/{acUnit}', [OwnerAcUnitController::class, 'update']);
            Route::delete('/{acUnit}', [OwnerAcUnitController::class, 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Services / Dashboard / Reports
        |--------------------------------------------------------------------------
        */
        Route::prefix('servis')->group(function () {
            Route::get('/', [OwnerServisController::class, 'index']);
            Route::get('/dashboard', [OwnerServisController::class, 'dashboardStats']);
            Route::get('/filter-options', [OwnerServisController::class, 'filterOptions']);
            Route::get('/export', [OwnerServisController::class, 'export']);
            Route::get('/status/{status}', [OwnerServisController::class, 'servicesByStatus']);
            Route::get('/{id}', [OwnerServisController::class, 'show']);
            Route::put('/{id}', [OwnerServisController::class, 'update']);

            // Actions
            Route::post('/{id}/konfirmasi-request', [OwnerServisController::class, 'konfirmasiRequest']);
            Route::post('/{id}/assign-teknisi', [OwnerServisController::class, 'assignTeknisi']);
            Route::post('/{id}/assign-multiple-teknisi', [OwnerServisController::class, 'assignMultipleTechnicians']);
            Route::post('/{id}/assign-teknisi-per-ac', [OwnerServisController::class, 'assignTechnicianByAcGroups']);
            Route::post('/{id}/konfirmasi-pengerjaan', [OwnerServisController::class, 'konfirmasiPengerjaan']);

            // Aktifkan hanya jika method-nya memang ada di controller
            // Route::post('/{id}/reassign-teknisi', [OwnerServisController::class, 'reassignTeknisi']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | CLIENT
    |--------------------------------------------------------------------------
    */
    Route::prefix('client')->middleware('role:client')->group(function () {
        Route::prefix('servis')->group(function () {
            Route::get('/', [ClientServisController::class, 'index']);
            Route::get('/{id}', [ClientServisController::class, 'show']);
            Route::post('/cuci', [ClientServisController::class, 'requestCuci']);
            Route::post('/perbaikan', [ClientServisController::class, 'requestPerbaikan']);
            Route::post('/instalasi', [ClientServisController::class, 'requestInstalasi']);
        });

        Route::get('/lokasi', [ClientMasterController::class, 'lokasi']);
        Route::get('/ac', [ClientMasterController::class, 'ac']);
    });

    /*
    |--------------------------------------------------------------------------
    | TEKNISI
    |--------------------------------------------------------------------------
    */
    Route::prefix('teknisi')->middleware('role:teknisi')->group(function () {
        Route::get('/servis/tugas', [TeknisiServisController::class, 'tugasSaya']);

        // service-level
        Route::post('/servis/{service}/mulai', [TeknisiServisController::class, 'mulaiPengerjaan']);
        Route::post('/servis/{service}/selesaikan', [TeknisiServisController::class, 'selesaikanPengerjaan']);

        // item-level
        Route::post('/servis-items/{item}/mulai', [TeknisiServisController::class, 'mulaiItem']);
        Route::post('/servis-items/{item}/upload-foto', [TeknisiServisController::class, 'uploadFotoItem']);
        Route::post('/servis-items/{item}/selesaikan', [TeknisiServisController::class, 'selesaikanItem']);

        // kalau masih dipakai endpoint lama upload foto generik
        // Route::post('/servis-items/{item}/foto', [TeknisiServisController::class, 'uploadFoto']);
    });
});