<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\AbanTether\Services\AbanTetherService;
use App\Modules\AbanTether\Services\AbanTetherServiceInterface;
use App\Modules\Admin\Services\AdminAuthService;
use App\Modules\Admin\Services\AdminAuthServiceInterface;
use App\Modules\Admin\Repositories\AdminRepositoryInterface;
use App\Modules\Admin\Repositories\EloquentAdminRepository;
use App\Modules\Admin\Services\AdminManagementService;
use App\Modules\Admin\Services\AdminManagementServiceInterface;
use App\Modules\Admin\Services\AdminDashboardService;
use App\Modules\Admin\Services\AdminDashboardServiceInterface;
use App\Modules\Admin\Services\AdminReportService;
use App\Modules\Admin\Services\AdminReportServiceInterface;
use App\Modules\Matching\Services\MatchingEngine;
use App\Modules\Matching\Services\MatchingEngineInterface;
use App\Modules\Orders\Repositories\EloquentOrderRepository;
use App\Modules\Orders\Repositories\OrderRepositoryInterface;
use App\Modules\Orders\Services\OrderService;
use App\Modules\Orders\Services\OrderServiceInterface;
use App\Modules\Positions\Repositories\EloquentPositionRepository;
use App\Modules\Positions\Repositories\PositionRepositoryInterface;
use App\Modules\Positions\Services\PositionService;
use App\Modules\Positions\Services\PositionServiceInterface;
use App\Modules\Settlement\Repositories\EloquentSettlementRepository;
use App\Modules\Settlement\Repositories\SettlementRepositoryInterface;
use App\Modules\Settlement\Services\SettlementService;
use App\Modules\Settlement\Services\SettlementServiceInterface;
use App\Modules\Trades\Repositories\EloquentTradeRepository;
use App\Modules\Trades\Repositories\TradeRepositoryInterface;
use App\Modules\Admin\Services\AdminDepositService;
use App\Modules\Admin\Services\AdminDepositServiceInterface;
use App\Modules\Admin\Services\AdminWithdrawalService;
use App\Modules\Admin\Services\AdminWithdrawalServiceInterface;
use App\Modules\Wallet\Repositories\EloquentDepositRepository;
use App\Modules\Wallet\Repositories\EloquentWalletRepository;
use App\Modules\Wallet\Repositories\DepositRepositoryInterface;
use App\Modules\Wallet\Repositories\WalletRepositoryInterface;
use App\Modules\Wallet\Repositories\EloquentWithdrawalRepository;
use App\Modules\Wallet\Repositories\WithdrawalRepositoryInterface;
use App\Modules\Wallet\Services\DepositService;
use App\Modules\Wallet\Services\DepositServiceInterface;
use App\Modules\Wallet\Services\WalletService;
use App\Modules\Wallet\Services\WalletServiceInterface;
use App\Modules\Wallet\Services\WithdrawalService;
use App\Modules\Wallet\Services\WithdrawalServiceInterface;
use App\Modules\Admin\Services\UserManagementService;
use App\Modules\Admin\Services\UserManagementServiceInterface;
use App\Modules\Auth\Repositories\EloquentUserRepository;
use App\Modules\Auth\Repositories\UserRepositoryInterface;
use App\Modules\Auth\Services\UserAuthService;
use App\Modules\Auth\Services\UserAuthServiceInterface;
use App\Modules\Markets\Repositories\CategoryRepositoryInterface;
use App\Modules\Markets\Repositories\EloquentCategoryRepository;
use App\Modules\Markets\Repositories\EloquentSubCategoryRepository;
use App\Modules\Markets\Repositories\SubCategoryRepositoryInterface;
use App\Modules\Markets\Repositories\EloquentEventRepository;
use App\Modules\Markets\Repositories\EventRepositoryInterface;
use App\Modules\Markets\Services\CategoryService;
use App\Modules\Markets\Services\CategoryServiceInterface;
use App\Modules\Markets\Services\EventService;
use App\Modules\Markets\Services\EventServiceInterface;
use App\Modules\Markets\Services\SubCategoryService;
use App\Modules\Markets\Services\SubCategoryServiceInterface;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AbanTetherServiceInterface::class, AbanTetherService::class);
        $this->app->bind(AdminAuthServiceInterface::class, AdminAuthService::class);
        $this->app->bind(AdminRepositoryInterface::class, EloquentAdminRepository::class);
        $this->app->bind(AdminManagementServiceInterface::class, AdminManagementService::class);
        $this->app->bind(UserAuthServiceInterface::class, UserAuthService::class);

        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(SubCategoryRepositoryInterface::class, EloquentSubCategoryRepository::class);
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(SubCategoryServiceInterface::class, SubCategoryService::class);

        $this->app->bind(EventRepositoryInterface::class, EloquentEventRepository::class);
        $this->app->bind(EventServiceInterface::class, EventService::class);

        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(UserManagementServiceInterface::class, UserManagementService::class);

        $this->app->bind(WalletRepositoryInterface::class, EloquentWalletRepository::class);
        $this->app->bind(WalletServiceInterface::class, WalletService::class);

        $this->app->bind(DepositRepositoryInterface::class, EloquentDepositRepository::class);
        $this->app->bind(DepositServiceInterface::class, DepositService::class);

        $this->app->bind(WithdrawalRepositoryInterface::class, EloquentWithdrawalRepository::class);
        $this->app->bind(WithdrawalServiceInterface::class, WithdrawalService::class);

        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);

        $this->app->bind(TradeRepositoryInterface::class, EloquentTradeRepository::class);
        $this->app->bind(PositionRepositoryInterface::class, EloquentPositionRepository::class);
        $this->app->bind(PositionServiceInterface::class, PositionService::class);
        $this->app->bind(SettlementRepositoryInterface::class, EloquentSettlementRepository::class);
        $this->app->bind(SettlementServiceInterface::class, SettlementService::class);
        $this->app->bind(MatchingEngineInterface::class, MatchingEngine::class);

        $this->app->bind(AdminDepositServiceInterface::class, AdminDepositService::class);
        $this->app->bind(AdminWithdrawalServiceInterface::class, AdminWithdrawalService::class);
        $this->app->bind(AdminReportServiceInterface::class, AdminReportService::class);
        $this->app->bind(AdminDashboardServiceInterface::class, AdminDashboardService::class);
    }

    public function boot(): void
    {
        Broadcast::routes(['middleware' => 'auth:users']);
    }
}
