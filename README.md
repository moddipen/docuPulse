# docuPulse

**AI-powered contract analysis — retrieval-augmented Q&A over legal documents, with tool-calling agents, semantic caching, and per-tenant isolation.**

docuPulse ingests a contract, splits it into section-aware chunks, embeds them into a
PostgreSQL + pgvector store, and answers natural-language questions about the document
by retrieving the most relevant passages and passing them to an LLM. The answering agent
can also take actions — for example, flagging a high-value contract for legal review —
via tool calls. It is built on Laravel and the [`laravel/ai`](https://github.com/laravel/ai)
SDK, and is multi-tenant throughout.

> **Interface note:** docuPulse is currently driven through **Artisan console commands**
> (ingest, ask, search). It is not yet exposed as an HTTP API — the only web route is the
> default Laravel welcome page.

---

## Key features

- **Retrieval-Augmented Generation (RAG) over contracts** — ask a question in plain English;
  the app embeds it, finds the nearest contract chunks by vector similarity, and grounds the
  LLM's answer in those passages only.
- **Section-aware chunking** — a custom `ContractChunker` splits documents on paragraph and
  heading boundaries, keeps section headings attached to their body, and targets ~500 words
  per chunk instead of blind fixed-size splitting.
- **pgvector semantic search** — embeddings are stored in a `vector(1536)` column and queried
  with pgvector's cosine-distance operator (`<=>`), ordered nearest-first.
- **Tool-calling agent** — a `ContractAnalyst` agent (Laravel AI SDK) answers strictly from the
  supplied context and can invoke tools during analysis:
  - `FlagHighValueContract` — records a review flag when a contract value exceeds $5,000.
  - `SendLegalAlert` — emits a legal-team alert for a flagged contract.
- **Semantic answer cache** — before calling the LLM, docuPulse looks for a previously answered
  question within a cosine-distance threshold. A hit returns the cached answer and skips the
  LLM call entirely, saving latency and tokens.
- **Token & cost logging** — every LLM call is recorded (`AiLog`) with model, input/output tokens,
  and an estimated cost computed from a configurable per-model price table.
- **Multi-tenancy** — `tenant_id` scopes chunks, cached answers, and flags. Retrieval, ingestion,
  and deletion are all filtered by tenant so one tenant's data never leaks into another's answers.
- **Background ingestion** — ingestion runs as a queued job (`IngestContractJob`) with retries,
  backoff, and per-tenant uniqueness/overlap locks.
- **Provider failover** — the ask flow is configured to fall back from OpenAI to Anthropic if the
  primary provider fails.
- **Streaming answers** — a streaming command emits the model's response token-by-token as it
  arrives.

---

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13 |
| Language | PHP 8.3+ (Docker image uses PHP 8.4) |
| AI SDK | `laravel/ai` ^0.8 |
| Embeddings | OpenAI `text-embedding-3-small` (1536 dimensions) |
| Chat / agent | OpenAI chat model via the AI SDK, with Anthropic (Claude) failover; model is configurable |
| Vector store | PostgreSQL + [pgvector](https://github.com/pgvector/pgvector) (`vector(1536)`, cosine `<=>`) |
| Queue | Database driver (queued ingestion job) |
| Containerization | Docker; deployable to Railway |

---

## How the AI integration works

1. **Ingest** — `IngestContractJob` takes a contract's text, runs it through `ContractChunker`,
   and for each chunk calls the embeddings model (via the SDK's `Str::of($text)->toEmbeddings()`)
   to produce a 1536-dimension vector. Chunks + embeddings + `tenant_id` are stored in
   `document_chunks`. A custom `Vector` cast enforces the 1536-dimension contract on write.
2. **Ask** — the user's question is embedded the same way. `DocumentChunk::nearestTo()` runs a
   pgvector cosine-distance search (`embedding <=> :vector`) scoped to the tenant and returns the
   top matches.
3. **Cache check** — the question embedding is compared against `answer_cache`; if a prior question
   is within the distance threshold, its stored answer is returned and the LLM is not called.
4. **Generate** — on a cache miss, the retrieved chunks are assembled into a context block and sent
   to the `ContractAnalyst` agent, which is instructed to answer *only* from that context. During
   the call the agent may invoke its tools (e.g. flag a high-value contract).
5. **Log & cache** — the answer is displayed, token usage and estimated cost are written to
   `ai_logs`, and the question/answer/embedding are stored in `answer_cache` for next time.

---

## Getting started

### Prerequisites

- PHP 8.3+ and Composer
- PostgreSQL with the **pgvector** extension available
- An OpenAI API key (and optionally an Anthropic key for failover)

### Installation

```bash
# 1. Clone
git clone https://github.com/moddipen/docuPulse.git
cd docuPulse

# 2. Install dependencies
composer install

# 3. Environment
cp .env.example .env
php artisan key:generate
```

Then edit `.env` and set at minimum:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=docupulse
DB_USERNAME=docupulse
DB_PASSWORD=secret

OPENAI_API_KEY=sk-...            # required
ANTHROPIC_API_KEY=sk-ant-...     # optional, enables failover
QUEUE_CONNECTION=database
```

```bash
# 4. Migrate (creates tables and enables the pgvector extension)
php artisan migrate
```

> The `document_chunks` migration runs `CREATE EXTENSION IF NOT EXISTS vector`, so the database
> user needs permission to enable extensions (pgvector must be installed on the server).

### Running

Ingestion is queued, so run a worker to process jobs:

```bash
php artisan queue:work
```

---

## Usage (Artisan commands)

```bash
# Ingest the contract for a tenant (queued — needs a running worker)
php artisan docupulse:ingest --tenant_id=1

# Ask a question, scoped to a tenant
php artisan docupulse:ask "What are the total fees?" --tenant_id=1
# → USD $12,000.
#   [cache MISS] fresh answer from LLM     (or [cache HIT] on a repeat/similar question)

# Inspect raw retrieval: nearest chunks and their cosine distances
php artisan docupulse:search "liability cap"

# Stream the answer token-by-token
php artisan docupulse:ask-question-streaming "Summarize the termination clause"
```

A typical isolation check — the same question against two tenants returns each tenant's own data:

```bash
php artisan docupulse:ask "What are the total fees?" --tenant_id=1   # → $12,000
php artisan docupulse:ask "What are the total fees?" --tenant_id=2   # → $99,000
```

---

## Data model

| Table | Purpose |
|---|---|
| `document_chunks` | Contract chunks with `content`, `token_count`, `embedding vector(1536)`, `tenant_id` |
| `answer_cache` | Prior `question` / `answer` / `embedding` for semantic caching, per tenant |
| `contract_flags` | Flags raised by the agent's tools (`amount`, `reason`, `tenant_id`) |
| `ai_logs` | Per-call `model`, `input_tokens`, `output_tokens`, `estimated_cost` |

---

## Tests

The project uses PHPUnit. Run the suite with:

```bash
php artisan test
```

Included tests cover the high-value-contract flagging tool
(`tests/Feature/FlagHighValueContractTest.php`), which constructs the tool with a tenant,
invokes `handle()` directly, and asserts both the returned confirmation and the persisted
`contract_flags` row.

---

## Deployment

A `Dockerfile` (PHP 8.4 CLI base with Composer and the `pdo_pgsql` extension) is included for
container deployment to Railway. The container runs migrations and starts the app on Railway's
injected `$PORT`. Credentials are read from environment variables — no `.env` is committed.
