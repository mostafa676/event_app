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

Route::get('/', function () {
    return view('welcome');
});
Route::get('/test-fcm-fixed', function () {
    $user = \App\Models\User::first();
    $user->fcm_token = 'test-token-123';
    $user->save();
    
    $result = \App\Helpers\NotificationHelper::sendFCM(
        $user, 
        'test', 
        'Test Title', 
        'Test Body',
        [
            'notifiable_id' => 1,
            'notifiable_type' => 'App\Models\User'
        ]
    );
    
    return response()->json($result);
});
