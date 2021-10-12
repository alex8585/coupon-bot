<?php

use Illuminate\Support\Facades\Route;
use Intervention\Image\ImageManagerStatic as Image;
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
    //phpinfo();
    // Image::configure(array('driver' => 'imagick'));
    // $img = Image::make('https://cdn.admitad.com/campaign/images/2021/4/9/17057-6835477a87515633.svg');

    // // resize image
    // // $img->fit(300, 200);

    // // save image
    // $img->save('bar.jpg');
    // dd('d');
    return view('welcome');
});
