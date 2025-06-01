<?php
// Otp Controller

Route::controller(\App\Http\Controllers\OtpController::class)->group(function() {
    Route::post('/send-otp', 'sendOtp')->name('send.otp');
    Route::post('/login-otp', 'loginWithOtp')->name('login.otp');

    Route::get('/complete-registration', 'showCompleteRegistrationForm')->name('complete.registration');
    Route::post('/complete-registration', 'completeRegistration')->name('complete.registration');



});
