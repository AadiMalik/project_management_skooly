<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $monthly_earnings = Customer::whereMonth('created_at', Carbon::now()->month)
            ->with('plan')
            ->get()
            ->sum(fn($customer) => $customer->plan->price ?? 0);
        $yearly_earnings = Customer::whereYear('created_at', Carbon::now()->year)
            ->with('plan')
            ->get()
            ->sum(fn($customer) => $customer->plan->price ?? 0);
        $total_customer = Customer::count();
        $total_plan = Plan::count();


        $monthlyEarnings = DB::table('customers')
            ->join('plans', 'customers.plan_id', '=', 'plans.id')
            ->selectRaw('MONTH(customers.created_at) as month, SUM(plans.price) as total_earnings')
            ->whereYear('customers.created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_earnings', 'month');
        $line_labels = [];
        $line_data = [];

        for ($i = 1; $i <= 12; $i++) {
            $line_labels[] = date("F", mktime(0, 0, 0, $i, 1)); // Convert month number to name
            $line_data[] = $monthlyEarnings[$i] ?? 0; // Get earnings or default to 0
        }


        $plans = DB::select("
    SELECT p.name, COUNT(c.id) AS customers_count
    FROM plans p
    LEFT JOIN customers c ON p.id = c.plan_id
    GROUP BY p.id, p.name
    ORDER BY customers_count DESC
");

        $pai_labels = array_column($plans, 'name'); // Extract plan names
        $pai_data = array_column($plans, 'customers_count'); // Extract customer counts

        return view('home', compact(
            'monthly_earnings',
            'yearly_earnings',
            'total_customer',
            'total_plan',
            'line_labels',
            'line_data',
            'pai_labels',
            'pai_data'
        ));
    }
}
