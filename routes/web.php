<?php

use App\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get("/reset-password", [ResetPasswordController::class, "reset_password"]);
Route::post("/save-new-password", [ResetPasswordController::class, "save_new_password"])->name('save_new_password');
Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
    return "successfull";
});