<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to(Auth::check() ? route('dashboard') : route('login'));
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('gastos', 'pages::expenses.index')->name('expenses.index');
    Route::livewire('categorias', 'pages::categories.index')->name('categories.index');
    Route::livewire('salario', 'pages::salary.index')->name('salary.index');
    Route::livewire('suscripciones', 'pages::subscriptions.index')->name('subscriptions.index');
    Route::livewire('cuentas-por-pagar', 'pages::bills.index')->name('bills.index');
    Route::livewire('ahorros', 'pages::savings.index')->name('savings.index');
    Route::livewire('metas', 'pages::goals.index')->name('goals.index');
    Route::livewire('reportes', 'pages::reports.index')->name('reports.index');
});

require __DIR__.'/settings.php';
