<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScrapingController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmailResponseController;
use App\Http\Controllers\ImportEmailsController;
use App\Http\Controllers\PhoneCallController;
use App\Http\Controllers\LineWorksBotController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import-guide', function () {
    return view('import_guide');
})->name('import.guide');

// 記事スクレイピング画面
Route::get('/scraping', [ScrapingController::class, 'index'])->name('scraping.index');
Route::get('/scraping/sources', [ScrapingController::class, 'sources'])->name('scraping.sources');
Route::post('/scraping/source', [ScrapingController::class, 'storeSource'])->name('scraping.store');
Route::delete('/scraping/source/{id}', [ScrapingController::class, 'destroy'])->name('scraping.destroy');
Route::get('/scraping/run/{id}', [ScrapingController::class, 'scrape'])->name('scraping.run');
Route::post('/scraping/update-all', [ScrapingController::class, 'scrapeAll'])->name('scraping.updateAll');

// メール反響一覧画面
Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');
Route::get('/emails/{id}', [EmailController::class, 'show'])->name('emails.show');
Route::post('/emails/import', [ImportEmailsController::class, 'import'])->name('emails.import');
Route::post('/emails/{email}/response', [EmailResponseController::class, 'store'])->name('emails.response.store');
Route::patch('/email-responses/{id}', [EmailResponseController::class, 'update'])->name('emails.response.update');
Route::delete('/emails/response/{id}', [EmailResponseController::class, 'destroy'])->name('emails.response.destroy');
Route::delete('/emails/{email}', [App\Http\Controllers\EmailController::class, 'destroy'])->name('emails.destroy');

//　電話反響一覧画面
Route::get('/calls', [PhoneCallController::class, 'index'])->name('calls.index');
Route::post('/calls/import', [PhoneCallController::class, 'import'])->name('calls.import');
Route::get('/calls/{id}', [PhoneCallController::class, 'show'])->name('calls.show');
Route::put('/calls/response/{id}', [PhoneCallController::class, 'updateResponse'])->name('calls.response.update');
Route::delete('/calls/response/{id}', [PhoneCallController::class, 'destroyResponse'])->name('calls.response.destroy');
Route::post('/calls/{call}/responses', [PhoneCallController::class, 'storeResponse'])->name('calls.response.store');

// LINE WORKS API
Route::post('/api/line-works/callback', [LineWorksBotController::class, 'handleWebhook']);