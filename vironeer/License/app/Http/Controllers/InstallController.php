<?php

namespace Vironeer\License\App\Http\Controllers;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Str;
use Validator;
use Vironeer\License\App\Http\Traits\handlerTrait;
use Vironeer\System;

class InstallController extends Controller
{
    use handlerTrait;

    public function redirect()
    {
        return redirect()->route('install.requirements');
    }

    public function requirements()
    {
        if (env('VR_REQUIREMENTS')) {return redirect()->route('install.permissions');}
        $error = false;
        if (in_array(false, $this->extensionsArray())) {$error = true;}
        return view('vironeer::requirements', ['extensions' => phpExtensions(), 'error' => $error]);
    }

    public function requirementsAction(Request $request)
    {
        if (in_array(false, $this->extensionsArray())) {return redirect()->route('install.requirements');}
        Artisan::call('key:generate');
        setEnv('APP_ENV', 'production');
        setEnv('VR_REQUIREMENTS', 1);
        return redirect()->route('install.permissions');
    }

    public function permissions()
    {
        if (env('VR_FILE_PERMISSIONS')) {return redirect()->route('install.license');}
        if (!env('VR_REQUIREMENTS')) {return redirect()->route('install.requirements');}
        $error = false;
        if (in_array(false, $this->permissionsArray())) {$error = true;}
        return view('vironeer::permissions', ['permissions' => filePermissions(), 'error' => $error]);
    }

    public function permissionsAction(Request $request)
    {
        if (in_array(false, $this->permissionsArray())) {return redirect()->route('install.permissions');}
        setEnv('VR_FILE_PERMISSIONS', 1);
        return redirect()->route('install.license');
    }

    public function license()
    {
        if (env('VR_LICENSE')) {return redirect()->route('install.database.details');}
        if (!env('VR_FILE_PERMISSIONS')) {return redirect()->route('install.requirements');}
        return view('vironeer::license');
    }

    public function licenseAction(Request $request)
    {
		setEnv('LICENSE_TYPE', 1);
		setEnv('VR_LICENSE', 1);
		return redirect()->route('install.database.details');
    }

    public function databaseDetails()
    {
        if (env('VR_DATABASE_INFO')) {return redirect()->route('install.database.import');}
        if (!env('VR_LICENSE')) {return redirect()->route('install.license');}
        return view('vironeer::database.details');
    }

    public function databaseDetailsAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_host' => ['required', 'string'],
            'db_name' => ['required', 'string'],
            'db_user' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if (str_contains($request->db_host, '#') || str_contains($request->db_name, '#') || str_contains($request->db_user, '#')) {
            return redirect()->back()->withErrors(['Database details cannot contain a hashtag #'])->withInput();
        }
        try {
            if (!function_exists('curl_version')) {
                return redirect()->back()->withErrors(['CURL does not exist in server'])->withInput();
            }
            if (!is_writable(base_path('.env'))) {
                return redirect()->back()->withErrors(['.ENV file is not writable'])->withInput();
            }
            if (!@mysqli_connect($request->db_host, $request->db_user, $request->db_pass, $request->db_name)) {
                return redirect()->back()->withErrors(['Incorrect database details'])->withInput();
            }
            setEnv('DB_HOST', $request->db_host);
            setEnv('DB_DATABASE', $request->db_name);
            setEnv('DB_USERNAME', $request->db_user);
            setEnv('DB_PASSWORD', $request->db_pass, true);
            setEnv('VR_DATABASE_INFO', 1);
            return redirect()->route('install.database.import');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([$e->getMessage()])->withInput();
        }
    }

    public function databaseImport()
    {
        if (env('VR_DATABASE_IMPORT')) {return redirect()->route('install.complete');}
        if (!env('VR_DATABASE_INFO')) {return redirect()->route('install.database.details');}
        return view('vironeer::database.import');
    }

    public function databaseImportAction(Request $request)
    {
        if (!file_exists(base_path('database/sql/data.sql'))) {
            return redirect()->back()->withErrors(['SQL file is missing ' . base_path('database/sql/data.sql')])->withInput();
        }
        try {
            DB::connection()->getPdo();
            if (DB::connection()->getDatabaseName()) {
                $sql = base_path('database/sql/data.sql');
                $import = DB::unprepared(file_get_contents($sql));
                if ($import) {
                    setEnv('VR_DATABASE_IMPORT', 1);
                    return redirect()->route('install.complete');
                }
            } else {
                return redirect()->back()->withErrors(['Could not find the database. Please check your configuration.']);
            }
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([$e->getMessage()]);
        }
    }

    public function databaseImportDownload(Request $request)
    {
        $sql = base_path('database/sql/data.sql');
        if (!file_exists($sql)) {
            return redirect()->back()->withErrors(['SQL file is missing ' . base_path('database/sql/data.sql')])->withInput();
        }
        return response()->download($sql);
    }

    public function databaseImportSkip(Request $request)
    {
        setEnv('VR_DATABASE_IMPORT', 1);
        return redirect()->route('install.complete');
    }

    public function complete()
    {
        if (!env('VR_DATABASE_IMPORT')) {return redirect()->route('install.database.import');}
        return view('vironeer::complete');
    }

    public function completeAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'website_name' => ['required', 'string', 'max:200'],
            'website_url' => ['required', 'url'],
            'email' => ['required', 'string', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if (str_contains($request->website_url, '#')) {
            return redirect()->back()->withErrors(['Website URL cannot contain a hashtag #'])->withInput();
        }
        $admin = User::create([
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        if ($admin) {
           // dump($admin);
            $admin->assignRole(User::ROLE_ADMIN);
            
            $generalSettings = Settings::selectSettings('general');
           
            $generalSettings->site_url = $request->website_url;
            $generalSettings->site_name = $request->website_name;
            $update = Settings::updateSettings('general', $generalSettings); 
           // dd($update);
            if ($update) {
                
                setEnv('APP_NAME', Str::slug($request->website_name, '_'));
                setEnv('APP_URL', $request->website_url);
                setEnv('VR_COMPLETE', 1);
                setEnv('VR_SYSTEM_STATUS', 1);
              
                return redirect()->route('login');
            } else {
                
                return redirect()->back()->withErrors(['Failed to update general settings'])->withInput();
            }
        } else {
           dd('no adminde');
            return redirect()->back()->withErrors(['Failed to create the admin'])->withInput();
        }
    }

    public function completeBack(Request $request)
    {
        setEnv('VR_DATABASE_IMPORT', '');
        return redirect()->route('install.database.import');
    }
}
