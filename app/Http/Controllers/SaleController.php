<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Redirect;

use Illuminate\Validation\Validator;
use Illuminate\View\View;

use Illuminate\Validation\Rule;

use App\Models\SalesLineItem;
use App\Models\Item;
use App\Models\Sale;
use App\Models\Payment;

class SaleController extends Controller
{
    public static function get_latest_sale_by_employee(Request $request) {
        return Sale::where('employee_id', '=', $request->user()->id)->orderBy('id', 'DESC')->first();
    }

    public static function update_sale_payment_status(string $status, int $sale_id) {
        $sale = Sale::where('id', '=', $sale_id)->first();
        $payment = $sale->payment;

        $payment->total = array_sum($sale->sales_line_item()->pluck('total')->all());
        $payment->payment_type = $status;
        $payment->save();
    }

    public static function create_blank_payment(int $id)
    {
        $new_payment = new Payment;
        $new_payment->sale_id = $id;
        $new_payment->total = 0;
        $new_payment->member_id = 1;
        $new_payment->save();

        return;
    }

    public function create(Request $request)
    {
        $sale = new Sale;
        $sale->employee_id = $request->user()->id;
        $sale->save();

        $this->create_blank_payment($sale->id);

        return Redirect::route('dashboard')->with('status', 'Sale created.');
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'pay_confirm' => 'required',
        ], [
            'pay_confirm.required' => 'Please check the box for confirmation first.',
        ]);
        
        $sale = Sale::where('id', '=', $request->sale_id)->first();
        $sale->payment->payment_type = $request->payment;

        $sale->payment->save();
        $sale->save();

        $bought_items = $sale->sales_line_item()->get();

        foreach($bought_items as $item) {
            $item->item->decrement('stock', $item->quantity);
        }

        return Redirect::route('sale.create');
    }

    public function destroy(Request $request)
    {
        return "confirm";
    }

    public function addItem(Request $request): RedirectResponse
    {
        $request->validate([
            'item_id' => [Rule::In(Item::where('id', '=', $request->item_id)->pluck('id')->all())],
            'quantity' => 'required|numeric|max:'.Item::where('id', '=', $request->item_id)->pluck('stock')->first()
        ], [
            'item_id.in' => 'Please select a viable item option.',
            'quantity.max' => 'Quantity exceeds the available stock: '.Item::where('id', '=', $request->item_id)->pluck('stock')->first()
        ]);

        $sales_line_item = SalesLineItem::where('sale_id', '=', $request->sale_id)->where('item_id', '=', $request->item_id)->first();
        if($sales_line_item != null) {
            $sales_line_item->increment('quantity', $request->quantity);
            $sales_line_item->increment('total', ($request->quantity * $sales_line_item->item->price));
        }
        else {
            $new_salesLineItem = new SalesLineItem;

            $new_salesLineItem->sale_id = $request->sale_id;
            $new_salesLineItem->item_id = $request->item_id;
            $new_salesLineItem->quantity = $request->quantity;

            $new_salesLineItem->save();
            
            $new_salesLineItem->increment('total', ($new_salesLineItem->quantity * $new_salesLineItem->item->price));
        }

        return Redirect::route('dashboard')->with('status', 'Item added.');
    }

    public function deleteItem(Request $request): RedirectResponse
    {
        $sales_line_item = SalesLineItem::where('id', '=', $request->id)->first();
        $sales_line_item->delete();
        return Redirect::route('dashboard')->with('status', 'Item removed.');
    }

    public function updatePayment(Request $request)
    {
        $member = MemberController::find_one_member_by_phone($request->phone);
        if($member == null or $request->phone == "99999") {
            $request->flash();
            return Redirect::route('payment')->with('status', 'Phone number not found.');
        }
        $payment = Payment::where('sale_id', '=', $request->sale_id)->first();
        $payment->member_id = $member->id;
        $payment->total = $payment->total * 0.9;
        $payment->save();

        return Redirect::route('payment')->with('status', 'Member added.');
    }

    public function destroyPayment(Request $request)
    {
        return "confirm";
    }
}
