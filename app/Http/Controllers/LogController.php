<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis; // Use Laravel's Redis facade
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        // Fetch logs from Redis
        $entries = Redis::xrange('log', '-', '+');
        $logs = [];

        foreach ($entries as $entry) {
            // Assuming entry has a 'fields' key similar to StreamEntry
            $logs[] = $entry['message']; // Adjust based on your actual structure
        }

        // Pass logs to the view
        return view('log_index', ['logs' => $logs]);
    }
}

