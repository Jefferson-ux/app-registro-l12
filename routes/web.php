<?php

use App\Http\Controllers\MailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/lang/{locale}', function (Request $request, string $locale) {
    if (in_array($locale, ['es', 'en'])) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang.switch');


Route::get('/thank-you', function () {
    return view('pages.thank-you');
})->name('thank-you');

Route::get('/mail-send', [MailController::class, 'mailMe'])->name('mail-send');
