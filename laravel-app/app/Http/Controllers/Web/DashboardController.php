<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AILog;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\Message;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $stats = [
            'documents'     => Document::where('tenant_id', $tenantId)->count(),
            'docs_ready'    => Document::where('tenant_id', $tenantId)->where('status', 'ready')->count(),
            'conversations' => Conversation::where('tenant_id', $tenantId)->count(),
            'messages'      => Message::whereHas('conversation', fn($q) => $q->where('tenant_id', $tenantId))->count(),
        ];

        $recentDocs = Document::where('tenant_id', $tenantId)
            ->latest()
            ->limit(6)
            ->get();

        $recentLogs = AILog::where('tenant_id', $tenantId)
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard', compact('stats', 'recentDocs', 'recentLogs'));
    }
}
