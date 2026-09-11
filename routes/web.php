<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CashVoucherController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseAccountController;
use App\Http\Controllers\JournalVoucherController;
use App\Http\Controllers\PlatformAdminController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\SalesmanController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockCategoryController;
use App\Http\Controllers\StockItemController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('home');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'processLoginRequest']);
});

Route::middleware(['auth', 'platform'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/', [PlatformAdminController::class, 'home'])->name('home');
    Route::get('/organizations', [PlatformAdminController::class, 'organizations'])->name('organizations');
    Route::get('/organizations/{organization}', [PlatformAdminController::class, 'show'])->name('organizations.show');
    Route::post('/organizations/{organization}', [PlatformAdminController::class, 'update'])->name('organizations.update');
    Route::post('/organizations/{organization}/extend-trial', [PlatformAdminController::class, 'extendTrial'])->name('organizations.extend');
    Route::get('/users', [PlatformAdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/toggle', [PlatformAdminController::class, 'toggleUser'])->name('users.toggle');
});

Route::middleware(['auth', 'org-active', 'permission'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/invoices/{invoice}/issue', [SalesInvoiceController::class, 'issue'])->name('invoices.issue');
    Route::post('/invoices/{invoice}/pay', [SalesInvoiceController::class, 'pay'])->name('invoices.pay');
    Route::get('/invoices/{invoice}/print', [SalesInvoiceController::class, 'print'])->name('invoices.print');
    Route::resource('invoices', SalesInvoiceController::class);

    Route::get('/settings/company', [SettingsController::class, 'company'])->name('settings.company');
    Route::post('/settings/company', [SettingsController::class, 'updateCompany'])->name('settings.company.update');
    Route::post('/assistant/chat', [AssistantController::class, 'chat'])->name('assistant.chat');
    Route::post('/assistant/confirm', [AssistantController::class, 'confirm'])->name('assistant.confirm');
    Route::post('/assistant/cancel', [AssistantController::class, 'cancel'])->name('assistant.cancel');

    Route::get("logout", function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login')->with("success", "Logout Successfully");
    })->name("logout");

    Route::resource('users', UserController::class)->except(['show']);

    // Stock
    Route::resource('stock-categories', StockCategoryController::class);
    Route::resource('stock-items', StockItemController::class);
    Route::get('purchase-orders/{purchase_order}/print', [PurchaseOrderController::class, 'print'])
        ->name('purchase-orders.print');
    Route::resource('purchase-orders', PurchaseOrderController::class);

    // Accounts
    Route::resource('customers', CustomerController::class);
    Route::resource('salesmen', SalesmanController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('bank-accounts', BankAccountController::class);
    Route::get('bank-accounts/{bankAccount}/balance', [BankAccountController::class, 'balance'])->name('bank-accounts.balance');
    Route::resource('expense-accounts', ExpenseAccountController::class);

    // Cash (separate module)
    Route::get('cash-vouchers/{cash_voucher}/print', [CashVoucherController::class, 'print'])
        ->name('cash-vouchers.print');
    Route::resource('cash-vouchers', CashVoucherController::class);
    Route::resource('journal-vouchers', JournalVoucherController::class);

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/party-ledger/accounts', [ReportController::class, 'partyLedgerAccounts'])->name('reports.party-ledger.accounts');
    Route::get('reports/party-ledger', [ReportController::class, 'partyLedger'])->name('reports.party-ledger');
    Route::get('reports/cash-register', [ReportController::class, 'cashRegister'])->name('reports.cash-register');
    Route::get('reports/journal-report', [ReportController::class, 'journalReport'])->name('reports.journal-report');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
});
