<?php

use App\Http\Controllers\Api\AcUnitController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Client\ClientKeluhanController;
use App\Http\Controllers\Api\Client\ClientMasterController;
use App\Http\Controllers\Api\Client\ClientServisController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\Owner\OwnerFloorController;
use App\Http\Controllers\Api\Owner\OwnerRoomController;
use App\Http\Controllers\Api\Owner\OwnerAcUnitController;
use App\Http\Controllers\Api\Owner\OwnerKeluhanController;
use App\Http\Controllers\Api\Owner\OwnerMasterController;
use App\Http\Controllers\Api\Owner\OwnerServisController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\Teknisi\TeknisiDashboardController;
use App\Http\Controllers\Api\Teknisi\TeknisiServisController;
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

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/media/service-item/{item}/photo', [MediaController::class, 'serviceItemPhoto']);

    // OWNER
    Route::prefix('owner')->middleware('role:owner')->group(function () {
        Route::prefix('clients')->group(function () {
        Route::get('/', [OwnerMasterController::class, 'clients']);
        Route::get('/{id}', [OwnerMasterController::class, 'clientShow']);
        Route::get('/{id}/stats', [OwnerMasterController::class, 'clientStats']);
        Route::post('/', [OwnerMasterController::class, 'clientStore']);
        Route::put('/{id}', [OwnerMasterController::class, 'clientUpdate']);
        Route::delete('/{id}', [OwnerMasterController::class, 'clientDestroy']);
    });

    // Technician Routes
    Route::prefix('technicians')->middleware('role:owner')->group(function () {
        Route::get('/', [OwnerMasterController::class, 'teknisi']);
        Route::get('/available', [OwnerMasterController::class, 'availableTeknisi']);
        Route::get('/{id}', [OwnerMasterController::class, 'teknisiShow']);
        Route::post('/', [OwnerMasterController::class, 'teknisiStore']);
        Route::put('/{id}', [OwnerMasterController::class, 'teknisiUpdate']);
        Route::delete('/{id}', [OwnerMasterController::class, 'teknisiDestroy']);
    });

    // ==================== LOCATIONS ====================
    Route::prefix('locations')->middleware('role:owner')->group(function () {
        Route::get('/', [OwnerMasterController::class, 'lokasiIndex']);
        Route::post('/', [OwnerMasterController::class, 'lokasiStore']);
        Route::put('/{id}', [OwnerMasterController::class, 'lokasiUpdate']);
        Route::delete('/{id}', [OwnerMasterController::class, 'lokasiDestroy']);
        
        Route::get('/{location}/rooms', [OwnerRoomController::class, 'byLocation']);

        // ✅ Floors di dalam lokasi (nested)
        Route::get('/{location}/floors', [OwnerFloorController::class, 'index']);
        Route::post('/{location}/floors', [OwnerFloorController::class, 'store']);
    });

    // ==================== FLOORS ====================
    Route::prefix('floors')->middleware('role:owner')->group(function () {
        Route::get('/{floor}', [OwnerFloorController::class, 'show']);
        Route::put('/{floor}', [OwnerFloorController::class, 'update']);
        Route::delete('/{floor}', [OwnerFloorController::class, 'destroy']);

        // ✅ Rooms di dalam floor (nested)
        Route::get('/{floor}/rooms', [OwnerRoomController::class, 'index']);
        Route::post('/{floor}/rooms', [OwnerRoomController::class, 'store']);
    });

    // ==================== ROOMS ====================
    Route::prefix('rooms')->middleware('role:owner')->group(function () {
        Route::get('/{room}', [OwnerRoomController::class, 'show']);
        Route::put('/{room}', [OwnerRoomController::class, 'update']);
        Route::delete('/{room}', [OwnerRoomController::class, 'destroy']);

        // (optional) AC list by room: /owner/rooms/{room}/ac-units
        Route::get('/{room}/ac-units', [OwnerAcUnitController::class, 'byRoom']);
    });

    // ==================== AC UNITS ====================
    // ✅ AC sekarang berbasis room_id (bukan location_id).
    // ✅ Tetap endpoint yang sama supaya frontend tidak terlalu berubah.
    Route::prefix('ac-units')->middleware('role:owner')->group(function () {
        Route::get('/', [OwnerAcUnitController::class, 'index']);        // filter: room_id / floor_id / location_id (optional)
        Route::get('/{acUnit}', [OwnerAcUnitController::class, 'show']);
        Route::post('/', [OwnerAcUnitController::class, 'store']);      // wajib room_id
        Route::put('/{acUnit}', [OwnerAcUnitController::class, 'update']);
        Route::delete('/{acUnit}', [OwnerAcUnitController::class, 'destroy']);
    });

    // ==================== Dashboard & Reports ====================
    Route::get('servis/dashboard', [OwnerServisController::class, 'dashboardStats']);
    Route::get('servis/filter-options', [OwnerServisController::class, 'filterOptions']);
    Route::get('servis/export', [OwnerServisController::class, 'export']);

    // Services CRUD
    Route::get('servis', [OwnerServisController::class, 'index']);
    Route::get('servis/{id}', [OwnerServisController::class, 'show']);
    Route::put('servis/{id}', [OwnerServisController::class, 'update']);
    Route::get('servis/status/{status}', [OwnerServisController::class, 'servicesByStatus']);

    // Actions
    Route::post('servis/{id}/konfirmasi-request', [OwnerServisController::class, 'konfirmasiRequest']);
    Route::post('servis/{id}/assign-teknisi', [OwnerServisController::class, 'assignTeknisi']);
    Route::post('servis/{id}/konfirmasi-pengerjaan', [OwnerServisController::class, 'konfirmasiPengerjaan']);
    Route::post('servis/{id}/assign-multiple-teknisi', [OwnerServisController::class, 'assignMultipleTechnicians']);
    Route::post('servis/{id}/assign-teknisi-per-ac', [OwnerServisController::class, 'assignTechnicianByAcGroups']);
    Route::post('servis/{id}/reassign-teknisi', [OwnerServisController::class, 'reassignTeknisi']);
        // Route::prefix('clients')->group(function () {
        //     Route::get('/', [OwnerMasterController::class, 'clients']);
        //     Route::get('/{id}', [OwnerMasterController::class, 'clientShow']);
        //     Route::get('/{id}/stats', [OwnerMasterController::class, 'clientStats']);
        //     Route::post('/', [OwnerMasterController::class, 'clientStore']);
        //     Route::put('/{id}', [OwnerMasterController::class, 'clientUpdate']);
        //     Route::delete('/{id}', [OwnerMasterController::class, 'clientDestroy']);
        // });

        // // Technician Routes
        // Route::prefix('technicians')->group(function () {
        //     Route::get('/', [OwnerMasterController::class, 'teknisi']);
        //     Route::get('/available', [OwnerMasterController::class, 'availableTeknisi']);
        //     Route::get('/{id}', [OwnerMasterController::class, 'teknisiShow']);
        //     Route::post('/', [OwnerMasterController::class, 'teknisiStore']);
        //     Route::put('/{id}', [OwnerMasterController::class, 'teknisiUpdate']);
        //     Route::delete('/{id}', [OwnerMasterController::class, 'teknisiDestroy']);
        // });

        // // Lokasi Routes (tetap ada)
        // Route::prefix('locations')->group(function () {
        //     Route::get('/', [OwnerMasterController::class, 'lokasiIndex']);
        //     Route::post('/', [OwnerMasterController::class, 'lokasiStore']);
        //     Route::put('/{id}', [OwnerMasterController::class, 'lokasiUpdate']);
        //     Route::delete('/{id}', [OwnerMasterController::class, 'lokasiDestroy']);
        // });

        // // AC Unit Routes (tetap ada)
        // Route::prefix('ac-units')->group(function () {
        //     Route::get('/', [OwnerMasterController::class, 'acIndex']);
        //     Route::get('/{id}', [OwnerMasterController::class, 'acShow']);
        //     Route::post('/', [OwnerMasterController::class, 'acStore']);
        //     Route::put('/{id}', [OwnerMasterController::class, 'acUpdate']);
        //     Route::delete('/{id}', [OwnerMasterController::class, 'acDestroy']);
        // });

        // // Dashboard & Reports
        // Route::get('servis/dashboard', [OwnerServisController::class, 'dashboardStats']);
        // Route::get('servis/filter-options', [OwnerServisController::class, 'filterOptions']);
        // Route::get('servis/export', [OwnerServisController::class, 'export']);

        // // Services CRUD
        // Route::get('servis', [OwnerServisController::class, 'index']);
        // Route::get('servis/{id}', [OwnerServisController::class, 'show']);
        // Route::put('servis/{id}', [OwnerServisController::class, 'update']);
        // Route::get('servis/status/{status}', [OwnerServisController::class, 'servicesByStatus']);

        // // Actions
        // Route::post('servis/{id}/konfirmasi-request', [OwnerServisController::class, 'konfirmasiRequest']);
        // Route::post('servis/{id}/assign-teknisi', [OwnerServisController::class, 'assignTeknisi']);
        // Route::post('servis/{id}/konfirmasi-pengerjaan', [OwnerServisController::class, 'konfirmasiPengerjaan']);

        // Route::post('servis/{id}/assign-multiple-teknisi', [OwnerServisController::class, 'assignMultipleTechnicians']);
        // Route::post('servis/{id}/assign-teknisi-per-ac', [OwnerServisController::class, 'assignTechnicianByAcGroups']);
        // Route::post('servis/{id}/reassign-teknisi', [OwnerServisController::class, 'reassignTeknisi']);
    });

    // CLIENT
    Route::prefix('client')->middleware('role:client')->group(function () {
        Route::post('servis/cuci', [ClientServisController::class, 'requestCuci']);
        Route::post('servis/perbaikan', [ClientServisController::class, 'requestPerbaikan']);
        Route::post('servis/instalasi', [ClientServisController::class, 'requestInstalasi']);
        Route::get('servis', [ClientServisController::class, 'index']);
        Route::get('servis/{id}', [ClientServisController::class, 'show']);

        Route::get('/lokasi', [ClientMasterController::class, 'lokasi']);
        Route::get('/ac', [ClientMasterController::class, 'ac']);
    });

    // TEKNISI
    Route::prefix('teknisi')->middleware('role:teknisi')->group(function () {
        Route::get('servis/tugas', [TeknisiServisController::class, 'tugasSaya']);

        // optional: mulai service (set service jadi dikerjakan kalau ada item mulai)
        Route::post('servis/{service}/mulai', [TeknisiServisController::class, 'mulaiPengerjaan']);

        // 1) mulai per item (tanpa foto)
        Route::post('servis-items/{item}/mulai', [TeknisiServisController::class, 'mulaiItem']);

        // 2) update progress per item: foto_sebelum/diagnosa/tindakan/foto_pengerjaan/foto_sesudah
        Route::post('servis-items/{item}/upload-foto', [TeknisiServisController::class, 'uploadFotoItem']);

        // 3) selesai per item (lebih tepat daripada selesai service)
        Route::post('servis-items/{item}/selesaikan', [TeknisiServisController::class, 'selesaikanItem']);

        // kalau kamu tetap mau selesai service (set selesai kalau semua item selesai)
        Route::post('servis/{service}/selesaikan', [TeknisiServisController::class, 'selesaikanPengerjaan']);
    });
});
