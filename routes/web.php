<?php

use App\Http\Controllers\DealerController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\Voyager\ServiceController;
use App\Http\Controllers\Voyager\UserController;
use App\Mail\ActivationAccountMail;
use App\Mail\RegisterUserMail;
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
    return "successfull storage link";
});

Route::get('/migratetable', function () {
    Artisan::call('migrate');
    return "successfull migrate";
});

Route::get('/cacheClear', function () {
    Artisan::call('config:cache');
    return "successful cache clear";
});


Route::get('/rollbackstepone', function () {
    Artisan::call('migrate:rollback', ['--step' => 1]);
    return "successfully rolled back one step";
});


// Route::get('/registerMail', function () {
//     return new ActivationAccountMail();
// });

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    Route::post("/update-user-status", [UserController::class, "update_user_status"])->name("update-user-status");
    Route::get("view-agent-task/{id}", [ServiceController::class, "view_agent_task"]);
    Route::get("view-agent-task-details/{id}", [ServiceController::class, "view_agent_task_details"]);
    Route::get("show-agent-list/{id}", [ServiceController::class, "show_agent_list"]);
    Route::put("update-agent", [ServiceController::class, "update_agent"]);
    Route::get("fcm-message", [ServiceController::class, "fcm_message"]);

    Route::get("/dealer", [DealerController::class, "index"])->name("dealer.index");
    Route::get("read-notification/{id}", [ServiceController::class, "read_notification"]);
       
    
    
});


