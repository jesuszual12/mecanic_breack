<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkshopController;
use Illuminate\Support\Facades\Route;

Route::get('/configuracion/inicial', [AuthController::class, 'setup'])->name('setup');
Route::post('/configuracion/inicial', [AuthController::class, 'storeSetup'])->name('setup.store');
Route::get('/ingresar', [AuthController::class, 'loginForm'])->name('login');
Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::post('/salir', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [WorkshopController::class, 'index'])->name('dashboard');
    Route::post('/clientes', [WorkshopController::class, 'storeCustomer'])->name('customers.store');
    Route::post('/vehiculos', [WorkshopController::class, 'storeVehicle'])->name('vehicles.store');
    Route::get('/vehiculos/{vehicle}', [WorkshopController::class, 'showVehicle'])->name('vehicles.show');
    Route::post('/ordenes', [WorkshopController::class, 'storeWorkOrder'])->name('work-orders.store');
    Route::patch('/ordenes/{workOrder}/costos', [WorkshopController::class, 'updateWorkOrderFinancials'])->middleware('can:manage-finances')->name('work-orders.financials.update');
    Route::get('/ordenes/{workOrder}/nota', [WorkshopController::class, 'printReceipt'])->middleware('can:manage-finances')->name('work-orders.receipt');
    Route::post('/ordenes/{workOrder}/conceptos', [WorkshopController::class, 'storeWorkOrderItem'])->name('work-orders.items.store');
    Route::get('/ordenes/{workOrder}', [WorkshopController::class, 'showWorkOrder'])->name('work-orders.show');
    Route::patch('/ordenes/{workOrder}/estado', [WorkshopController::class, 'updateStatus'])->name('work-orders.status');
    Route::post('/ordenes/{workOrder}/pagos', [WorkshopController::class, 'storePayment'])->middleware('can:manage-finances')->name('payments.store');
    Route::post('/cotizaciones', [WorkshopController::class, 'storeQuote'])->middleware('can:manage-finances')->name('quotes.store');
    Route::patch('/cotizaciones/{quote}/autorizar', [WorkshopController::class, 'authorizeQuote'])->middleware('can:manage-finances')->name('quotes.authorize');
    Route::post('/cotizaciones/{quote}/convertir', [WorkshopController::class, 'convertQuote'])->middleware('can:manage-finances')->name('quotes.convert');
    Route::post('/convenios', [WorkshopController::class, 'storeDebtAccount'])->middleware('can:manage-finances')->name('debt-accounts.store');
    Route::post('/convenios/{debtAccount}/abonos', [WorkshopController::class, 'storeDebtPayment'])->middleware('can:manage-finances')->name('debt-accounts.payments.store');
    Route::post('/inventario', [WorkshopController::class, 'storeInventoryItem'])->middleware('can:manage-inventory')->name('inventory.store');
    Route::post('/inventario/{inventoryItem}/movimientos', [WorkshopController::class, 'storeInventoryMovement'])->middleware('can:manage-inventory')->name('inventory.movements.store');
    Route::post('/gastos', [WorkshopController::class, 'storeExpense'])->middleware('can:manage-finances')->name('expenses.store');
    Route::post('/empleados', [WorkshopController::class, 'storeEmployee'])->middleware('can:manage-users')->name('employees.store');
    Route::post('/empleados/{employee}/nomina', [WorkshopController::class, 'storePayrollPayment'])->middleware('can:manage-finances')->name('payroll.store');
});
