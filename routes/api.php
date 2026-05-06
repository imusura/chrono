<?php

use App\Http\Controllers\Admin\ApiClientController as AdminApiClientController;
use App\Http\Controllers\Admin\TicketStatusController as AdminTicketStatusController;
use App\Http\Controllers\Admin\TicketTypeController as AdminTicketTypeController;
use App\Http\Controllers\Admin\TicketTypeFieldController;
use App\Http\Controllers\Admin\TicketTypeStatusController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TicketAttachmentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketStatusController;
use App\Http\Controllers\TicketTypeController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ResolveProject;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', RegisterController::class)->middleware('throttle:10,1');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'reset']);

// External integrations (token-authenticated). Versioned: breaking changes go under v2/.
Route::middleware(['api.client', 'throttle:api-client'])
    ->prefix('integrations/v1')
    ->group(function (): void {
        Route::post('/tickets', [App\Http\Controllers\Integrations\V1\TicketController::class, 'store']);
    });

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => response()->json(new UserResource($request->user())));
    Route::post('/logout', [LoginController::class, 'logout']);

    // Projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/templates', [ProjectController::class, 'templates']);
    Route::post('/projects', [ProjectController::class, 'store']);

    // Project-scoped routes
    Route::prefix('/projects/{project:slug}')->middleware(ResolveProject::class)->group(function (): void {
        // Ticket config (read)
        Route::get('/ticket-types', [TicketTypeController::class, 'index']);
        Route::get('/ticket-types/{ticketType}', [TicketTypeController::class, 'show']);
        Route::get('/ticket-statuses', [TicketStatusController::class, 'index']);

        // Users (project members)
        Route::get('/users', [UserController::class, 'index']);

        // Tickets
        Route::get('/tickets/board', [TicketController::class, 'board']);
        Route::apiResource('tickets', TicketController::class);
        Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign']);
        Route::patch('/tickets/{ticket}/close', [TicketController::class, 'close']);
        Route::patch('/tickets/{ticket}/reopen', [TicketController::class, 'reopen']);

        Route::post('/tickets/{ticket}/comments', [CommentController::class, 'store']);

        Route::post('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store']);
        Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'show']);
        Route::delete('/tickets/{ticket}/attachments/{attachment}', [TicketAttachmentController::class, 'destroy']);

        // Admin routes (project admin only)
        Route::middleware('can:manageProjectConfig,project')->prefix('admin')->group(function (): void {
            Route::get('/ticket-types', [AdminTicketTypeController::class, 'index']);
            Route::post('/ticket-types', [AdminTicketTypeController::class, 'store']);
            Route::put('/ticket-types/{ticketType}', [AdminTicketTypeController::class, 'update']);
            Route::delete('/ticket-types/{ticketType}', [AdminTicketTypeController::class, 'destroy']);

            Route::get('/ticket-statuses', [AdminTicketStatusController::class, 'index']);
            Route::post('/ticket-statuses', [AdminTicketStatusController::class, 'store']);
            Route::put('/ticket-statuses/{ticketStatus}', [AdminTicketStatusController::class, 'update']);
            Route::delete('/ticket-statuses/{ticketStatus}', [AdminTicketStatusController::class, 'destroy']);

            Route::put('/ticket-types/{ticketType}/statuses', [TicketTypeStatusController::class, 'update']);

            Route::put('/ticket-types/{ticketType}/fields/reorder', [TicketTypeFieldController::class, 'reorder']);
            Route::post('/ticket-types/{ticketType}/fields', [TicketTypeFieldController::class, 'store']);
            Route::put('/ticket-types/{ticketType}/fields/{field}', [TicketTypeFieldController::class, 'update']);
            Route::delete('/ticket-types/{ticketType}/fields/{field}', [TicketTypeFieldController::class, 'destroy']);

            // Member management
            Route::get('/members', [ProjectController::class, 'members']);
            Route::post('/members', [ProjectController::class, 'addMember']);
            Route::put('/members/{user}', [ProjectController::class, 'updateMember']);
            Route::delete('/members/{user}', [ProjectController::class, 'removeMember']);

            // Project settings
            Route::put('/settings', [ProjectController::class, 'update']);
            Route::delete('/delete', [ProjectController::class, 'destroy']);

            // API clients (external integrations)
            Route::get('/api-clients', [AdminApiClientController::class, 'index']);
            Route::post('/api-clients', [AdminApiClientController::class, 'store']);
            Route::put('/api-clients/{apiClient}', [AdminApiClientController::class, 'update']);
            Route::post('/api-clients/{apiClient}/rotate', [AdminApiClientController::class, 'rotate']);
            Route::delete('/api-clients/{apiClient}', [AdminApiClientController::class, 'destroy']);
        });
    });
});
