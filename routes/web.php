<?php

use App\Http\Controllers\Admin\CompanyProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HandoverController;
use App\Http\Controllers\Admin\KnowledgeItemController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\AiStaffChatController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CompanySiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CompanySiteController::class, 'show'])->name('home');

Route::prefix('ai-staff')->name('ai-staff.')->group(function () {
    Route::post('start', [AiStaffChatController::class, 'start'])->middleware('throttle:10,1')->name('start');
    Route::post('message', [AiStaffChatController::class, 'message'])->middleware('throttle:20,1')->name('message');
    Route::post('consultation', [AiStaffChatController::class, 'consultation'])->middleware('throttle:10,1')->name('consultation');
    Route::get('history', [AiStaffChatController::class, 'history'])->middleware('throttle:60,1')->name('history');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('company', [CompanyProfileController::class, 'edit'])->name('company.edit');
        Route::put('company', [CompanyProfileController::class, 'update'])->name('company.update');

        Route::resource('services', ServiceController::class)->except('show');
        Route::resource('projects', ProjectController::class)->except('show');
        Route::resource('knowledge-items', KnowledgeItemController::class)->except('show');

        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status');

        Route::get('handovers', [HandoverController::class, 'index'])->name('handovers.index');
        Route::get('handovers/{handover}', [HandoverController::class, 'show'])->name('handovers.show');
        Route::post('handovers/{handover}/reply', [HandoverController::class, 'reply'])->name('handovers.reply');
        Route::post('handovers/{handover}/resolve', [HandoverController::class, 'resolve'])->name('handovers.resolve');
    });
});
