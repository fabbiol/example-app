<?php

use App\Http\Controllers\ActivitiesController;
use App\Http\Controllers\CrushingCircuitController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstimatedLoadingController;
use App\Http\Controllers\FlowController;
use App\Http\Controllers\LoaderOperatorController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionEntryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeighTicketController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('permission:loader')->prefix('loader')->name('loader.')->group(function () {
        Route::get('/', [LoaderOperatorController::class, 'index'])->name('index');
        Route::get('/orders/{order}', [LoaderOperatorController::class, 'show'])->name('show');
        Route::post('/orders/{order}/loadings', [LoaderOperatorController::class, 'store'])->name('store');
        Route::get('/done/{estimatedLoading}', [LoaderOperatorController::class, 'done'])->name('done');
    });

    Route::middleware('permission:dashboard')->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

    Route::middleware('permission:flow')->group(function () {
        Route::get('flow', FlowController::class)->name('flow');
    });

    Route::middleware('permission:activities')->group(function () {
        Route::get('activities', [ActivitiesController::class, 'index'])->name('activities.index');
    });

    Route::middleware('permission:products')->group(function () {
        Route::resource('products', ProductController::class);
    });

    Route::middleware('permission:customers')->group(function () {
        Route::resource('customers', CustomerController::class);
    });

    Route::middleware('permission:users')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::middleware('permission:roles')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::middleware('permission:orders')->group(function () {
        Route::resource('orders', OrderController::class);
    });

    Route::middleware('permission:trucks')->group(function () {
        Route::resource('trucks', TruckController::class)->except(['show']);
    });

    Route::middleware('permission:crushing-circuits')->group(function () {
        Route::get('crushing-circuits', [CrushingCircuitController::class, 'edit'])->name('crushing-circuits.edit');
        Route::put('crushing-circuits/{crushingCircuit}', [CrushingCircuitController::class, 'update'])->name('crushing-circuits.update');
    });

    Route::middleware('permission:weigh-tickets')->group(function () {
        Route::resource('weigh-tickets', WeighTicketController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy']);
    });

    Route::middleware('permission:estimated-loadings')->group(function () {
        Route::resource('estimated-loadings', EstimatedLoadingController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy']);
    });

    Route::middleware('permission:production')->group(function () {
        Route::resource('production', ProductionEntryController::class)
            ->parameters(['production' => 'productionEntry'])
            ->only(['index', 'create', 'store', 'destroy']);
    });
});

require __DIR__.'/settings.php';
