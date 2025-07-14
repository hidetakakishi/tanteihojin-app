<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScrapingController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmailResponseController;
use App\Http\Controllers\ImportEmailsController;

Route::get('/', function () {
    return view('welcome');
});

// 記事スクレイピング画面
Route::get('/scraping', [ScrapingController::class, 'index'])->name('scraping.index');
Route::get('/scraping/sources', [ScrapingController::class, 'sources'])->name('scraping.sources');
Route::post('/scraping/source', [ScrapingController::class, 'storeSource'])->name('scraping.store');
Route::delete('/scraping/source/{id}', [ScrapingController::class, 'destroy'])->name('scraping.destroy');
Route::get('/scraping/run/{id}', [ScrapingController::class, 'scrape'])->name('scraping.run');
Route::post('/scraping/update-all', [ScrapingController::class, 'scrapeAll'])->name('scraping.updateAll');

Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');
Route::get('/emails/{id}', [EmailController::class, 'show'])->name('emails.show');
Route::post('/emails/import', [ImportEmailsController::class, 'import'])->name('emails.import');
Route::post('/emails/{email}/response', [EmailResponseController::class, 'store'])->name('emails.response.store');
Route::patch('/email-responses/{id}', [EmailResponseController::class, 'update'])->name('emails.response.update');
Route::delete('/emails/response/{id}', [EmailResponseController::class, 'destroy'])->name('emails.response.destroy');
Route::delete('/emails/{email}', [App\Http\Controllers\EmailController::class, 'destroy'])->name('emails.destroy');
