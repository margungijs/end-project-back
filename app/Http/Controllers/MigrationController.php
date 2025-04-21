<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MigrationController extends Controller
{
    public function runLatestMigration(Request $request)
    {
        try {
            Artisan::call('migrate', ['--step' => 1]);

            return response()->json(['status' => 'success', 'message' => 'Latest migration executed successfully!']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
