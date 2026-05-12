# AI SaaS Support Agent

A self-hosted, multi-tenant AI support platform built on Laravel 12. Companies upload their documentation; the system chunks, embeds, and indexes it into a vector store so every conversation is grounded in tenant-specific knowledge — not generic LLM hallucination.

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white" alt="Laravel 12" />
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white" alt="PHP 8.2+" />
  <img src="https://img.shields.io/badge/LLM-Ollama-black?logo=ollama" alt="Ollama" />
  <img src="https://img.shields.io/badge/VectorDB-Qdrant-DC143C" alt="Qdrant" />
  <img src="https://img.shields.io/badge/Pattern-RAG-5A67D8" alt="RAG" />
  <img src="https://img.shields.io/badge/Arch-Multi--Tenant-0EA5E9" alt="Multi-Tenant" />
  <img src="https://img.shields.io/badge/License-MIT-22C55E" alt="MIT License" />
</p>

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Design Decisions and Tradeoffs](#design-decisions-and-tradeoffs)
3. [Processing Lifecycle](#processing-lifecycle)
4. [Service Layer](#service-layer)
5. [Multi-Tenancy Model](#multi-tenancy-model)
6. [Scalability Considerations](#scalability-considerations)
7. [Extension Points](#extension-points)
8. [Observability](#observability)
9. [Tech Stack](#tech-stack)
10. [Getting Started](#getting-started)
11. [Configuration Reference](#configuration-reference)
12. [API Reference](#api-reference)
13. [Embeddable Widget](#embeddable-widget)
14. [AI-Assisted Development](#ai-assisted-development)
15. [Roadmap](#roadmap)

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│  Clients                                                    │
│  Browser (Blade UI) · REST API consumers · Embedded Widget  │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────┐
│  Laravel 12 Application Layer                               │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  Web Routes  │  │  API Routes  │  │  Widget Endpoint │  │
│  │  (session)   │  │  (token)     │  │  (CORS / public) │  │
│  └──────┬───────┘  └──────┬───────┘  └────────┬─────────┘  │
│         └────────────┬────┘                   │             │
│                      ▼                        │             │
│  ┌───────────────────────────────────────┐    │             │
│  │  AIManager (Orchestrator)             │◄───┘             │
│  │  · Conversation state (MemoryService) │                  │
│  │  · RAG context injection              │                  │
│  │  · Provider and model resolution      │                  │
│  │  · Streaming / non-streaming dispatch │                  │
│  └────────┬───────────────┬──────────────┘                  │
│           │               │                                 │
│           ▼               ▼                                 │
│  ┌─────────────┐  ┌──────────────────────┐                  │
│  │  RagService │  │  AIProvider Contract │                  │
│  │  · embed    │  │  · OllamaProvider    │                  │
│  │  · search   │  │  · (extensible)      │                  │
│  │  · assemble │  └──────────────────────┘                  │
│  └────┬────────┘                                            │
│       │                                                     │
│       ▼                                                     │
│  ┌─────────────┐   ┌──────────────────────┐                 │
│  │  Qdrant     │   │  MySQL               │                 │
│  │  (vectors)  │   │  (structured state)  │                 │
│  └─────────────┘   └──────────────────────┘                 │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Queue Workers (ProcessDocumentJob)                 │    │
│  │  extract → chunk → embed → store DB → upsert Qdrant │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                        │
                        ▼
           ┌────────────────────────┐
           │  Ollama  (local)       │
           │  nomic-embed-text      │  ← embedding model
           │  phi / llama3 / etc.   │  ← generation model
           └────────────────────────┘
```

The system deliberately separates three concerns: **document ingestion** (queue workers), **context retrieval** (RagService + Qdrant), and **response generation** (AIManager + Ollama). These are independently scalable and independently replaceable.

---

## Design Decisions and Tradeoffs

### Why Ollama for local inference?

Running inference locally eliminates per-token API cost and keeps all customer data on-premise. The tradeoff is hardware dependency — a machine with 8GB+ RAM is practical, 16GB+ is comfortable for larger models. The `AIProvider` contract ensures the application is not coupled to Ollama; adding an OpenAI or Groq provider is a single class implementation.

### Why Qdrant for vector storage?

Qdrant supports payload filtering natively, which is the mechanism used for tenant-scoped retrieval. Every embedding is stored with a `tenant_id` payload field; searches are filtered server-side in Qdrant, meaning no application-side tenant leakage is possible even under concurrent load. A single collection scales across all tenants — this avoids the operational complexity of per-tenant collections while maintaining strict isolation.

### Why a database queue (not Redis) by default?

Reducing the baseline infrastructure requirement to MySQL + Qdrant + Ollama makes self-hosted deployment accessible. The queue driver is an environment variable — switching to Redis Horizon in production requires no code changes.

### Why SHA-256 token hashing without Sanctum?

Sanctum adds value in multi-guard SPA scenarios. This project uses a single API guard with hashed tokens stored in a `users.api_token` column — a pattern that is simpler to audit, has no additional package surface area, and satisfies the security model without the Sanctum dependency.

### Context window management

`AIManager::buildMessages` slices the last 10 conversation turns before injection. This is a deliberate budget decision: 10 turns at typical message lengths fits within Ollama's default 2048 token context without truncation. The `OLLAMA_NUM_CTX` env var allows operators to raise this limit on capable hardware.

### RAG as best-effort

`RagService::getContext` catches all `Throwable` and returns an empty string. This means a Qdrant outage or a missing embedding model degrades the experience (no knowledge context) without breaking the chat surface. This is the correct failure mode for a support assistant — partial functionality is better than a hard failure.

---

## Processing Lifecycle

### Document Ingestion

```
POST /api/v1/documents
        │
        ▼
DocumentController::store
  · validates type (pdf/txt/url/text)
  · stores file on local disk
  · creates Document record (status: pending)
  · dispatches ProcessDocumentJob to queue
        │
        ▼ (async worker)
ProcessDocumentJob
  · extracts text by type
    ├── pdf  → smalot/pdfparser
    ├── docx → ZipArchive XML extraction
    ├── csv  → row-to-sentence conversion
    ├── url  → HTTP fetch + strip_tags
    └── txt  → direct read
  · chunks text (500-word windows, 50-word overlap)
  · for each chunk:
      ├── EmbeddingService → POST /api/embeddings (Ollama)
      ├── stores DocumentChunk in MySQL (id used as Qdrant point ID)
      └── QdrantService::upsert → stores vector + payload
  · updates Document status: ready (or failed on error)
```

### Conversation (RAG Chat)

```
POST /api/v1/chat  (or /chat/sse for streaming)
        │
        ▼
AIManager::generateWithMemory (or streamWithMemory)
  │
  ├── MemoryService::getOrCreateConversation
  │     · idempotent session lookup/creation
  │     · scoped to tenant_id
  │
  ├── MemoryService::getHistory
  │     · last 10 turns from MySQL
  │
  ├── RagService::getContext          ← best-effort, fails open
  │     · embed query via Ollama
  │     · Qdrant search (top-5, filtered by tenant_id)
  │     · assemble chunks into context block
  │
  ├── buildMessages
  │     · system prompt + optional RAG context
  │     · conversation history (last 10 turns)
  │     · current user message
  │
  └── OllamaProvider::generate / stream
        · POST /api/chat (Ollama)
        · streaming: Server-Sent Events to client
```

---

## Service Layer

```
app/Services/
├── AI/
│   ├── Contracts/
│   │   └── AIProvider.php          # generate() + stream() — all providers implement this
│   ├── DTOs/
│   │   └── AIResponse.php          # typed response — success, model, message
│   ├── Providers/
│   │   └── OllamaProvider.php      # Ollama HTTP client implementation
│   ├── AIManager.php               # orchestrator — memory + RAG + provider dispatch
│   ├── EmbeddingService.php        # text → float[] via Ollama /api/embeddings
│   └── MemoryService.php           # conversation CRUD — get/create/append/read
│
├── RAG/
│   └── RagService.php              # embed query → search Qdrant → assemble context
│
└── Qdrant/
    └── QdrantService.php           # Qdrant REST client — upsert, search, delete
```

The `AIProvider` contract (`generate` + `stream`) is the only interface boundary between the orchestration layer and any LLM backend. Adding OpenAI, Groq, Anthropic, or any other provider requires:
1. Implementing `AIProvider`
2. Adding a case to `AIManager::resolveProvider`
3. Registering models in `config/ai.php`

No other files change.

---

## Multi-Tenancy Model

Tenant isolation is enforced at two independent layers:

**Database layer** — every primary table (`users`, `documents`, `document_chunks`, `conversations`, `ai_logs`) carries a `tenant_id` foreign key. All queries are scoped by authenticated tenant. There is no shared row between tenants in any table.

**Vector store layer** — Qdrant stores a `tenant_id` payload field alongside every embedding. All searches include a filter condition on this field, evaluated server-side. Even a query that bypasses application logic cannot retrieve a different tenant's vectors without supplying the correct filter.

This dual-isolation model means a bug in one layer does not expose data from the other — both layers must fail simultaneously for a cross-tenant leak to occur.

---

## Scalability Considerations

**Current model (single server, low volume)**
- One PHP-FPM process + one queue worker handles the typical pilot deployment.
- Qdrant and MySQL run in Docker on the same host.

**Scaling the queue (medium volume)**
- Increase queue workers: `php artisan queue:work --queue=default --sleep=1`
- Switch `QUEUE_CONNECTION=redis` and deploy Laravel Horizon for visibility and worker management.
- Document ingestion is CPU-bound (embedding), so workers scale horizontally without shared state issues — each job is idempotent (delete existing chunks, re-embed, re-upsert).

**Scaling inference (high volume)**
- Replace `OllamaProvider` with a provider that targets a GPU server or cloud inference API.
- The contract boundary means zero application changes; only provider implementation and config change.
- Multiple Ollama instances behind a load balancer work without state coordination because Ollama is stateless per-request.

**Scaling Qdrant**
- Qdrant supports distributed mode (sharding + replication) for high-availability deployments.
- The `QdrantService` client points to a single `QDRANT_URL` — putting a load balancer in front requires no application change.

**Tenant-aware rate limiting** — not yet implemented; planned as a middleware layer on `POST /api/v1/chat`.

---

## Extension Points

| What to extend | Where | How |
|---|---|---|
| New LLM provider | `Services/AI/Providers/` | Implement `AIProvider`, add case to `AIManager::resolveProvider` |
| New document type | `Jobs/ProcessDocumentJob.php` | Add extraction branch to `extractText()` |
| New embedding model | `.env` + Qdrant collection | Update `OLLAMA_EMBEDDING_MODEL` + `QDRANT_VECTOR_SIZE`; recreate collection |
| Auth strategy | `config/auth.php` | The token guard is swappable; Sanctum, Passport, or JWT slot in |
| Queue backend | `.env` | `QUEUE_CONNECTION=redis` — no code changes |
| Storage backend | `config/filesystems.php` | Swap `local` for `s3` — no code changes in the job |

---

## Observability

**AI request logging** — every call through `AIManager` writes to `ai_logs` (model, tokens, latency, tenant). Query with `GET /api/v1/ai/logs`.

**Document status tracking** — `documents.status` transitions: `pending → processing → ready | failed`. Failed documents include an error message in the `processing_error` column for operator diagnosis.

**Health endpoint** — `GET /api/health` returns service status (database reachability, queue connectivity). Suitable for load balancer health checks and uptime monitoring.

**Queue visibility** — switch to `QUEUE_CONNECTION=redis` and add Horizon to get real-time queue metrics, failure tracking, and retry management via the Horizon dashboard.

**Planned** — structured JSON logging for all service boundaries; OpenTelemetry trace propagation across the RAG pipeline.

---

## Tech Stack

| Layer | Technology | Rationale |
|---|---|---|
| Backend | Laravel 12, PHP 8.2+ | Mature queue, DI container, ORM — reduces infrastructure boilerplate |
| Database | MySQL 8 | Structured tenant/conversation state with ACID guarantees |
| Vector store | Qdrant | Native payload filtering for tenant-scoped retrieval |
| LLM + embeddings | Ollama | Self-hosted; no token cost; data stays on-premise |
| Default model | phi (3B) | Low RAM footprint; fast cold start; swappable per-request |
| Embedding model | nomic-embed-text | 768-dim; good retrieval quality; runs locally |
| Frontend | Blade + Tailwind (CDN) | No build step; no Node.js dependency for deployment |
| Background jobs | Laravel database queue | Zero extra infrastructure; Redis-ready via env var |

---

## Getting Started

### Prerequisites

- PHP 8.2+ with extensions: `pdo_mysql`, `zip`, `curl`, `mbstring`
- Composer
- Docker (for MySQL and Qdrant)
- Ollama — [ollama.com](https://ollama.com)

### 1. Clone

```bash
git clone https://github.com/shafi-rahman/ai-saas-support-agent.git
cd ai-saas-support-agent/laravel-app
```

### 2. Pull Ollama models

```bash
ollama pull nomic-embed-text   # embedding model (required)
ollama pull phi                # chat model — 3B, recommended for constrained RAM
```

### 3. Start infrastructure

```bash
docker compose up -d
# starts MySQL 8 on :3306 and Qdrant on :6333
```

### 4. Configure and migrate

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
```

The default `.env.example` targets the Docker Compose setup. No changes required if you followed step 3.

### 5. Run

**Terminal 1 — web server:**
```bash
php artisan serve
```

**Terminal 2 — queue worker** (document processing runs here):
```bash
php artisan queue:work
```

Open `http://localhost:8000`, register a company account, upload documents, and start chatting.

---

## Configuration Reference

Key environment variables with operational notes:

```env
# Inference
OLLAMA_URL=http://localhost:11434/api/chat
OLLAMA_EMBEDDING_URL=http://localhost:11434/api/embeddings
OLLAMA_EMBEDDING_MODEL=nomic-embed-text

# Context window — lower these on machines with <8GB RAM
OLLAMA_NUM_CTX=2048
OLLAMA_NUM_PREDICT=512

# Vector store — QDRANT_VECTOR_SIZE must match embedding model output dimensions
QDRANT_HOST=localhost
QDRANT_PORT=6333
QDRANT_COLLECTION=knowledge_base
QDRANT_VECTOR_SIZE=768   # nomic-embed-text outputs 768-dim

# Queue — switch to redis for production; no code changes required
QUEUE_CONNECTION=database
```

---

## API Reference

All endpoints are under `/api/v1/`. Token-authenticated routes require `Authorization: Bearer <token>`.

### Authentication

```bash
# Register (creates tenant + admin user, returns token)
curl -X POST /api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Acme","email":"admin@acme.com","password":"secret"}'

# Login
curl -X POST /api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@acme.com","password":"secret"}'
```

### Documents (admin)

```bash
# Upload PDF
curl -X POST /api/v1/documents \
  -H "Authorization: Bearer TOKEN" \
  -F "title=Product FAQ" -F "type=pdf" -F "file=@faq.pdf"

# Upload URL
curl -X POST /api/v1/documents \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Pricing Page","type":"url","url":"https://acme.com/pricing"}'

# List, get, delete, reprocess
curl /api/v1/documents                    -H "Authorization: Bearer TOKEN"
curl /api/v1/documents/1                  -H "Authorization: Bearer TOKEN"
curl -X DELETE /api/v1/documents/1        -H "Authorization: Bearer TOKEN"
curl -X POST /api/v1/documents/1/reprocess -H "Authorization: Bearer TOKEN"
```

### Chat

```bash
# Non-streaming
curl -X POST /api/v1/chat \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"prompt":"What is your refund policy?","session_id":"s-abc","model":"phi"}'

# Server-Sent Events (streaming)
curl -X POST /api/v1/chat/sse \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"prompt":"Explain your pricing","session_id":"s-abc","model":"phi"}'
```

### Observability

```bash
curl /api/v1/conversations          -H "Authorization: Bearer TOKEN"
curl /api/v1/conversations/s-abc    -H "Authorization: Bearer TOKEN"
curl /api/v1/ai/logs                -H "Authorization: Bearer TOKEN"
curl /api/health
```

---

## Embeddable Widget

The widget endpoint is authenticated by a per-tenant widget key (no Bearer token required), making it safe to embed in public-facing sites.

```bash
curl -X POST /api/widget/chat \
  -H "Content-Type: application/json" \
  -d '{"widget_key":"WIDGET_KEY","prompt":"What is your return policy?","session_id":"visitor-123"}'
```

Embed the chat bubble by pasting this snippet before `</body>`. The widget key and configuration are available from the dashboard.

```html
<script
  src="https://yourapp.com/widget.js"
  data-key="WIDGET_KEY"
  data-title="Acme Support"
  data-color="#2563eb"
  data-position="right"
  data-model="phi">
</script>
```

---

## Project Structure

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/DocumentController.php   # document CRUD + reprocess (API)
│   │   │   ├── AIController.php               # chat + SSE streaming (API)
│   │   │   ├── ConversationController.php     # history + AI logs (API)
│   │   │   ├── WidgetController.php           # public widget endpoint (CORS)
│   │   │   └── Web/                           # session-authenticated web controllers
│   │   └── Middleware/
│   │       ├── EnsureRole.php                 # role guard (admin / user)
│   │       └── ApiKeyMiddleware.php           # token validation
│   ├── Jobs/
│   │   └── ProcessDocumentJob.php             # ingestion pipeline: extract → chunk → embed → index
│   ├── Models/
│   │   ├── Tenant.php                         # organisation root
│   │   ├── User.php                           # belongs to tenant; role; api_token
│   │   ├── Document.php                       # uploaded knowledge artifact
│   │   ├── DocumentChunk.php                  # chunked text; DB id = Qdrant point id
│   │   ├── Conversation.php                   # chat session (scoped to tenant)
│   │   ├── Message.php                        # individual turn
│   │   └── AILog.php                          # per-request telemetry
│   └── Services/
│       ├── AI/
│       │   ├── Contracts/AIProvider.php       # provider interface (generate + stream)
│       │   ├── DTOs/AIResponse.php            # typed response object
│       │   ├── Providers/OllamaProvider.php   # Ollama HTTP implementation
│       │   ├── AIManager.php                  # orchestrator
│       │   ├── EmbeddingService.php           # text → vector
│       │   └── MemoryService.php              # conversation state
│       ├── RAG/RagService.php                 # retrieval-augmented generation pipeline
│       └── Qdrant/QdrantService.php           # vector store client
├── routes/
│   ├── api.php                                # token-authenticated REST
│   └── web.php                                # session-authenticated UI
├── resources/views/                           # Blade templates
├── database/migrations/                       # schema version history
├── public/widget.js                           # self-contained embeddable widget
├── docker-compose.yml                         # MySQL + Qdrant
└── .env.example                               # fully documented configuration
```

---

## Supported Document Types

| Type | Extraction method |
|---|---|
| PDF | `smalot/pdfparser` (optional; install separately) |
| Word (.docx) | PHP `ZipArchive` — reads `word/document.xml` directly |
| CSV | Rows converted to `column: value` natural-language sentences |
| TXT | Direct file read |
| Plain text | Pasted in-form; no file required |
| URL | HTTP fetch + `strip_tags` — suitable for public documentation pages |

PDF support requires `smalot/pdfparser`:
```bash
composer require smalot/pdfparser
```

Without it, PDF uploads fail with a descriptive error; all other types work.

---

## Troubleshooting

**Documents remain in `pending` status**
The queue worker is not running. Start it: `php artisan queue:work`

**Embedding errors / 500 on chat**
Ollama is not running or the embedding model is not available:
```bash
ollama serve
ollama pull nomic-embed-text
```

**Database connection refused**
Docker containers are not running: `docker compose up -d`

**First response is slow (30–60s)**
Ollama is loading the model into RAM. Response time normalises on subsequent turns. Consider `ollama pull phi` (3B) over larger models on RAM-constrained machines.

**Qdrant search returns empty results**
The collection may not exist yet. It is created automatically on first upsert. If documents have been processed but search returns nothing, check that `QDRANT_VECTOR_SIZE` matches the embedding model dimensions.

---

## AI-Assisted Development

This project was developed with AI assistance as part of the engineering workflow. Claude (Anthropic) was used as a development accelerator — reviewing service contracts, surfacing edge cases, suggesting security considerations, and generating scaffolding for well-defined specifications.

All architectural decisions in this repository — the provider contract design, tenant isolation strategy, RAG fallback behavior, context window budgeting, queue-first ingestion model, and data isolation layering — were designed, reasoned through, and validated by the author.

AI assistance did not replace engineering judgment; it compressed implementation time for components with well-understood specifications. The same standard applies here as in any senior engineering context: AI output was reviewed, tested, adapted, and owned before it shipped.

This disclosure is intentional. Using AI tooling responsibly as a productivity multiplier is, in my view, a professional engineering practice — not something to obscure.

---

## Roadmap

| Status | Item |
|---|---|
| Done | Multi-tenant isolation (DB + vector) |
| Done | RAG pipeline (chunk → embed → retrieve → inject) |
| Done | Streaming SSE responses |
| Done | Embeddable JS widget |
| Done | Per-tenant AI logging and conversation history |
| Done | Document reprocessing (idempotent re-embed) |
| Planned | Docker Compose with Ollama included |
| Planned | Tenant admin user invite flow |
| Planned | Per-tenant rate limiting middleware |
| Planned | Structured JSON logging + OpenTelemetry trace IDs |
| Planned | Alternative provider implementations (OpenAI, Groq) |
| Planned | Analytics dashboard (token usage, retrieval quality) |
| Planned | Webhook events on document status transitions |

---

## Screenshots

### Authentication
![Register](./register.png)
![Login](./login.png)

### Dashboard
![Dashboard](./ai-dashboard.png)

### AI Assistant
![AI Assistant](./ai-assistent.png)

### Embeddable Widget
![Widget](./code-pest.png)

---

## Author

**Shafi Ur Rahman** — Senior PHP / Laravel Engineer

[![LinkedIn](https://img.shields.io/badge/LinkedIn-Shafi%20Ur%20Rahman-0A66C2?logo=linkedin&style=flat-square)](https://www.linkedin.com/in/shafirahman-com/)

---

## License

MIT — free to use, modify, and distribute.
