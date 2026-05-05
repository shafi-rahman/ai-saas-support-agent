(function () {
    'use strict';

    const script  = document.currentScript;
    const KEY     = script?.dataset.key;
    const TITLE   = script?.dataset.title   || 'Support Chat';
    const COLOR   = script?.dataset.color   || '#2563eb';
    const POS     = script?.dataset.position === 'left' ? 'left' : 'right';
    const MODEL   = script?.dataset.model   || 'phi';
    const API_URL = (script?.dataset.apiUrl || script?.src.replace(/\/widget\.js.*$/, '')).replace(/\/$/, '');

    if (!KEY) { console.error('[AI Widget] Missing data-key attribute.'); return; }

    // ── Unique session per page load ─────────────────────────────────────────
    const SESSION_ID = 'widget-' + Math.random().toString(36).slice(2, 10);

    // ── Shadow DOM container ─────────────────────────────────────────────────
    const host = document.createElement('div');
    host.id = 'ai-support-widget';
    document.body.appendChild(host);
    const shadow = host.attachShadow({ mode: 'open' });

    // ── Styles ───────────────────────────────────────────────────────────────
    const style = document.createElement('style');
    style.textContent = `
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        .fab {
            position: fixed;
            bottom: 24px;
            ${POS}: 24px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: ${COLOR};
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(0,0,0,.25);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2147483647;
            transition: transform .2s, box-shadow .2s;
        }
        .fab:hover { transform: scale(1.08); box-shadow: 0 6px 24px rgba(0,0,0,.3); }
        .fab svg { width: 26px; height: 26px; fill: #fff; }

        .panel {
            position: fixed;
            bottom: 92px;
            ${POS}: 20px;
            width: 360px;
            max-width: calc(100vw - 32px);
            height: 520px;
            max-height: calc(100vh - 120px);
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0,0,0,.18);
            display: flex;
            flex-direction: column;
            z-index: 2147483646;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            font-size: 14px;
            color: #111827;
            overflow: hidden;
            transform: scale(.92) translateY(12px);
            opacity: 0;
            pointer-events: none;
            transition: transform .22s cubic-bezier(.34,1.56,.64,1), opacity .18s;
        }
        .panel.open {
            transform: scale(1) translateY(0);
            opacity: 1;
            pointer-events: all;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            background: ${COLOR};
            color: #fff;
            flex-shrink: 0;
        }
        .header-title { font-weight: 600; font-size: 15px; }
        .header-sub   { font-size: 11px; opacity: .8; margin-top: 1px; }
        .close-btn {
            background: rgba(255,255,255,.2);
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            cursor: pointer;
            color: #fff;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background .15s;
        }
        .close-btn:hover { background: rgba(255,255,255,.35); }

        .messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .empty {
            margin: auto;
            text-align: center;
            color: #9ca3af;
            padding: 20px;
        }
        .empty p   { font-size: 14px; margin-bottom: 4px; }
        .empty span { font-size: 12px; }

        .msg { display: flex; flex-direction: column; max-width: 82%; }
        .msg.user      { align-self: flex-end; align-items: flex-end; }
        .msg.assistant { align-self: flex-start; align-items: flex-start; }

        .bubble {
            padding: 9px 13px;
            border-radius: 14px;
            line-height: 1.55;
            word-break: break-word;
            font-size: 13.5px;
        }
        .msg.user .bubble {
            background: ${COLOR};
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .msg.assistant .bubble {
            background: #f3f4f6;
            color: #111827;
            border-bottom-left-radius: 4px;
        }

        .cursor {
            display: inline-block;
            width: 2px;
            height: 13px;
            background: #6b7280;
            border-radius: 1px;
            margin-left: 2px;
            vertical-align: middle;
            animation: blink .8s step-end infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }

        .error-bubble {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 9px 13px;
            border-radius: 10px;
            font-size: 13px;
            align-self: flex-start;
            max-width: 82%;
        }

        .input-row {
            display: flex;
            gap: 8px;
            padding: 12px;
            border-top: 1px solid #e5e7eb;
            flex-shrink: 0;
            background: #fff;
        }
        .input-row textarea {
            flex: 1;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13.5px;
            font-family: inherit;
            resize: none;
            outline: none;
            min-height: 38px;
            max-height: 100px;
            line-height: 1.5;
            color: #111827;
            background: #f9fafb;
        }
        .input-row textarea:focus { border-color: ${COLOR}; background: #fff; }
        .send-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: none;
            background: ${COLOR};
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: opacity .15s;
        }
        .send-btn:disabled { opacity: .5; cursor: not-allowed; }
        .send-btn svg { width: 18px; height: 18px; fill: #fff; }

        .branding {
            text-align: center;
            font-size: 10px;
            color: #d1d5db;
            padding: 4px 0 8px;
            flex-shrink: 0;
        }
        .branding a { color: #d1d5db; text-decoration: none; }
    `;
    shadow.appendChild(style);

    // ── FAB button ───────────────────────────────────────────────────────────
    const fab = document.createElement('button');
    fab.className = 'fab';
    fab.title = 'Open chat';
    fab.innerHTML = `<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>`;
    shadow.appendChild(fab);

    // ── Chat panel ───────────────────────────────────────────────────────────
    const panel = document.createElement('div');
    panel.className = 'panel';
    panel.innerHTML = `
        <div class="header">
            <div>
                <div class="header-title">${escHtml(TITLE)}</div>
                <div class="header-sub">Powered by AI</div>
            </div>
            <button class="close-btn" title="Close">✕</button>
        </div>
        <div class="messages" id="wgt-messages">
            <div class="empty">
                <p>How can I help you?</p>
                <span>Ask me anything about ${escHtml(TITLE)}</span>
            </div>
        </div>
        <div class="input-row">
            <textarea id="wgt-input" placeholder="Type a message…" rows="1"></textarea>
            <button class="send-btn" id="wgt-send" title="Send">
                <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
            </button>
        </div>
        <div class="branding">Powered by <a href="#" target="_blank">AI Support Agent</a></div>
    `;
    shadow.appendChild(panel);

    const messagesEl = panel.querySelector('#wgt-messages');
    const inputEl    = panel.querySelector('#wgt-input');
    const sendBtn    = panel.querySelector('#wgt-send');
    const closeBtn   = panel.querySelector('.close-btn');

    // ── Open / close ─────────────────────────────────────────────────────────
    let isOpen = false;
    function toggle() {
        isOpen = !isOpen;
        panel.classList.toggle('open', isOpen);
        fab.innerHTML = isOpen
            ? `<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>`
            : `<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>`;
        if (isOpen) { inputEl.focus(); }
    }
    fab.addEventListener('click', toggle);
    closeBtn.addEventListener('click', toggle);

    // ── Message rendering ─────────────────────────────────────────────────────
    function addMessage(role, text) {
        const empty = messagesEl.querySelector('.empty');
        if (empty) empty.remove();

        const wrap   = document.createElement('div');
        wrap.className = 'msg ' + role;
        const bubble = document.createElement('div');
        bubble.className = 'bubble';
        if (text) bubble.textContent = text;
        wrap.appendChild(bubble);
        messagesEl.appendChild(wrap);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return bubble;
    }

    function addError(text) {
        const div = document.createElement('div');
        div.className = 'error-bubble';
        div.textContent = '⚠ ' + text;
        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    // ── Send ──────────────────────────────────────────────────────────────────
    async function send() {
        const prompt = inputEl.value.trim();
        if (!prompt || sendBtn.disabled) return;

        addMessage('user', prompt);
        inputEl.value = '';
        inputEl.style.height = 'auto';
        sendBtn.disabled = true;

        const bubble = addMessage('assistant');
        const cursor = document.createElement('span');
        cursor.className = 'cursor';
        bubble.appendChild(cursor);

        let rawText   = '';
        let firstChunk = false;

        try {
            const res = await fetch(API_URL + '/api/widget/chat', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify({
                    widget_key: KEY,
                    prompt,
                    session_id: SESSION_ID,
                    model:      MODEL,
                }),
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                bubble.parentElement.remove();
                addError(err.message || 'Request failed (' + res.status + ')');
                return;
            }

            const reader  = res.body.getReader();
            const decoder = new TextDecoder();
            let   buffer  = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop();

                for (const line of lines) {
                    if (!line.startsWith('data: ')) continue;
                    const data = line.slice(6);
                    if (data === 'true') continue;

                    if (!firstChunk) {
                        firstChunk = true;
                        cursor.remove();
                    }

                    rawText += data.replace(/\\n/g, '\n');
                    bubble.textContent = rawText;
                    bubble.appendChild(cursor);
                    messagesEl.scrollTop = messagesEl.scrollHeight;
                }
            }

            cursor.remove();
            bubble.textContent = rawText || '(no response)';

        } catch (err) {
            bubble.parentElement?.remove();
            addError(err.message);
        } finally {
            sendBtn.disabled = false;
            inputEl.focus();
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    }

    inputEl.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });
    inputEl.addEventListener('input', () => {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 100) + 'px';
    });
    sendBtn.addEventListener('click', send);

    // ── Utility ───────────────────────────────────────────────────────────────
    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
