<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customers = Customer::all();
        return view('customer.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $plans = Plan::where('is_active', 1)->get();
        return view('customer.create', compact('plans'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required',
            'email'            => [
                'required',
                'string',
                'max:190',
                Rule::unique('customers')->ignore($request->id)->whereNull('deleted_at')
            ],
            'phone_no'         => [
                'required',
                'string',
                'max:190',
                Rule::unique('customers')->ignore($request->id)->whereNull('deleted_at')
            ],
            'subdomain'        => [
                'required',
                'string',
                'max:190',
                Rule::unique('customers')->ignore($request->id)->whereNull('deleted_at')
            ],
            'plan_id'          => 'required'
        ]);
        if ($request->id != '' && $request->id != null) {
            $obj = [
                "name" => $request->name ?? '',
                "email" => $request->email ?? '',
                "phone_no" => $request->phone_no ?? '',
                "subdomain" => $request->subdomain ?? '',
                "plan_id" => $request->plan_id ?? null,
                "updatedby_id" => Auth::user()->id,
            ];
            $plan = Plan::find($request->plan_id);
            $expiry_date = Carbon::now()->addDays($plan->days)->format('Y-m-d');
            $obj['expiry_date'] = $expiry_date;
            // Find the record first
            $customer = Customer::find($request->id);

            if ($customer) {
                $customer->update($obj);
                return redirect('customer')->with('success', 'customer updated successfully!');
            }

            return redirect()->back()->with('error', 'Something went wrong!');
        } else {
            $obj = [
                "name" => $request->name ?? '',
                "email" => $request->email ?? '',
                "phone_no" => $request->phone_no ?? '',
                "subdomain" => $request->subdomain ?? '',
                "plan_id" => $request->plan_id ?? null,
                "createdby_id" => Auth::user()->id,
            ];
            $plan = Plan::find($request->plan_id);
            $expiry_date = Carbon::now()->addDays($plan->days)->format('Y-m-d');
            $obj['expiry_date'] = $expiry_date;
            $customer = Customer::create($obj);
            if ($customer)
                return redirect('customer')->with('success', 'Customer created successfully!');

            return redirect()->back()->with('error', 'Something went wrong!');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = Customer::find($id);
        $plans = Plan::where('is_active', 1)->get();
        return view('customer.create', compact('customer', 'plans'));
    }

    /**
     * Update status the specified resource in storage.
     */
    public function status($id)
    {
        $customer = Customer::find($id);
        if ($customer->is_active == 1) {
            $customer->is_active = 0;
        } else {
            $customer->is_active = 1;
        }
        $customer->updatedby_id = Auth::user()->id;
        $customer->update();

        return response()->json([
            'success' => true,
            'message' => 'Customer status updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);
        $customer->deletedby_id = Auth::user()->id;
        $customer->update();

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully!'
        ]);
    }
}
