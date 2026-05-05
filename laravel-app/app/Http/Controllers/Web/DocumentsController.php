<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Document;

class DocumentsController extends Controller
{
    public function index()
    {
        $documents = Document::where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->get();

        return view('documents', compact('documents'));
    }
}
