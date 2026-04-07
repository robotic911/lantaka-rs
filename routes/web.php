<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\RoomVenueController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\EventLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReservationCalendarExport;
// use App\Http\Controllers\CalendarStreamController;


/* Index */
Route::get('/', function () { return view('pages/index');})->name('pages/index');

/* Login */
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

/* Signup */
Route::get('/signup', [SignupController::class, 'showSignupForm'])->name('signup');
Route::post('/signup', [SignupController::class, 'store'])->name('register.post');

/* Forgot Password */
Route::get('/forgot-password',  [LoginController::class, 'showForgotPassword'])->name('forgot.password');
Route::post('/forgot-password', [LoginController::class, 'sendForgotPassword'])->name('forgot.password.send');

/* Room/Venue browsing — public (no login required) */
Route::get('/client/room-venue', [RoomVenueController::class, 'index'])->name('client.room_venue');
Route::get('/accommodations', [RoomVenueController::class, 'index'])->name('client.index');

// Route::get('/checkout/{category}/{id}', [RoomVenueController::class, 'show'])->name('client.show');
Route::get('/booking/prepare', [RoomVenueController::class, 'prepareBooking'])->name('booking.prepare');

/* Food AJAX (used by both client and employee booking flows) */
Route::get('/foods/ajax/list',     [FoodController::class, 'getFoodsAjax'])->name('foods.ajax.list');
Route::get('/foods/ajax/sets',     [FoodController::class, 'getFoodSetsAjax'])->name('foods.ajax.sets');
Route::get('/view/{category}/{id}', [RoomVenueController::class, 'show'])->name('client.show');

/* TEST ONLY — DO NOT TOUCH */
Route::get('/test_client_room_venue_viewing', function () {
    return view('test_client_room_venue_viewing');
})->name('test_client_room_venue_viewing');

/* Employee Routes --- */

Route::prefix('employee')
    ->name('employee.')
    ->middleware(['role:admin,staff'])       
    ->group(function () {
     
        Route::post('/reservations/{id}/status', [ReservationController::class, 'updateStatus'])->name('reservations.updateStatus');
        Route::post('/reservations/{id}/mark-paid', [ReservationController::class, 'markAsPaid'])->name('reservations.markPaid');
        Route::get('/dashboard', [ReservationController::class, 'showReservationsCalendar'])->name('dashboard');
        Route::get('/reservations', [ReservationController::class, 'adminIndex'])->name('reservations');
        
        Route::get('/reservations/{id}', [ReservationController::class, 'adminIndexSpecificId'])->name('reservations.specific');
        Route::get('/guest/{id}', [ReservationController::class, 'adminIndexSpecificId'])->name('guests.specific');

        Route::get('/guest', [ReservationController::class, 'showGuests'])->name('guest');
        Route::put('/guest', [ReservationController::class, 'updateGuests'])->name('updateGuests');
        Route::get('/SOA/{clientId}', [ReservationController::class, 'showSOA'])->name('SOA');
        // Route::post('/reservations/store', [ReservationController::class, 'storeReservation'])->name('reservations.store');
        Route::get('/eventlogs', [EventLogController::class, 'index'])->name('eventlogs');
        Route::get('/room_venue', [RoomVenueController::class, 'adminIndex'])->name('room_venue');

        /* ── Admin Only ── */
        Route::middleware(['role:admin'])->group(function () {
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
        Route::post('/accounts/{id}/update-status', [AccountController::class, 'updateStatus'])->name('accounts.updateStatus');
        // Graceful fallback: redirect stray GET requests back to the accounts list
        Route::get('/accounts/{id}/update-status', fn($id) => redirect()->route('employee.accounts'));
        Route::put('/accounts/{id}/update', [AccountController::class, 'update'])->name('employee.accounts.update');
        Route::post('/accounts/create', [AccountController::class, 'adminCreateAccount'])->name('accounts.create');
        // Revert a paid reservation back to unpaid — admin only
        Route::post('/reservations/{id}/mark-unpaid', [ReservationController::class, 'markAsUnpaid'])->name('reservations.markUnpaid');
        });
    });


