<?php

use Illuminate\Support\Facades\Route;

Route::get('/hello', fn () => response('notes-hello'))->name('hello');
