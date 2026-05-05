@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')

{{-- Header --}}
<div class="px-8 py-6 bg-white border-b border-gray-200 flex-shrink-0">
    <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-500 text-sm mt-0.5">Welcome back, {{ auth()->user()->name }}</p>
</div>

<div class="flex-1 px-8 py-6 space-y-6">

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Documents</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['documents'] }}</p>
            <p class="text-xs text-green-600 mt-1">{{ $stats['docs_ready'] }} ready</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Conversations</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['conversations'] }}</p>
            <p class="text-xs text-gray-400 mt-1">all time</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Messages</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['messages'] }}</p>
            <p class="text-xs text-gray-400 mt-1">exchanged</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">AI Requests</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $recentLogs->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">recent</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Upload document --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Upload Document</h2>
            <form id="uploadForm" class="space-y-3">
                @csrf
                <input type="text" id="docTitle" placeholder="Document title" required
                       class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <div class="flex gap-2">
                    <select id="docType"
                            class="flex-1 px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="pdf">PDF (.pdf)</option>
                        <option value="docx">Word (.docx)</option>
                        <option value="csv">CSV (.csv)</option>
                        <option value="txt">Text file (.txt)</option>
                        <option value="text">Plain text (paste)</option>
                        <option value="url">URL</option>
                    </select>
                </div>
                <div id="fileInput">
                    <input type="file" id="docFile" accept=".pdf"
                           class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p id="fileHint" class="text-xs text-gray-400 mt-1">PDF up to 20 MB</p>
                </div>
                <div id="textInput" class="hidden">
                    <textarea id="docContent" rows="4" placeholder="Paste your document content here..."
                              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                </div>
                <div id="urlInput" class="hidden">
                    <input type="url" id="docUrl" placeholder="https://example.com/page"
                           class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div id="uploadStatus" class="hidden text-sm"></div>
                <button type="submit"
                        class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Upload &amp; Process
                </button>
            </form>
        </div>

        {{-- Recent documents --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-900">Knowledge Base</h2>
                <span class="text-xs text-gray-400">{{ $stats['documents'] }} docs</span>
            </div>
            @if($recentDocs->isEmpty())
                <p class="text-sm text-gray-400 text-center py-8">No documents yet. Upload one to get started.</p>
            @else
                <div class="space-y-2">
                    @foreach($recentDocs as $doc)
                        <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                                {{ $doc->status === 'ready' ? 'bg-green-100 text-green-700' : ($doc->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                @if($doc->type === 'pdf')
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6l-4-4H4v16zm8-15l3 3h-3V3z"/></svg>
                                @elseif($doc->type === 'url')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                @else
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $doc->title }}</p>
                                <p class="text-xs text-gray-400">{{ $doc->chunk_count }} chunks · {{ $doc->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0
                                {{ $doc->status === 'ready' ? 'bg-green-100 text-green-700' : ($doc->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ $doc->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

    {{-- Recent AI logs --}}
    @if($recentLogs->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-4">Recent Activity</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase tracking-wide border-b border-gray-100">
                        <th class="pb-3 font-medium">Time</th>
                        <th class="pb-3 font-medium">Model</th>
                        <th class="pb-3 font-medium">Preview</th>
                        <th class="pb-3 font-medium">Duration</th>
                        <th class="pb-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($recentLogs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 text-gray-400 text-xs whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                        <td class="py-3 font-mono text-xs text-gray-600">{{ $log->model }}</td>
                        <td class="py-3 text-gray-700 max-w-xs truncate">{{ $log->prompt_preview }}</td>
                        <td class="py-3 text-gray-400 text-xs">{{ $log->duration_ms ? $log->duration_ms . 'ms' : '—' }}</td>
                        <td class="py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $log->status === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $log->status }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Quick chat CTA --}}
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 flex items-center justify-between">
        <div>
            <h3 class="text-white font-semibold text-lg">Start chatting with your knowledge base</h3>
            <p class="text-blue-100 text-sm mt-0.5">Ask questions against your uploaded documents</p>
        </div>
        <a href="{{ route('chat') }}"
           class="flex-shrink-0 px-5 py-2.5 bg-white text-blue-700 font-medium text-sm rounded-lg hover:bg-blue-50 transition-colors">
            Open Chat →
        </a>
    </div>

</div>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const TOKEN = '{{ session("api_token", "") }}';
const typeEl   = document.getElementById('docType');
const fileDiv  = document.getElementById('fileInput');
const fileEl   = document.getElementById('docFile');
const fileHint = document.getElementById('fileHint');
const textDiv  = document.getElementById('textInput');
const urlDiv   = document.getElementById('urlInput');
const statusEl = document.getElementById('uploadStatus');

const FILE_TYPES = { pdf: '.pdf', docx: '.docx', csv: '.csv', txt: '.txt' };
const FILE_HINTS = {
    pdf:  'PDF up to 20 MB',
    docx: 'Word document (.docx) up to 20 MB',
    csv:  'CSV file up to 20 MB',
    txt:  'Plain text file (.txt) up to 20 MB',
};

typeEl.addEventListener('change', () => {
    const t = typeEl.value;
    const isFile = t in FILE_TYPES;
    fileDiv.classList.toggle('hidden', !isFile);
    textDiv.classList.toggle('hidden', t !== 'text');
    urlDiv.classList.toggle('hidden',  t !== 'url');
    if (isFile) {
        fileEl.accept = FILE_TYPES[t];
        fileHint.textContent = FILE_HINTS[t];
    }
});

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const title = document.getElementById('docTitle').value.trim();
    const type  = typeEl.value;
    if (!title) return;

    statusEl.className = 'text-sm text-blue-600';
    statusEl.textContent = 'Uploading…';
    statusEl.classList.remove('hidden');

    try {
        let body, headers;

        if (type in FILE_TYPES) {
            const file = fileEl.files[0];
            if (!file) { statusEl.className = 'text-sm text-red-600'; statusEl.textContent = 'Select a file.'; return; }
            const fd = new FormData();
            fd.append('title', title);
            fd.append('type', type);
            fd.append('file', file);
            body    = fd;
            headers = { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Authorization': 'Bearer ' + TOKEN };
        } else {
            const content = type === 'url'
                ? document.getElementById('docUrl').value.trim()
                : document.getElementById('docContent').value.trim();
            if (!content) { statusEl.className = 'text-sm text-red-600'; statusEl.textContent = 'Content required.'; return; }
            body    = JSON.stringify({ title, type, [type === 'url' ? 'url' : 'content']: content });
            headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Authorization': 'Bearer ' + TOKEN };
        }

        const res  = await fetch('/api/v1/documents', { method: 'POST', headers, body });
        const data = await res.json();

        if (res.ok) {
            statusEl.className   = 'text-sm text-green-600';
            statusEl.textContent = `✓ "${data.title}" uploaded — processing in background`;
            e.target.reset();
            typeEl.dispatchEvent(new Event('change'));
            setTimeout(() => location.reload(), 3000);
        } else {
            statusEl.className   = 'text-sm text-red-600';
            statusEl.textContent = data.message ?? 'Upload failed.';
        }
    } catch (err) {
        statusEl.className   = 'text-sm text-red-600';
        statusEl.textContent = err.message;
    }
});
</script>
@endpush
