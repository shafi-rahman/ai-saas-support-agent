# AI SaaS Support Agent

A multi-tenant AI support agent platform built with Laravel 12. Each company gets its own isolated knowledge base — upload documents, then let an AI chat against them in real time.

![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue?logo=php)
![Ollama](https://img.shields.io/badge/LLM-Ollama-black)
![Qdrant](https://img.shields.io/badge/VectorDB-Qdrant-purple)
![License](https://img.shields.io/badge/License-MIT-green)

---

## What it does

1. **Register** a company account (tenant)
2. **Upload** documents — PDF, Word, CSV, TXT, plain text, or a URL
3. Documents are chunked and embedded into a vector database in the background
4. **Chat** — ask questions and get AI answers grounded in your uploaded content (RAG)
5. **Embed** a floating chat widget on any website with a single `<script>` tag
6. Each tenant's data is fully isolated from all others

---

## Features

- Multi-tenant architecture — one codebase, many companies
- Document upload: PDF, DOCX, CSV, TXT, plain text, URL scraping
- RAG pipeline: chunk → embed → store in Qdrant → retrieve → generate
- Streaming chat responses (Server-Sent Events)
- Web UI: dashboard, document management (list / delete / re-process), settings, chat
- **Embeddable widget** — copy-paste `<script>` snippet adds a chat bubble to any website
- Settings page: default AI model picker, custom system prompt (agent persona), API key management
- REST API with token authentication (usable from any frontend or curl)
- Conversation history and AI request logs
- Queue-based background processing for documents

---

## Architecture

```
Browser / API client
        │
        ▼
  Laravel 12 (PHP)
  ┌─────────────────────────────────────────┐
  │  Web UI (Blade + Tailwind)              │
  │  REST API  /api/v1/*                    │
  │  Queue worker  (ProcessDocumentJob)     │
  └────────┬───────────────┬───────────────┘
           │               │
           ▼               ▼
        MySQL           Qdrant
    (users, docs,    (vector embeddings
    conversations)    for similarity search)
           │
           ▼
        Ollama  (runs locally)
    ┌─────────────────────┐
    │ nomic-embed-text    │  ← turns text into vectors
    │ phi / llama3 / etc. │  ← generates chat answers
    └─────────────────────┘
```

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL 8 |
| Vector database | Qdrant |
| LLM + embeddings | Ollama (runs 100% locally) |
| Default chat model | phi (3B) — swappable in UI |
| Embedding model | nomic-embed-text (768 dim) |
| Frontend | Blade templates + Tailwind CSS (CDN) |
| Background jobs | Laravel database queue |

---

## Prerequisites

Install these before you start:

- **PHP 8.2+** with extensions: `pdo_mysql`, `zip`, `curl`, `mbstring`
- **Composer** — [getcomposer.org](https://getcomposer.org)
- **Node.js 18+** — [nodejs.org](https://nodejs.org)
- **Docker** — for MySQL and Qdrant (or install them manually)
- **Ollama** — [ollama.ai](https://ollama.ai) — the local AI engine

---

## Quick start

### 1. Clone the repo

```bash
git clone https://github.com/shafi-rahman/ai-saas-support-agent.git
cd ai-saas-support-agent/laravel-app
```

### 2. Install Ollama models

```bash
# Pull the embedding model (required)
ollama pull nomic-embed-text

# Pull at least one chat model
ollama pull phi          # fast, 3B — recommended for low-RAM machines
ollama pull llama3       # better quality, 8B
```

### 3. Start MySQL and Qdrant with Docker

```bash
# From laravel-app/
docker compose up -d
```

This starts:
- MySQL 8 on port `3306` (database: `ai_saas_support`, no password)
- Qdrant on port `6333`

> **No Docker?** Install MySQL and Qdrant manually and update `.env` accordingly.

### 4. Configure the app

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install && npm run build
```

The default `.env.example` is pre-configured for the Docker setup above. No changes needed if you used Docker.

### 5. Start the server

Open **two terminals**:

**Terminal 1 — web server:**
```bash
php artisan serve
```

**Terminal 2 — queue worker** (processes document uploads in the background):
```bash
php artisan queue:work
```

### 6. Open the app

Visit [http://localhost:8000](http://localhost:8000), click **Create account**, and you're in.

---

## Usage

### Web UI

| Page | URL | What it does |
|---|---|---|
| Register | `/register` | Create a company account |
| Login | `/login` | Sign in |
| Dashboard | `/dashboard` | Upload docs, view stats, copy embed snippet |
| Documents | `/documents` | List, delete, and re-process knowledge base docs |
| Chat | `/chat` | Talk to your knowledge base |
| Settings | `/settings` | Default model, agent persona, API key |

### REST API

All API routes are under `/api/v1/`. Authenticate with your token in the `Authorization` header.

**Get a token:**
```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"you@company.com","password":"yourpassword"}'
```

**Upload a document:**
```bash
curl -X POST http://localhost:8000/api/v1/documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "title=Product FAQ" \
  -F "type=pdf" \
  -F "file=@/path/to/faq.pdf"
```

**Chat:**
```bash
curl -X POST http://localhost:8000/api/v1/chat/sse \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"prompt":"What is your refund policy?","session_id":"session-abc","model":"phi"}'
```

**List conversations:**
```bash
curl http://localhost:8000/api/v1/conversations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Delete a document:**
```bash
curl -X DELETE http://localhost:8000/api/v1/documents/1 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Re-process a document** (clears old vectors and re-embeds):
```bash
curl -X POST http://localhost:8000/api/v1/documents/1/reprocess \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Embeddable widget

The widget endpoint is **public** — no Bearer token needed, just the tenant's widget key.

```bash
curl -X POST http://localhost:8000/api/widget/chat \
  -H "Content-Type: application/json" \
  -d '{"widget_key":"YOUR_WIDGET_KEY","prompt":"What is your return policy?","session_id":"abc123"}'
```

To embed the chat bubble on any website, copy the snippet from `/dashboard` and paste it before `</body>`:

```html
<script src="https://yourapp.com/widget.js"
        data-key="YOUR_WIDGET_KEY"
        data-title="Acme Support"
        data-color="#2563eb"
        data-position="right"
        data-model="phi"></script>
```

---

## Project structure

```
laravel-app/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/DocumentController.php   # document CRUD + reprocess API
│   │   ├── AIController.php               # chat + SSE streaming
│   │   ├── ConversationController.php     # history + logs API
│   │   ├── WidgetController.php           # public widget chat endpoint (CORS)
│   │   └── Web/
│   │       ├── AuthController.php         # login / register / logout
│   │       ├── DashboardController.php    # dashboard page
│   │       ├── DocumentsController.php    # document management page
│   │       └── SettingsController.php     # settings + API key regeneration
│   ├── Jobs/
│   │   └── ProcessDocumentJob.php         # chunk → embed → store in Qdrant
│   ├── Models/
│   │   ├── Tenant.php                     # company / organisation
│   │   ├── User.php                       # belongs to a tenant
│   │   ├── Document.php                   # uploaded knowledge
│   │   ├── DocumentChunk.php              # chunked text + vector ID
│   │   ├── Conversation.php               # chat session
│   │   ├── Message.php                    # individual message
│   │   └── AILog.php                      # every AI request logged
│   └── Services/
│       ├── AI/EmbeddingService.php        # text → vector via Ollama
│       ├── RAG/RagService.php             # retrieve chunks → build prompt
│       └── Qdrant/QdrantService.php       # vector DB client
├── routes/
│   ├── api.php                            # token-authenticated REST API
│   └── web.php                            # session-authenticated web UI
├── resources/views/
│   ├── layouts/app.blade.php              # sidebar layout
│   ├── auth/{login,register}.blade.php
│   ├── dashboard.blade.php                # stats, upload, embed snippet
│   ├── documents.blade.php                # list, delete, re-process
│   ├── settings.blade.php                 # model, persona, API key
│   └── chat.blade.php                     # streaming chat UI
├── public/
│   └── widget.js                          # self-contained embeddable chat bubble
├── database/migrations/                   # all schema migrations
├── docker-compose.yml                     # MySQL + Qdrant
└── .env.example                           # all config options documented
```

---

## Configuration

All settings live in `.env`. Key options:

```env
# Switch chat model per request in the UI, or set a default here
OLLAMA_URL=http://localhost:11434/api/chat
OLLAMA_EMBEDDING_MODEL=nomic-embed-text

# Reduce these if your machine runs out of RAM
OLLAMA_NUM_CTX=2048
OLLAMA_NUM_PREDICT=512

# Qdrant — must match your embedding model's output dimensions
QDRANT_VECTOR_SIZE=768
QDRANT_COLLECTION=knowledge_base
```

---

## Supported document types

| Type | How it's processed |
|---|---|
| PDF | Text extracted with `smalot/pdfparser` |
| Word (.docx) | XML extracted from the zip using PHP's built-in `ZipArchive` |
| CSV | Rows converted to `column: value` sentences |
| TXT | Read as plain text |
| Plain text | Pasted directly in the form |
| URL | Fetched and `strip_tags()` applied |

---

## Troubleshooting

**Documents stay "pending"**
The queue worker isn't running. Start it with `php artisan queue:work`.

**Embedding fails / 500 error on chat**
Ollama isn't running, or the embedding model isn't pulled. Run:
```bash
ollama serve          # start Ollama
ollama pull nomic-embed-text
```

**MySQL connection refused**
Docker containers aren't running. Run `docker compose up -d` from `laravel-app/`.

**First chat response is very slow (30–60s)**
Ollama is loading the model into RAM. Subsequent messages are much faster.

---

## License

MIT — free to use, modify, and distribute.
