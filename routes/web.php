<?php


use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/cryptoapisverifydomain', function () {
//    return response()->json('cryptoapis-cb-ded2b5b81c16387d706997cd95e6483f41ffd8dfd920c626921f8fcc527865e9');
// });

Route::get('mail', function () {
    // return (new DemoNotice())->toMail((object) [])->render();  

    // Notification::route('mail', 'afuwapesunday12@gmail.com')
    //         ->notifyNow(new DemoNotice());
});


