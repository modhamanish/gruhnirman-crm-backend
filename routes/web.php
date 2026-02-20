<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-migration', function () {
    Artisan::call('migrate --force');
    return "Migrations finished successfully!";
});

Route::get('/run-seed', function () {
    Artisan::call('db:seed --force');
    return "Seed finished successfully!";
});

Route::get('cache-clear', function () {
    Artisan::call('optimize:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');

    echo "Cache Clear successfully";
});

Route::get('/generate-swagger', function () {
    Artisan::call('l5-swagger:generate');
    return "Swagger generated successfully!";
});
