<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\GoogleCalendarController; // Add this

Route::get('/', function () {
    return view('welcome');
});

// This group ensures only logged-in users can access these routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard route
    Route::get('/dashboard', function () {
        // Redirect dashboard to reminders index page
        return redirect()->route('reminders.index');
    })->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Add Reminder Resource Routes
    Route::resource('reminders', ReminderController::class);

    // Google Calendar Integration Routes
    Route::get('/google-calendar/connect', [GoogleCalendarController::class, 'connect'])
         ->name('google.calendar.connect'); // Route to start connection

    Route::get('/google-calendar/callback', [GoogleCalendarController::class, 'store'])
         ->name('google.calendar.callback'); // Route Google redirects back to

    Route::get('/google-calendar/disconnect', [GoogleCalendarController::class, 'destroy'])
         ->name('google.calendar.disconnect'); // Optional: Route to remove connection

    // Calendar Display Routes
    Route::get('/calendar', [ReminderController::class, 'calendar'])->name('calendar.index'); // Page display
    Route::get('/calendar/events', [ReminderController::class, 'getEvents'])->name('calendar.events'); // Data source
});

// Google Socialite Routes
Route::get('/auth/google/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('google.redirect');

Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback'])->name('google.callback');

// Include the default auth routes
require __DIR__.'/auth.php';