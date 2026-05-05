@extends('layouts.app')
@section('title', 'Documents')

@section('content')

{{-- Header --}}
<div class="px-8 py-6 bg-white border-b border-gray-200 flex-shrink-0 flex items-center justify-between">
    <div>
        <h1 class="text-xl font-bold text-gray-900">Documents</h1>
        <p class="text-gray-500 text-sm mt-0.5">{{ $documents->count() }} document{{ $documents->count() !== 1 ? 's' : '' }} in your knowledge base</p>
    </div>
    <a href="{{ route('dashboard') }}"
       class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
        + Upload Document
    </a>
</div>

<div class="flex-1 px-8 py-6">

    @if($documents->isEmpty())

        {{-- Empty state --}}
        <div class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <p class="text-gray-900 font-semibold text-lg">No documents yet</p>
            <p class="text-gray-500 text-sm mt-1 mb-5">Upload PDFs, Word docs, CSVs, or paste text to build your knowledge base.</p>
            <a href="{{ route('dashboard') }}"
               class="inline-block px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                Upload your first document
            </a>
        </div>

    @else

        {{-- Status filter tabs --}}
        <div class="flex gap-1 mb-5 bg-gray-100 p-1 rounded-xl w-fit">
            <button onclick="filterDocs('all')" id="tab-all"
                    class="tab-btn px-4 py-1.5 text-sm font-medium rounded-lg transition-colors bg-white text-gray-900 shadow-sm">
                All <span class="ml-1 text-xs text-gray-400">{{ $documents->count() }}</span>
            </button>
            <button onclick="filterDocs('ready')" id="tab-ready"
                    class="tab-btn px-4 py-1.5 text-sm font-medium rounded-lg transition-colors text-gray-500">
                Ready <span class="ml-1 text-xs text-gray-400">{{ $documents->where('status', 'ready')->count() }}</span>
            </button>
            <button onclick="filterDocs('processing')" id="tab-processing"
                    class="tab-btn px-4 py-1.5 text-sm font-medium rounded-lg transition-colors text-gray-500">
                Processing <span class="ml-1 text-xs text-gray-400">{{ $documents->whereIn('status', ['pending','processing'])->count() }}</span>
            </button>
            <button onclick="filterDocs('failed')" id="tab-failed"
                    class="tab-btn px-4 py-1.5 text-sm font-medium rounded-lg transition-colors text-gray-500">
                Failed <span class="ml-1 text-xs text-gray-400">{{ $documents->where('status', 'failed')->count() }}</span>
            </button>
        </div>

        {{-- Documents table --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Document</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Chunks</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Uploaded</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                    </tr>
                </thead>
                <tbody id="docTableBody" class="divide-y divide-gray-50">
                    @foreach($documents as $doc)
                    <tr class="doc-row hover:bg-gray-50 transition-colors" data-status="{{ $doc->status }}" data-id="{{ $doc->id }}">

                        {{-- Title --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0
                                    @if($doc->status === 'ready') bg-green-50 text-green-600
                                    @elseif($doc->status === 'failed') bg-red-50 text-red-500
                                    @else bg-amber-50 text-amber-500 @endif">
                                    @if($doc->type === 'pdf')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 18h12V6l-4-4H4v16zm8-15l3 3h-3V3z"/></svg>
                                    @elseif($doc->type === 'docx')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM8 17v-1h8v1H8zm0-3v-1h8v1H8zm0-3V10h4v1H8z"/></svg>
                                    @elseif($doc->type === 'csv')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h18v2H3V3zm0 4h18v2H3V7zm0 4h18v2H3v-2zm0 4h18v2H3v-2zm0 4h18v2H3v-2z"/></svg>
                                    @elseif($doc->type === 'url')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-900 truncate max-w-xs">{{ $doc->title }}</p>
                                    @if($doc->status === 'failed' && $doc->error)
                                        <p class="text-xs text-red-500 truncate max-w-xs mt-0.5">{{ $doc->error }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Type --}}
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 uppercase">
                                {{ $doc->type }}
                            </span>
                        </td>

                        {{-- Status --}}
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                @if($doc->status === 'ready') bg-green-100 text-green-700
                                @elseif($doc->status === 'failed') bg-red-100 text-red-600
                                @elseif($doc->status === 'processing') bg-blue-100 text-blue-700
                                @else bg-amber-100 text-amber-700 @endif">
                                @if($doc->status === 'processing')
                                    <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                                @endif
                                {{ $doc->status }}
                            </span>
                        </td>

                        {{-- Chunks --}}
                        <td class="px-5 py-4 text-gray-500">
                            {{ $doc->chunk_count > 0 ? $doc->chunk_count : '—' }}
                        </td>

                        {{-- Uploaded --}}
                        <td class="px-5 py-4 text-gray-400 whitespace-nowrap text-xs">
                            {{ $doc->created_at->format('M j, Y') }}<br>
                            <span class="text-gray-300">{{ $doc->created_at->diffForHumans() }}</span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">

                                {{-- Re-process --}}
                                <button onclick="reprocess({{ $doc->id }}, this)"
                                        class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors whitespace-nowrap">
                                    Re-process
                                </button>

                                {{-- Delete (two-step confirm) --}}
                                <div class="relative" id="del-wrap-{{ $doc->id }}">
                                    <button onclick="askDelete({{ $doc->id }})"
                                            id="del-btn-{{ $doc->id }}"
                                            class="px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                        Delete
                                    </button>
                                    <div id="del-confirm-{{ $doc->id }}"
                                         class="hidden absolute right-0 top-0 flex items-center gap-1 bg-white border border-gray-200 rounded-lg shadow-lg p-1 z-10 whitespace-nowrap">
                                        <span class="text-xs text-gray-600 px-2">Sure?</span>
                                        <button onclick="confirmDelete({{ $doc->id }})"
                                                class="px-2.5 py-1 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                                            Yes, delete
                                        </button>
                                        <button onclick="cancelDelete({{ $doc->id }})"
                                                class="px-2.5 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100 rounded-md transition-colors">
                                            Cancel
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @endif

</div>

@endsection

@push('scripts')
<script>
const TOKEN = '{{ session("api_token", "") }}';

// ── Status filter ─────────────────────────────────────────────────────────────
const STATUS_MAP = { processing: ['pending', 'processing'] };

function filterDocs(status) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-white', 'text-gray-900', 'shadow-sm');
        btn.classList.add('text-gray-500');
    });
    const active = document.getElementById('tab-' + status);
    active.classList.add('bg-white', 'text-gray-900', 'shadow-sm');
    active.classList.remove('text-gray-500');

    document.querySelectorAll('.doc-row').forEach(row => {
        const s = row.dataset.status;
        const show = status === 'all'
            || (status === 'processing' ? ['pending','processing'].includes(s) : s === status);
        row.style.display = show ? '' : 'none';
    });
}