/* ── Employee create-reservation workflow (Admin + Staff) ── */
Route::middleware(['role:admin,staff'])->group(function () {
    Route::get('/employee/create_reservation', [RoomVenueController::class, 'showAssignedAccomodation'])->name('showAssignedAccomodation');
    Route::get('/employee/search-accounts', [AccountController::class, 'searchAccounts']) ->name('employee.search_accounts');
    Route::get('/employee/create_food_reservation', [ReservationController::class, 'showEmployeeFoodReservation'])->name('employee.create_food_reservation');
    Route::post('employee/reservations/prepare', [ReservationController::class, 'prepareEmployeeBooking'])->name('employee.reservations.prepare');
    Route::post('employee/reservations/store', [ReservationController::class, 'storeReservation'])->name('employee.reservations.store');
    Route::get('/export-soa/{clientId}', [ReservationController::class, 'exportSOA'])->name('export.exportSOA');
    Route::get('/employee/calendar-data', [ReservationController::class, 'fetchUpdatedCalendarData'])->name('calendar.fetchUpdatedData');
    Route::get('/employee/calendar-export',     [ReservationController::class, 'exportCalendar'])->name('calendar.export');
    Route::get('/employee/calendar-export-pdf', [ReservationController::class, 'exportCalendarPDF'])->name('calendar.export.pdf');
    Route::get('/employee/calendar-export-csv', [ReservationController::class, 'exportCalendarCSV'])->name('calendar.export.csv');
    Route::get('/employee/analytics-report-data', [ReservationController::class, 'analyticsReportData'])->name('employee.analytics.report.data');
    // Cancellation request management (employee)
    Route::get('/employee/reservations/{id}/cancellation-request', [ReservationController::class, 'getCancellationRequest'])->name('employee.reservations.cancellationRequest');
    Route::post('/employee/cancellation-requests/{requestId}/process', [ReservationController::class, 'processCancellation'])->name('employee.cancellation.process');

    // Request for Changes management (employee)
    Route::get('/employee/reservations/{id}/change-request', [ReservationController::class, 'getChangeRequest'])->name('employee.reservations.changeRequest');
    Route::post('/employee/change-requests/{requestId}/process', [ReservationController::class, 'processChangeRequest'])->name('employee.change.process');

    /* ── Food Management Page (admin + staff can view; CRUD is admin-only) ── */
    Route::get('/employee/food', [FoodController::class, 'showFoodManagementPage'])->name('employee.food');

    /* ── Admin Only: Room / Venue / Food CRUD ── */
    Route::middleware(['role:admin'])->group(function () {
        Route::put('/employee/room-venue/update', [RoomVenueController::class, 'update'])->name('room_venue.update');
        Route::post('/employee/room_venue/store', [RoomVenueController::class, 'store'])->name('room_venue.store');
        // Individual food CRUD
        Route::post('/employee/food/store',        [FoodController::class, 'store'])->name('admin.food.store');
        Route::put('/employee/food/{id}',           [FoodController::class, 'update'])->name('admin.food.update');
        Route::delete('/employee/food/{id}/delete', [FoodController::class, 'destroy'])->name('admin.food.destroy');
        // Food Set CRUD
        Route::post('/employee/food-sets/store',         [FoodController::class, 'storeFoodSet'])->name('admin.food_set.store');
        Route::put('/employee/food-sets/{id}',            [FoodController::class, 'updateFoodSet'])->name('admin.food_set.update');
        Route::delete('/employee/food-sets/{id}/delete',  [FoodController::class, 'destroyFoodSet'])->name('admin.food_set.destroy');
    });
});

/* --- 3. Client Routes (logged-in clients only) --- */
Route::prefix('client')
    ->name('client.')
    ->middleware(['auth', 'role:client']) 
    ->group(function () {
       
        Route::get('/my_bookings', [ReservationController::class, 'checkout'])->name('my_bookings');
        Route::get('/my_reservations', [ReservationController::class, 'index'])->name('my_reservations');
        Route::get('/food_option', function () {
            return view('client.food_option'); // ✓ matches resources/views/client/food_option.blade.php
        })->name('food_option');
        
        Route::post('/reservations/{id}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

        // Cancellation request (new flow: client submits a request, admin approves/rejects)
        Route::post('/reservations/{id}/request-cancellation', [ReservationController::class, 'requestCancellation'])->name('reservations.requestCancellation');
        // Client checks their own cancellation request status
        Route::get('/reservations/{id}/cancellation-status', [ReservationController::class, 'getClientCancellationStatus'])->name('reservations.cancellationStatus');

        // Request for Changes — redirect-based flow mirroring checkout "Edit"
        // Step 1: Client clicks "Submit Request for Changes" → sets session + redirects to viewing page
        Route::post('/reservations/{id}/initiate-change', [ReservationController::class, 'initiateChangeRequest'])->name('reservations.initiateChange');
        // Step 2: Client submits booking form (from food_option or change_request_confirm) → saved as pending
        Route::post('/reservations/store-change-request', [ReservationController::class, 'storeChangeRequest'])->name('reservations.storeChangeRequest');

        // Account page
        Route::get('/account', [AccountController::class, 'showClientAccount'])->name('account');
        Route::put('/account', [AccountController::class, 'updateClientAccount'])->name('account.update');

        // In-system notifications
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/read-all',  [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    });


/* --- 4. Shared Auth Routes (any logged-in user) --- */

Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/checkout', [ReservationController::class, 'checkout'])->name('checkout');
    Route::post('/reservation/store', [ReservationController::class, 'store'])->name('reservation.store');
    Route::post('/checkout/remove', [ReservationController::class, 'removeFromCart'])->name('checkout.remove');
    Route::post('/checkout/edit', [ReservationController::class, 'editCartItem'])->name('checkout.edit');
    /* Client booking flow — needs auth but role check is loose
       (employee can create
       */
});