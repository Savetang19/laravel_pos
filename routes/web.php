<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesLineItemController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Models\Member;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return Redirect::to('dashboard');
});

Route::get('/dashboard', function (Request $request) {
    $sale = SaleController::get_latest_sale_by_employee($request);

    if($sale == null) {
        return Redirect::route('sale.create');
    }
    elseif($sale->payment->payment_type == 'pending') {
        return Redirect::route('payment');
    }
    
    return view('dashboard')->with('sale', $sale)
                            ->with('total', array_sum($sale->sales_line_item()->pluck('total')->all()))
                            ->with('sale_items', $sale->sales_line_item()->get())
                            ->with('stock', ItemController::get_available_items());
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/confirm_pay', function (Request $request) {
    $sale = SaleController::get_latest_sale_by_employee($request);
    if($sale->payment->payment_type == 'unfinished'){
        SaleController::update_payment_status('pending', $sale->id);
    }
    $sale = SaleController::get_latest_sale_by_employee($request);
    $member = $sale->payment->member;
    return view('payment')->with('total_price', $sale->payment->total)->with('sale_id', $sale->id)->with('member', $member);
})->middleware(['auth', 'verified'])->name('payment');

Route::get('/stock', function (Request $request) {

    $stock = ItemController::get_all_items();
    
    return view('stock')->with('stock', $stock);
})->middleware(['auth', 'verified'])->name('stock');

Route::get('/member', function (Request $request) {
    return view('member')->with('members', MemberController::get_all_members());
})->middleware(['auth', 'verified'])->name('member');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/item', [ItemController::class, 'create'])->name('item.create');
    Route::patch('/item', [ItemController::class, 'update'])->name('item.update');
    Route::delete('/item', [ItemController::class, 'destroy'])->name('item.destroy');

    Route::get('/sale', [SaleController::class, 'create'])->name('sale.create');
    Route::patch('/sale', [SaleController::class, 'confirm'])->name('sale.confirm');
    Route::post('/add_sale_line_item', [SaleController::class, 'add_sale_line_item'])->name('sale.add_sale_line_item');
    Route::delete('/delete_sale_line_item', [SaleController::class, 'delete_sale_line_item'])->name('sale.delete_sale_line_item');
    Route::patch('/payment', [SaleController::class, 'update_payment'])->name('sale.update_payment');

    Route::post('/member', [MemberController::class, 'create'])->name('member.create');
});

require __DIR__.'/auth.php';
