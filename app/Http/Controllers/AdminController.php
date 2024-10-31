<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use App\Jobs\EchoJob;


function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

class AdminController extends Controller
{
    public function index()
     {

        $text = "Hello Cruel World";
        return view('admin_index', ['text' => $text]);  
     } 

     public function reset(Request $request)
     {  
        $value = $request->session()->getId();
        
        return response()->json([
            'success' => true,
            'message' => 'Task created successfully!'
        ], 201);
        
    }


     public function show(Request $request)
     {  
        // session
        $value = $request->session()->put('session', 'my session value');
        $value = $request->session()->get('session');
        
        // cache
        Cache::put('cached_key', 'cached value', 60);
        $value = Cache::get('cached_key');
        
        // database
        Redis::set('test', generateRandomString());

        // job
        $message = 'Hello, this is a queued message!';

        // Dispatch the job
        EchoJob::dispatch($message);

        return view('admin_index', ['text' => Redis::get('test'),
                                    'session' => $value]);
     }
}
