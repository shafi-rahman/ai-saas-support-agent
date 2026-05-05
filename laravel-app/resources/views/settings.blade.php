@extends('layouts.app')
@section('title', 'Settings')

@section('content')

{{-- Header --}}
<div class="px-8 py-6 bg-white border-b border-gray-200 flex-shrink-0">
    <h1 class="text-xl font-bold text-gray-900">Settings</h1>
    <p class="text-gray-500 text-sm mt-0.5">Configure your AI agent and manage your account.</p>
</div>

<div class="flex-1 px-8 py-6 space-y-6 max-w-2xl">

    {{-- Success banner --}}
    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- ── AI Configuration ─────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">AI Configuration</h2>
            <p class="text-sm text-gray-500 mt-0.5">Set the default model and persona for your agent.</p>
        </div>

        <form method="POST" action="{{ route('settings.update') }}" class="px-6 py-5 space-y-5">
            @csrf

            {{-- Default model --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Default model</label>
                <div class="grid grid-cols-2 gap-2" id="modelGrid">
                    @php
                        $models = [
                            'phi'     => ['label' => 'Phi',     'desc' => '3B · Fastest'],
                            'llama3'  => ['label' => 'Llama 3', 'desc' => '8B · Balanced'],
                            'gemma2'  => ['label' => 'Gemma 2', 'desc' => '9B · Google'],
                            'mistral' => ['label' => 'Mistral', 'desc' => '7B · Precise'],
                        ];
                        $current = $tenant->default_model ?? 'phi';
                    @endphp
                    @foreach($models as $value => $meta)
                    <label class="model-card relative flex items-center gap-3 px-4 py-3 rounded-lg border-2 cursor-pointer transition-all
                        {{ $current === $value ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                        <input type="radio" name="default_model" value="{{ $value }}"
                               {{ $current === $value ? 'checked' : '' }}
                               class="sr-only" onchange="updateModelCards()">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-900">{{ $meta['label'] }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $meta['desc'] }}</p>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 flex-shrink-0 flex items-center justify-center
                            {{ $current === $value ? 'border-blue-500 bg-blue-500' : 'border-gray-300' }}">
                            @if($current === $value)
                            <div class="w-1.5 h-1.5 bg-white rounded-full"></div>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- System prompt --}}
            <div>
                <label for="system_prompt" class="block text-sm font-medium text-gray-700 mb-2">
                    Agent persona
                    <span class="text-gray-400 font-normal">(system prompt)</span>
                </label>
                <textarea id="system_prompt" name="system_prompt" rows="4"
                          placeholder="e.g. You are a helpful support agent for Acme Corp. Answer questions based only on the provided documentation. If unsure, say so."
                          class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none text-gray-700"
                >{{ old('system_prompt', $tenant->system_prompt) }}</textarea>
                <p class="text-xs text-gray-400 mt-1.5">This is sent to the AI before every conversation. Leave blank for default behaviour.</p>
            </div>

            @error('default_model') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @error('system_prompt') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="pt-1">
                <button type="submit"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Save changes
                </button>
            </div>
        </form>
    </div>

    {{-- ── API Key ──────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">API Key</h2>
            <p class="text-sm text-gray-500 mt-0.5">Use this token to authenticate requests to the REST API.</p>
        </div>

        <div class="px-6 py-5 space-y-4">

            {{-- New token banner (shown once after regeneration) --}}
            @if(session('new_token'))
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
                <p class="text-sm font-medium text-amber-800 mb-2">New token generated — copy it now. It won't be shown again.</p>
                <div class="flex items-center gap-2">
                    <code id="newTokenDisplay" class="flex-1 font-mono text-xs bg-white border border-amber-200 rounded-lg px-3 py-2 text-amber-900 break-all">{{ session('new_token') }}</code>
                    <button onclick="copyToken('newTokenDisplay', this)"
                            class="flex-shrink-0 px-3 py-2 bg-amber-100 hover:bg-amber-200 text-amber-800 text-xs font-medium rounded-lg transition-colors">
                        Copy
                    </button>
                </div>
            </div>
            @endif

            {{-- Current key (from session) --}}
            @if($apiToken && !session('new_token'))
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Current key</label>
                <div class="flex items-center gap-2">
                    <code id="tokenDisplay"
                          class="flex-1 font-mono text-xs bg-gray-50 border border-gray-200 rounded-lg px-3 py-2.5 text-gray-700 break-all"
                          data-full="{{ $apiToken }}"
                          data-masked="{{ str_repeat('•', 40) . substr($apiToken, -8) }}"
                    >{{ str_repeat('•', 40) . substr($apiToken, -8) }}</code>
                    <div class="flex flex-col gap-1.5">
                        <button onclick="toggleToken()"
                                id="toggleBtn"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                            Show
                        </button>
                        <button onclick="copyToken('tokenDisplay', this)"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                            Copy
                        </button>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-2">Key was generated during your last login. Log out and back in to refresh it here.</p>
            </div>
            @elseif(!session('new_token'))
            <p class="text-sm text-gray-400">Sign out and sign back in to view your current key here.</p>
            @endif

            {{-- Regenerate --}}
            <div class="pt-1 border-t border-gray-100">
                <p class="text-sm text-gray-600 mb-3">Generate a new API key. <span class="text-red-500 font-medium">This immediately invalidates the current key.</span></p>
                <form method="POST" action="{{ route('settings.regenerate-key') }}" id="regenForm">
                    @csrf
                    <button type="button" onclick="confirmRegen()"
                            class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium rounded-lg transition-colors border border-red-200">
                        Regenerate API key
                    </button>
                    <span id="regenConfirm" class="hidden ml-3 text-sm text-gray-600">
                        Are you sure?
                        <button type="submit" class="ml-2 px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-md transition-colors">Yes, regenerate</button>
                        <button type="button" onclick="cancelRegen()" class="ml-1 px-3 py-1 text-gray-500 hover:bg-gray-100 text-xs rounded-md transition-colors">Cancel</button>
                    </span>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Account Info ─────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Account</h2>
        </div>
        <dl class="px-6 py-5 divide-y divide-gray-50">
            @foreach([
                'Company'  => $tenant->name,
                'Your name'=> auth()->user()->name,
                'Email'    => auth()->user()->email,
                'Role'     => ucfirst(auth()->user()->role),
                'Plan'     => 'Free',
            ] as $label => $value)
            <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                <dt class="text-sm text-gray-500">{{ $label }}</dt>
                <dd class="text-sm font-medium text-gray-900">{{ $value }}</dd>
            </div>
            @endforeach
        </dl>
    </div>

</div>

@endsection

@push('scripts')
<script>
// ── Model card radio styling ───────────────────────────────────────────────────
function updateModelCards() {
    document.querySelectorAll('.model-card').forEach(card => {
        const radio  = card.querySelector('input[type=radio]');
        const dot    = card.querySelector('.rounded-full.border-2');
        const inner  = dot.querySelector('div');
        const active = radio.checked;

        card.classList.toggle('border-blue-500', active);
        card.classList.toggle('bg-blue-50',      active);
        card.classList.toggle('border-gray-200', !active);

        dot.classList.toggle('border-blue-500', active);
        dot.classList.toggle('bg-blue-500',     active);
        dot.classList.toggle('border-gray-300', !active);

        if (active && !inner) {
            const d = document.createElement('div');
            d.className = 'w-1.5 h-1.5 bg-white rounded-full';
            dot.appendChild(d);
        } else if (!active && inner) {
            inner.remove();
        }
    });
}

// ── Token show/hide ───────────────────────────────────────────────────────────
let tokenVisible = false;
function toggleToken() {
    const el  = document.getElementById('tokenDisplay');
    const btn = document.getElementById('toggleBtn');
    tokenVisible = !tokenVisible;
    el.textContent = tokenVisible ? el.dataset.full : el.dataset.masked;
    btn.textContent = tokenVisible ? 'Hide' : 'Show';
}

// ── Copy to clipboard ─────────────────────────────────────────────────────────
function copyToken(elId, btn) {
    const el  = document.getElementById(elId);
    const val = el.dataset.full ?? el.textContent;
    navigator.clipboard.writeText(val.trim()).then(() => {
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = orig, 2000);
    });
}

// ── Regenerate confirm ────────────────────────────────────────────────────────
function confirmRegen() {
    document.getElementById('regenConfirm').classList.remove('hidden');
}
function cancelRegen() {
    document.getElementById('regenConfirm').classList.add('hidden');
}
</script>
@endpush
