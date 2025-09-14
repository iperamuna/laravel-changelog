<?php

use Illuminate\Support\Facades\Route;
use Iperamuna\LaravelChangelog\Http\Controllers\ChangelogController;


Route::get(config('changelog.url'), ChangelogController::class)
    ->name('laravel-changelog.endpoint');
