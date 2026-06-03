<?php

declare(strict_types=1);

use App\Modules\Admin\Http\Controllers\AdminAuthController;
use App\Modules\Admin\Http\Controllers\AdminDashboardController;
use App\Modules\Admin\Http\Controllers\AdminDepositController;
use App\Modules\Admin\Http\Controllers\AdminReportController;
use App\Modules\Admin\Http\Controllers\AdminWithdrawalController;
use App\Modules\Admin\Http\Controllers\UserController;
use App\Modules\Auth\Http\Controllers\UserAuthController;
use App\Modules\Markets\Http\Controllers\CategoryController;
use App\Modules\Markets\Http\Controllers\EventController;
use App\Modules\Markets\Http\Controllers\SubCategoryController;
use App\Modules\Orders\Http\Controllers\OrderController;
use App\Modules\Positions\Http\Controllers\PositionController;
use App\Modules\Wallet\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {

        // Auth (public)
        Route::prefix('auth')->group(function () {
            Route::post('login', [AdminAuthController::class, 'login']);

            Route::middleware('auth:admins')->group(function () {
                Route::post('logout', [AdminAuthController::class, 'logout']);
                Route::get('me',     [AdminAuthController::class, 'me']);
            });
        });

        // Protected admin routes
        Route::middleware('auth:admins')->group(function () {

            // Categories
            Route::apiResource('categories', CategoryController::class)
                ->parameters(['categories' => 'ulid']);

            // Sub-categories nested under category (index + store)
            Route::get('categories/{ulid}/sub-categories',  [SubCategoryController::class, 'index']);
            Route::post('categories/{ulid}/sub-categories', [SubCategoryController::class, 'store']);

            // Sub-categories top-level (update + delete)
            Route::put('sub-categories/{ulid}',    [SubCategoryController::class, 'update']);
            Route::delete('sub-categories/{ulid}', [SubCategoryController::class, 'destroy']);

            // Events
            Route::apiResource('events', EventController::class)
                ->parameters(['events' => 'ulid']);

            // Event status transitions
            Route::put('events/{ulid}/open',   [EventController::class, 'open']);
            Route::put('events/{ulid}/close',  [EventController::class, 'close']);
            Route::put('events/{ulid}/settle', [EventController::class, 'settle']);

            // Event sub-resources
            Route::get('events/{ulid}/orders',    [EventController::class, 'orders']);
            Route::get('events/{ulid}/trades',    [EventController::class, 'trades']);
            Route::get('events/{ulid}/positions', [EventController::class, 'positions']);

            // Dashboard
            Route::get('dashboard', [AdminDashboardController::class, 'index']);

            // Deposits
            Route::get('deposits',                    [AdminDepositController::class, 'index']);
            Route::get('deposits/{ulid}',             [AdminDepositController::class, 'show']);
            Route::put('deposits/{ulid}/confirm',     [AdminDepositController::class, 'confirm']);
            Route::put('deposits/{ulid}/reject',      [AdminDepositController::class, 'reject']);

            // Withdrawals
            Route::get('withdrawals',                 [AdminWithdrawalController::class, 'index']);
            Route::get('withdrawals/{ulid}',          [AdminWithdrawalController::class, 'show']);
            Route::put('withdrawals/{ulid}/approve',  [AdminWithdrawalController::class, 'approve']);
            Route::put('withdrawals/{ulid}/reject',   [AdminWithdrawalController::class, 'reject']);

            // Reports
            Route::prefix('reports')->group(function () {
                Route::get('fees',     [AdminReportController::class, 'fees']);
                Route::get('trades',   [AdminReportController::class, 'trades']);
                Route::get('wallets',  [AdminReportController::class, 'wallets']);
                Route::get('balances', [AdminReportController::class, 'balances']);
                Route::get('volume',   [AdminReportController::class, 'volume']);
            });

            // Users
            Route::get('users',                       [UserController::class, 'index']);
            Route::get('users/{ulid}',                [UserController::class, 'show']);
            Route::put('users/{ulid}/activate',       [UserController::class, 'activate']);
            Route::put('users/{ulid}/deactivate',     [UserController::class, 'deactivate']);
            Route::get('users/{ulid}/wallet',         [UserController::class, 'wallet']);
            Route::get('users/{ulid}/transactions',   [UserController::class, 'transactions']);
            Route::get('users/{ulid}/deposits',       [UserController::class, 'deposits']);
            Route::get('users/{ulid}/positions',      [UserController::class, 'positions']);
            Route::get('users/{ulid}/orders',         [UserController::class, 'orders']);

        });

    });

    /*
    |--------------------------------------------------------------------------
    | Public category routes (no auth)
    |--------------------------------------------------------------------------
    */
    Route::get('categories', [CategoryController::class, 'publicIndex']);

    /*
    |--------------------------------------------------------------------------
    | Public event routes (no auth)
    |--------------------------------------------------------------------------
    */
    Route::prefix('events')->group(function () {
        Route::get('/',                    [EventController::class, 'index']);
        Route::get('{ulid}',               [EventController::class, 'show']);
        Route::get('{ulid}/orderbook',     [EventController::class, 'orderbook']);
        Route::get('{ulid}/trades',        [EventController::class, 'trades']);
    });

    /*
    |--------------------------------------------------------------------------
    | User routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('register', [UserAuthController::class, 'register']);
        Route::post('login',    [UserAuthController::class, 'login']);

        Route::middleware('auth:users')->group(function () {
            Route::post('logout', [UserAuthController::class, 'logout']);
            Route::get('me',      [UserAuthController::class, 'me']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Wallet routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('wallet')->middleware('auth:users')->group(function () {
        Route::get('balance',          [WalletController::class, 'balance']);
        Route::get('transactions',     [WalletController::class, 'transactions']);
        Route::get('deposit-address',  [WalletController::class, 'depositAddress']);
        Route::get('withdrawal-fee',   [WalletController::class, 'withdrawalFee']);
        Route::post('deposit',         [WalletController::class, 'deposit']);
        Route::post('deposits',        [WalletController::class, 'deposit']);
        Route::post('withdraw',        [WalletController::class, 'withdraw']);
        Route::post('withdrawals',     [WalletController::class, 'withdraw']);
    });

    /*
    |--------------------------------------------------------------------------
    | Order routes (auth required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:users')->group(function () {
        Route::get('orders',           [OrderController::class, 'index']);
        Route::post('orders',          [OrderController::class, 'store']);
        Route::delete('orders/{ulid}', [OrderController::class, 'destroy']);

        Route::get('positions', [PositionController::class, 'index']);
    });

});
