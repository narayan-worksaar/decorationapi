<?php

use App\Http\Controllers\API\AgentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\HomeController;
use App\Http\Controllers\API\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([

    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'auth'

], function ($router) {

    Route::post('login', [AuthController:: class, 'login']);
    Route::post("register", [AuthController::class, "register"]);
    Route::get("me", [AuthController::class, "me"]);
    Route::post("logout", [AuthController::class, "logout"]);
    Route::post("refresh", [AuthController::class, "refresh"]);
    Route::get("nearest-service-center", [AuthController::class, "nearest_service_center"]);
    //booking
    Route::get("all-service-booking-fields", [BookingController::class, "all_service_booking_fields"]);
    Route::post("store-service-booking", [BookingController::class, "store_service_booking"]);
    Route::get("task-type", [BookingController::class, "task_type"]);
    Route::post("update-dealer-details", [UserController::class, "update_dealer_details"]);
    Route::get("all-measurement-details-fields", [BookingController::class, "all_measurement_details_fields"]);
    Route::get("all-pending-booking", [BookingController::class, "all_pending_booking"]);
    Route::get("payment-mode", [BookingController::class, "payment_mode"]);
    
    Route::get("all-on-going-booking", [BookingController::class, "all_on_going_booking"]);
    Route::get("get-on-going-service-details", [BookingController::class, "get_on_going_service_details"]);

    Route::get("all-states", [UserController::class, "all_states"]);
    Route::get("state-wise-cities", [UserController::class, "state_wise_cities"]);
    
    Route::get("get-service-details", [BookingController::class, "get_service_details"]);
    Route::post("update-booked-service", [BookingController::class, "update_booked_service"]);
    Route::get("all-assigned-service", [BookingController::class, "all_assigned_service"]);

    Route::get("all-status", [AgentController::class, "all_status"]);
    Route::post("update-service-by-agent", [AgentController::class, "update_service_by_agent"]);
    
    Route::get("all-completed-booking", [BookingController::class, "all_completed_booking"]);
    Route::get("get-booking-completed-details", [BookingController::class, "get_booking_completed_details"]);

    Route::get("all-yes-no", [AgentController::class, "all_yes_no"]);
    Route::post("accept-decline-task", [AgentController::class, "accept_decline_task"]);
    
});

Route::get("user-types", [HomeController::class, "user_types"]);
Route::get("on-boarding", [HomeController::class, "on_boarding"]);
Route::post("forgot-password", [HomeController::class, "forgot_password"]);
Route::post("update-role", [HomeController::class, "update_role"]);
Route::get("get-service-booking-data", [HomeController::class, "get_service_booking_data"]);
Route::get("gender", [HomeController::class, "gender"]);

