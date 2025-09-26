<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Plan;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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
        ini_set('max_execution_time', 0);
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
        // try {
        //     DB::beginTransaction();
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
                $expiry_date = Carbon::now()->addDays((int)$plan->days)->format('Y-m-d');
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
                $expiry_date = Carbon::now()->addDays((int)$plan->days)->format('Y-m-d');
                $obj['register_date'] = Carbon::now()->format('Y-m-d');
                $obj['expiry_date'] = $expiry_date;
                $customer = Customer::create($obj);

                //Sub Domain
                $subdomain = $request->subdomain . '.myskooly.com';
                $cpanelHost = "127.0.0.1"; // Your main domain
                $cpanelUser = "brighfrw";
                $cpanelToken = "QYJJMQG5XK5BXW812KHD4S6HJGAWYMT3";

                // **1. Create the Subdomain using cPanel API**
                $response = Http::withHeaders([
                    'Authorization' => "cpanel $cpanelUser:$cpanelToken"
                ])->get("https://$cpanelHost:2083/execute/SubDomain/addsubdomain", [
                    'domain' => $subdomain, // Only the subdomain part (e.g., "blog")
                    'rootdomain' => 'myskooly.com', // Your main domain
                    'dir' => '/home/brighfrw/' . $subdomain . '/public', // Document root
                ]);

                if ($response->failed()) {
                    dd($response->body()); // Show error response
                }

                // dd($response->body());

                // **2. Copy and Extract Project Files**
                $path = "/home/{$cpanelUser}/{$subdomain}";
                $sourcePath = "/home/{$cpanelUser}/myskooly.com";
                $this->copyProjectFiles($sourcePath, $path);

                // // **3. Create Database and User**
                $dbName = "brighfrw_" . $request->subdomain;
                $dbUser = "brighfrw_lms";
                $dbPass = "brighfrw_lms";

                $db_create = Http::withHeaders([
                    'Authorization' => "cpanel $cpanelUser:$cpanelToken"
                ])->get("https://$cpanelHost:2083/execute/Mysql/create_database", [
                    'name' => $dbName, // Database name (must include cPanel user prefix)
                ]);

                if ($db_create->failed()) {
                    dd($db_create->body()); // Show error response
                }

                $db_attach = Http::withHeaders([
                    'Authorization' => "cpanel $cpanelUser:$cpanelToken"
                ])->get("https://$cpanelHost:2083/execute/Mysql/set_privileges_on_database", [
                    'user' => $dbUser, // Database user
                    'database' => $dbName, // Database name
                    'privileges' => 'ALL PRIVILEGES', // Full access
                ]);

                if ($db_attach->failed()) {
                    dd($db_attach->body()); // Show error response
                }

                //Database Import

                $sqlFile = "/home/{$cpanelUser}/{$subdomain}/public/db/lms.sql";
                $importCommand = "mysql -u$dbUser -p'$dbPass' $dbName < $sqlFile";
                exec($importCommand, $output, $returnVar);

                // // **4. Update .env file for the new project**
                $envPath = "{$path}/.env";
                if (File::exists($envPath)) {
                    $envContent = File::get($envPath);
                    $envContent = preg_replace("/DB_DATABASE=.*/", "DB_DATABASE={$dbName}", $envContent);
                    $envContent = preg_replace("/DB_USERNAME=.*/", "DB_USERNAME={$dbUser}", $envContent);
                    $envContent = preg_replace("/DB_PASSWORD=.*/", "DB_PASSWORD={$dbPass}", $envContent);
                    File::put($envPath, $envContent);
                }


                //  ** 5. Composer update
                $projectPath = "/home/brighfrw/" . $subdomain;
                $composerPath = "/home/brighfrw/composer.phar"; // Update with your actual composer path

                exec("export HOME=/home/brighfrw && cd $projectPath && php $composerPath update 2>&1", $output, $returnVar);
            }
        //     DB::commit();
        // } catch (Exception $e) {
        //     DB::rollback();
        //     return redirect()->back()->with('error', $e->getMessage());
        // }
        return redirect('customer')->with('success', 'Customer created successfully! URL:https://{$subdomain}');

        // return redirect()->back()->with('error', 'Something went wrong!');
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

    //Helping function

    // Copy and extract project files
    private function copyProjectFiles($sourcePath, $destinationPath)
    {
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true, true);
            File::cleanDirectory($destinationPath);
        }
        File::cleanDirectory($destinationPath);

        // Copy all files, including hidden ones
        exec("cp -r {$sourcePath}/* {$sourcePath}/.* {$destinationPath}/ 2>/dev/null");

        // Unzip project.zip
        exec("unzip {$destinationPath}/project.zip -d {$destinationPath}");

        // Move extracted project files
        exec("mv {$destinationPath}/project/* {$destinationPath}/");
        exec("mv {$destinationPath}/project/.* {$destinationPath}/ 2>/dev/null");

        // Clean up extracted folder and zip file
        exec("rm -rf {$destinationPath}/project {$destinationPath}/project.zip");

        // Set proper permissions
        exec("find {$destinationPath} -type d -exec chmod 775 {} +");
        exec("find {$destinationPath} -type f -exec chmod 664 {} +");
    }
}
