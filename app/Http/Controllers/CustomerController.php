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
        try {
            DB::beginTransaction();
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

                //Sub Domain
                $subdomain = $request->subdomain . '.alldigi.biz';
                $cpanelHost = "alldigi.biz"; // Your main domain
                $cpanelUser = "alldxyrq";
                $cpanelToken = "B4O4FFOH5WM94YJAYNI7WN8YANPL9GXZ";
                $newSubdomainPath = "/home/{$cpanelUser}/{$subdomain}/public";

                // **1. Create the Subdomain using cPanel API**
                $response = Http::withHeaders([
                    'Authorization' => "cpanel $cpanelUser:$cpanelToken"
                ])->get("https://$cpanelHost:2083/execute/SubDomain/addsubdomain", [
                    'domain' => $subdomain, // Only the subdomain part (e.g., "blog")
                    'rootdomain' => 'alldigi.biz', // Your main domain
                    'dir' => '/home/alldxyrq/'.$subdomain.'/public', // Document root
                ]);
                
                if ($response->failed()) {
                    dd($response->body()); // Show error response
                }
                
                // dd($response->body());

                // **2. Copy and Extract Project Files**
                $path = "/home/{$cpanelUser}/{$subdomain}";
                $sourcePath = "/home/{$cpanelUser}/lms.alldigi.biz";
                $this->copyProjectFiles($sourcePath, $path);

                // // **3. Create Database and User**
                $dbName = "alldxyrq_".$request->subdomain;
                $dbUser = "alldxyrq_lms";
                $dbPass = "alldxyrq_lms";

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

                $db_import = Http::withHeaders([
                    'Authorization' => "cpanel cpaneluser:cpaneltoken"
                ])->get("https://yourcpanel.com:2083/execute/Mysql/import_database", [
                    'database' => $dbName,
                    'file' => '/home/'.$cpanelUser.'/lms.alldigi.biz/lms.sql', // Path to uploaded SQL file
                ]);
                if ($db_import->failed()) {
                    dd($db_import->body()); // Show error response
                }
                // // **4. Update .env file for the new project**
                $this->updateEnvFile($newSubdomainPath, $dbName, $dbUser, $dbPass);

            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->back()->with('error', $e);
        }
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

    // Create a new domain via cPanel API
    private function createDomain($cpanelUser, $cpanelToken, $subdomain, $domain, $path)
    {
        // API URL to create a subdomain
        $apiUrl = "https://{$domain}:2083/execute/DomainInfo/add_domain?domain={$subdomain}&dir={$path}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: cpanel {$cpanelUser}:{$cpanelToken}"]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($data['status'] === 1) {
            return true;
        } else {
            return false;
        }
    }

    // Copy and extract project files
    private function copyProjectFiles($sourcePath, $destinationPath)
    {
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true, true);
        }

        exec("cp -r {$sourcePath}/* {$destinationPath}");
        exec("unzip {$destinationPath}/project.zip -d {$destinationPath}");
        exec("mv {$destinationPath}/project/* {$destinationPath}/");
        exec("rm -rf {$destinationPath}/project {$destinationPath}/project.zip");
    }

    // Create a database, user, and grant privileges
    private function createDatabase($cpanelUser, $cpanelToken, $dbName, $dbUser, $dbPass)
    {
        // Create database
        $dbQuery = "https://alldigi.biz:2083/execute/Mysql/add_database?name={$dbName}";
        $this->executeCpanelRequest($cpanelUser, $cpanelToken, $dbQuery);

        // Create database user
        $userQuery = "https://alldigi.biz:2083/execute/Mysql/add_user?name={$dbUser}&password={$dbPass}";
        $this->executeCpanelRequest($cpanelUser, $cpanelToken, $userQuery);

        // Grant privileges
        $grantQuery = "https://alldigi.biz:2083/execute/Mysql/set_privileges_on_database?user={$dbUser}&database={$dbName}&privileges=ALL%20PRIVILEGES";
        $this->executeCpanelRequest($cpanelUser, $cpanelToken, $grantQuery);
    }

    // Execute cPanel API requests
    private function executeCpanelRequest($cpanelUser, $cpanelToken, $query)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: cpanel {$cpanelUser}:{$cpanelToken}"]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true)['status'] ?? false;
    }

    // Update .env file with new database credentials
    private function updateEnvFile($path, $dbName, $dbUser, $dbPass)
    {
        $envPath = "{$path}/.env";
        if (File::exists($envPath)) {
            $envContent = File::get($envPath);
            $envContent = preg_replace("/DB_DATABASE=.*/", "DB_DATABASE={$dbName}", $envContent);
            $envContent = preg_replace("/DB_USERNAME=.*/", "DB_USERNAME={$dbUser}", $envContent);
            $envContent = preg_replace("/DB_PASSWORD=.*/", "DB_PASSWORD={$dbPass}", $envContent);
            File::put($envPath, $envContent);
        }
    }
}