// ── Re-process ────────────────────────────────────────────────────────────────
async function reprocess(id, btn) {
    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Queuing…';

    try {
        const res  = await fetch(`/api/v1/documents/${id}/reprocess`, {
            method:  'POST',
            headers: { 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json' },
        });
        const data = await res.json();

        if (res.ok) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            row.querySelector('.px-2\\.5.py-1.rounded-full').className =
                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700';
            row.querySelector('.px-2\\.5.py-1.rounded-full').textContent = 'pending';
            btn.textContent = 'Queued!';
            setTimeout(() => { btn.textContent = original; btn.disabled = false; }, 2000);
        } else {
            alert(data.message || 'Failed to re-process.');
            btn.textContent = original;
            btn.disabled = false;
        }
    } catch (e) {
        alert(e.message);
        btn.textContent = original;
        btn.disabled = false;
    }
}

// ── Delete (two-step) ─────────────────────────────────────────────────────────
function askDelete(id) {
    document.getElementById('del-btn-' + id).classList.add('hidden');
    document.getElementById('del-confirm-' + id).classList.remove('hidden');
}
function cancelDelete(id) {
    document.getElementById('del-btn-' + id).classList.remove('hidden');
    document.getElementById('del-confirm-' + id).classList.add('hidden');
}
async function confirmDelete(id) {
    const wrap = document.getElementById('del-wrap-' + id);
    wrap.innerHTML = '<span class="text-xs text-gray-400">Deleting…</span>';

    try {
        const res = await fetch(`/api/v1/documents/${id}`, {
            method:  'DELETE',
            headers: { 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json' },
        });

        if (res.ok) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            row.style.transition = 'opacity .3s';
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else {
            const data = await res.json();
            wrap.innerHTML = `<span class="text-xs text-red-500">${data.message || 'Error'}</span>`;
        }
    } catch (e) {
        wrap.innerHTML = `<span class="text-xs text-red-500">${e.message}</span>`;
    }
}

// Close confirm popover when clicking outside
document.addEventListener('click', e => {
    if (!e.target.closest('[id^="del-wrap-"]')) {
        document.querySelectorAll('[id^="del-confirm-"]').forEach(el => {
            if (!el.classList.contains('hidden')) {
                const id = el.id.replace('del-confirm-', '');
                cancelDelete(id);
            }
        });
    }
});
</script>
@endpush
