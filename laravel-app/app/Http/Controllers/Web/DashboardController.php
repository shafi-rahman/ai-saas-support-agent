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
            'messages'      => Message::whereIn('conversation_id', Conversation::where('tenant_id', $tenantId)->select('id'))->count(),
        ];

        $recentDocs = Document::where('tenant_id', $tenantId)
            ->latest()
            ->limit(6)
            ->get();

        $recentLogs = AILog::where('tenant_id', $tenantId)
            ->latest()
            ->limit(8)
            ->get();

        $widgetKey = auth()->user()->tenant->widget_key;

        return view('dashboard', compact('stats', 'recentDocs', 'recentLogs', 'widgetKey'));
    }
}
