<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FormController;
use App\Services\GoogleSheetService;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\PudoBoothAdmin;
use App\Http\Controllers\OpenHouseController;

// Home
Route::get('/', function () {
    return view('sementara.homepage', [
        "alert" => session('success'),
        "alertForward" => "/"
    ]);
});

// OPEN HOUSE FRONTEND
Route::get('/oh/ticket', function() {
    return view('sementara.openhouse_check_ticket');
});
Route::get('/oh/ticket/data', [OpenHouseController::class, 'showTicketDataPage'])->name('oh.ticketdata');
Route::get('/oh/dashboard', function() {
    return view('sementara.openhouse_dashboard');
});
Route::get('oh/database', function() {
    return redirect()->away('https://auth-db1322.hstgr.io/');
});
// OPEN HOUSE API
// DEBUG
Route::get('/api/oh/data', [OpenHouseController::class, 'getOpenHouseData'])->name('oh.data');
Route::get('/api/oh/stats', [OpenHouseController::class, 'getOpenHouseStats'])->name('oh.stats');
Route::get('/api/oh/parsed-data', [OpenHouseController::class, 'getParsedData'])->name('oh.parseddata');
Route::get('/api/oh/sync-sql', [OpenHouseController::class, 'syncToSQLDatabase'])->name('oh.syncsql');
Route::get('/api/oh/check-new-data', [OpenHouseController::class, 'checkSheetsBaru'])->name('oh.checknewdata');
Route::get('/api/oh/sql-search', [OpenHouseController::class, 'sqlSearchLogic'])->name('oh.sqlsearchdata');
// API SHEETS
Route::get('/api/oh/check-ticket-sheets/{name}', [OpenHouseController::class, 'sheetsCheckTicket'])->name('oh.checkticket');
// API SQL
Route::get('/api/oh/get-all-data-sql', [OpenHouseController::class, 'sqlGetAllData'])->name('oh.getallsqldata');
Route::get('/api/oh/check-ticket-sql', [OpenHouseController::class, 'sqlCheckTicket'])->name('oh.checkticketsql');
Route::get('/api/oh/update-ticket-status', [OpenHouseController::class, 'sqlUpdateTicketStatus'])->name('oh.updateticketstatus');
// API ABSENSI
Route::get('/api/oh/present', [OpenHouseController::class, 'sqlOHPresent'])->name('oh.sqlOHPresent');

Route::get('/sitemap.xml', function () {
    return response()->file(resource_path('views/sitemap.xml'), [
        'Content-Type' => 'application/xml'
    ]);
});
