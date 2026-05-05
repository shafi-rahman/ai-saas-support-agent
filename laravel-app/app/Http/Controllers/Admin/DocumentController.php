<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDocumentJob;
use App\Models\Document;
use App\Services\Qdrant\QdrantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $docs = Document::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->get(['id', 'title', 'type', 'status', 'chunk_count', 'created_at']);

        return response()->json($docs);
    }

    private const FILE_TYPES = ['pdf', 'docx', 'csv', 'txt'];

    public function store(Request $request)
    {
        $request->validate([
            'type'    => 'required|in:pdf,docx,csv,txt,text,url',
            'title'   => 'required|string|max:255',
            'content' => 'required_if:type,text|nullable|string',
            'url'     => 'required_if:type,url|nullable|url|max:2048',
            'file'    => [
                'nullable', 'file', 'max:20480',
                \Illuminate\Validation\Rule::requiredIf(in_array($request->type, self::FILE_TYPES)),
                'mimes:pdf,docx,csv,txt',
            ],
        ]);

        $tenantId = $request->user()->tenant_id;
        $source   = null;

        if (in_array($request->type, self::FILE_TYPES)) {
            $source = $request->file('file')->store("documents/{$tenantId}", 'local');
        } elseif ($request->type === 'url') {
            $source = $request->url;
        }

        $document = Document::create([
            'tenant_id' => $tenantId,
            'title'     => $request->title,
            'type'      => $request->type,
            'source'    => $source,
            'status'    => 'pending',
        ]);

        if ($request->type === 'text') {
            $path = "documents/{$tenantId}/{$document->id}.txt";
            Storage::disk('local')->put($path, $request->content);
            $document->update(['source' => $path]);
        }

        ProcessDocumentJob::dispatch($document);

        return response()->json([
            'id'     => $document->id,
            'title'  => $document->title,
            'type'   => $document->type,
            'status' => $document->status,
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $document = Document::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        return response()->json($document);
    }

    public function destroy(Request $request, int $id, QdrantService $qdrant)
    {
        $document = Document::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($id);

        $qdrant->deleteByDocument($document->id, $request->user()->tenant_id);
        $document->delete();

        return response()->json(['message' => 'Document deleted.']);
    }
}
