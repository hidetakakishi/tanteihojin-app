<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScrapingController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/scraping', [ScrapingController::class, 'index'])->name('scraping.index');
Route::get('/scraping/sources', [ScrapingController::class, 'sources'])->name('scraping.sources');
Route::post('/scraping/source', [ScrapingController::class, 'storeSource'])->name('scraping.store');
Route::delete('/scraping/source/{id}', [ScrapingController::class, 'destroy'])->name('scraping.destroy');
Route::get('/scraping/run/{id}', [ScrapingController::class, 'scrape'])->name('scraping.run');
Route::post('/scraping/update-all', [ScrapingController::class, 'scrapeAll'])->name('scraping.updateAll');