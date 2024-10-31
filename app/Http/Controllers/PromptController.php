<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class PromptController extends Controller
{
    /**
     * Show the prompt page with values from Redis.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $systemPrompt = Redis::get('prompt:system');
        $userPrompt = Redis::get('prompt:user');

        return view('prompt_index', [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ]);
    }

    /**
     * Save the prompt to Redis based on type (system or user).
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function save(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string',
            'type' => 'required|string|in:system,user',
        ]);

        $prompt = $request->input('prompt');
        $type = $request->input('type');

        if ($type === 'system') {
            Redis::set('prompt:system', $prompt);
        } elseif ($type === 'user') {
            Redis::set('prompt:user', $prompt);
        }

        return redirect()->route('prompt.index');
    }
}
