<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\NonWorkingDayController;
use App\Http\Controllers\OrganisationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserActivityController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\UserController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => response()->json(new UserResource($request->user()->loadMissing('organisation'))));
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::get('/user-activities', [UserActivityController::class, 'index']);
    Route::get('/non-working-days', [NonWorkingDayController::class, 'index']);

    Route::get('/time-entries', [TimeEntryController::class, 'index']);
    Route::post('/time-entries', [TimeEntryController::class, 'store']);
    Route::put('/time-entries/{timeEntry}', [TimeEntryController::class, 'update']);
    Route::delete('/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy']);

    Route::middleware('admin')->group(function (): void {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::put('/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

        Route::get('/activities', [ActivityController::class, 'index']);
        Route::post('/activities', [ActivityController::class, 'store']);
        Route::put('/activities/{activity}', [ActivityController::class, 'update']);
        Route::delete('/activities/{activity}', [ActivityController::class, 'destroy']);

        Route::post('/non-working-days/sync', [NonWorkingDayController::class, 'sync']);
        Route::post('/non-working-days', [NonWorkingDayController::class, 'store']);
        Route::put('/non-working-days/{nonWorkingDay}', [NonWorkingDayController::class, 'update']);
        Route::delete('/non-working-days/{nonWorkingDay}', [NonWorkingDayController::class, 'destroy']);
    });

    Route::middleware('super_admin')->group(function (): void {
        Route::get('/organisations', [OrganisationController::class, 'index']);
        Route::post('/organisations', [OrganisationController::class, 'store']);
        Route::put('/organisations/{organisation}', [OrganisationController::class, 'update']);
        Route::delete('/organisations/{organisation}', [OrganisationController::class, 'destroy']);
    });
});
