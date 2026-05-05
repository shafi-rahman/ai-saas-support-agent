<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $tenant   = auth()->user()->tenant;
        $apiToken = session('api_token');

        return view('settings', compact('tenant', 'apiToken'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'default_model' => 'required|string|in:phi,llama3,gemma2,mistral',
            'system_prompt' => 'nullable|string|max:2000',
        ]);

        auth()->user()->tenant->update([
            'default_model' => $request->default_model,
            'system_prompt' => $request->system_prompt,
        ]);

        return back()->with('success', 'Settings saved.');
    }

    public function regenerateKey(Request $request)
    {
        $plain = auth()->user()->issueToken();
        $request->session()->put('api_token', $plain);

        return back()->with('new_token', $plain);
    }
}
