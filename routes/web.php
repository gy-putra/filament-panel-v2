<?php

use App\Http\Controllers\HomeController;
use App\Livewire\Post\Show as PostShow;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/article/{post:slug}', PostShow::class)->name('post.show');
Route::get('/detailpaket/{paket}', [HomeController::class, 'detailPaket'])->name('paket.detail');
