<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $plans = Plan::all();
        return view('plan.index', compact('plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('plan.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'              => [
                'required',
                'string',
                'max:190',
                Rule::unique('plans')->ignore($request->id)->whereNull('deleted_at')
            ],
            'price'             => 'required',
            'days'              => 'required|integer'
        ]);
        if ($request->id != '' && $request->id != null) {
            $obj = [
                "name" => $request->name ?? '',
                "price" => $request->price ?? 0,
                "days" => $request->days ?? 0,
                "updatedby_id" => Auth::user()->id,
            ];
            // Find the record first
            $plan = Plan::find($request->id);

            if ($plan) {
                $plan->update($obj);
                return redirect('plan')->with('success', 'Plan updated successfully!');
            }

            return redirect()->back()->with('error', 'Something went wrong!');
        } else {
            $obj = [
                "name" => $request->name ?? '',
                "price" => $request->price ?? 0,
                "days" => $request->days ?? 0,
                "createdby_id" => Auth::user()->id,
            ];
            $plan = Plan::create($obj);
            if ($plan)
                return redirect('plan')->with('success', 'Plan created successfully!');

            return redirect()->back()->with('error', 'Something went wrong!');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $plan = Plan::find($id);
        return view('plan.create', compact('plan'));
    }

    /**
     * Update status the specified resource in storage.
     */
    public function status($id)
    {
        $plan = Plan::find($id);
        if ($plan->is_active == 1) {
            $plan->is_active = 0;
        } else {
            $plan->is_active = 1;
        }
        $plan->updatedby_id = Auth::user()->id;
        $plan->update();

        return response()->json([
            'success' => true,
            'message' => 'Plan status updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $plan = Plan::find($id);
        $plan->deletedby_id = Auth::user()->id;
        $plan->update();

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully!'
        ]);
    }
}
