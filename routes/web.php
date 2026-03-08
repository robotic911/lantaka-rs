<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController; 
use App\Http\Controllers\AccountController; 
use App\Http\Controllers\RoomVenueController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\FoodController;

/* --- 1. Pages Routes --- */

/* Index */
Route::get('/', function () {
    return view('pages/index');
})->name('pages/index');
/* Login */
Route::get('/login', action: [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
/* Singup */
Route::get('/signup', [SignupController::class, 'showSignupForm'])->name('signup');
Route::post('/signup', [SignupController::class, 'store'])->name('register.post');
/* User Room Venue without Login */
Route::get('client.room_venue', [RoomVenueController::class, 'index'])->name('client.room_venue');

/* Layouts */
/* Pwede ka mag store ng function, mag prefix, sa isang line lng */

    Route::prefix('employee')
        ->name('employee.')
        ->middleware(['role:admin,staff'])
        ->group(function () {

            Route::get('/dashboard', action: fn() => view('employee.dashboard'))->name('dashboard');
            Route::get('/dashboard', [ReservationController::class, 'displayStatistics'])->name('dashboard');
            Route::get('/dashboard', [ReservationController::class, 'showReservationsCalendar'])->name('dashboard');
            Route::get('/employee_dashboard', function () {
                // Fetch the food for the dashboard modal
                $foods = \App\Models\Food::all()->groupBy('food_category');
                return view('employee.dashboard', compact('foods'));
            })->name('employee.dashboard');
            Route::get('/reservations', [ReservationController::class, 'adminIndex'])->name('reservations');
            Route::get('/guest', [ReservationController::class, 'showGuests'])->name('guest');
            Route::post('/guest', [ReservationController::class, 'updateGuests'])->name('updateGuests');
            Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
            Route::get('/room_venue', [RoomVenueController::class, 'adminIndex'])->name('room_venue');
            Route::get('/eventlogs', action: fn() => view('employee.eventlogs'))->name('eventlogs');
            Route::post('/reservations/{id}/status', [ReservationController::class, 'updateStatus'])->name('reservations.updateStatus');
            Route::post('/accounts/{id}/update-status', [AccountController::class, 'updateStatus'])->name('accounts.updateStatus');
            Route::post('/accounts/{id}/update', [AccountController::class, 'update'])->name('employee.accounts.update');
            Route::get('/SOA', action: fn() => view('employee.SOA'))->name('SOA');
            Route::post('/room_venue', action: fn() => view('employee.SOA'))->name('SOA');

        });
        Route::prefix('client')
        ->name('client.')
        ->middleware(['auth'])
        ->group(function () {
    
            Route::get('/view/{category}/{id}', [RoomVenueController::class, 'show'])->name('show');
            Route::get('/my_bookings', [ReservationController::class, 'checkout'])->name('my_bookings');
            Route::get('/my_reservations', [ReservationController::class, 'index'])->name('my_reservations');
            Route::get('/food_option', function () {
                return view('food_option');
            })->name('food_option');
        });
Route::get('/accommodations', [RoomVenueController::class, 'index'])->name('client.index');

Route::post('/client/reservations/{id}/cancel', [ReservationController::class, 'cancel'])
    ->name('client.reservations.cancel');

Route::get('/booking/prepare', [RoomVenueController::class, 'prepareBooking'])->name('booking.prepare');

Route::get('/checkout/{category}/{id}', [RoomVenueController::class, 'show'])->name('client.show');

/* --- 2. Login & Signup --- */

/* --- 3. Protected Routes (SECURE) --- */
Route::middleware(['auth'])->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // 1. The Checkout Page (calculates price)
    Route::get('/checkout', [ReservationController::class, 'checkout'])->name('checkout');
    
    // 2. The Action to Save the Reservation
    Route::post('/reservation/store', [ReservationController::class, 'store'])->name('reservation.store');
    
    // 3. My Reservations (Shows database data instead of static view)
    //Route::get('/client_my_reservations', [ReservationController::class, 'index'])->name('client_my_reservations');

  
    Route::post('/employee_room_venue/store', [RoomVenueController::class, 'store'])->name('room_venue.store');

    Route::post('/employee/food/store', [FoodController::class, 'store'])->name('admin.food.store');
    Route::put('/employee/room_venue/{id}', [FoodController::class, 'update'])->name('admin.food.update');});
    Route::get('/employee/room_venue/{id}/delete', [FoodController::class, 'destroy']);  
// TEST ONLY DO NOT TOUCH (CALENDAR)
Route::get('/test_client_room_venue_viewing', function () {
    return view('test_client_room_venue_viewing');
})->name('test_client_room_venue_viewing');
// TEST ONLY DO NOT TOUCH (CALENDAR)