<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CouponBotController;
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

$token = config('telegram.bots.mybot.token');



// Example of POST Route:
Route::post("/$token/webhook", [CouponBotController::class, 'webhook']);

Route::get('/', function () {

    return view('welcome');
});
