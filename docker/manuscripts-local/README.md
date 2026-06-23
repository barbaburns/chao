# Manuscripts Local Stack

Minimal local stack for the manuscript pilot.

Included services:

- `postgres`
- `redis`
- `pdf-worker`

This stack does **not** include `eScriptorium` itself yet. It is meant to validate the first layer and the adapter contract:

- Docker is running
- the worker container builds
- `pdftoppm` is available inside the worker
- the worker can accept a PDF job
- the worker can emit synthetic `raw_text + layout_data` for visual highlight tests
- the worker can normalize a real `eScriptorium + Kraken` line payload into the same internal format

## Start

From this directory, run:

```bash
docker compose up --build
```

To run in the background:

```bash
docker compose up --build -d
```

## Check status

```bash
docker compose ps
```

## Check logs

```bash
docker compose logs -f
```

## Health check

```bash
curl http://localhost:8002/health
```

Expected response:

```json
{"ok":true}
```

## Stop

```bash
docker compose down
```

## First PDF job test

Replace the URL below with a real PDF that your local WordPress can expose:

```bash
curl -X POST http://localhost:8002/jobs/pdf \
  -H "Content-Type: application/json" \
  -d '{
    "manuscript_id": 123,
    "pdf_url": "http://host.docker.internal:10004/wp-content/uploads/2026/06/manuscrito.pdf"
  }'
```

If extraction succeeds, the response will include:

- `job_id`
- `page_count`
- `pages[]`

## Notes

- If `ESCRIPTORIUM_BASE_URL`, `ESCRIPTORIUM_TOKEN`, and `ESCRIPTORIUM_PROJECT_ID` are configured, the worker will:
  - ensure a document like `doc-<manuscript_id>` exists in eScriptorium
  - upload each page image as a part like `page-<n>`
  - trigger segmentation through the document-level route and pass the current part id in `parts`
  - trigger transcription only when `ESCRIPTORIUM_TRANSCRIBE_MODEL_ID` is configured
  - poll the part status and fetch line transcriptions from `/api/documents/<document>/parts/<part>/lines/`
  - normalize the response into `raw_text + layout_data`
- If the eScriptorium response already contains transcribed lines, the worker prefers that text over the local fallback.
- `ESCRIPTORIUM_TRANSCRIPTION_ID` can be used to force a specific transcription layer.
- `ESCRIPTORIUM_POLL_SECONDS` and `ESCRIPTORIUM_POLL_ATTEMPTS` control how long the worker waits for real HTR output before falling back.
- No piloto local, `ESCRIPTORIUM_POLL_ATTEMPTS=1` evita que o admin fique preso esperando varias rodadas por pagina quando a instancia ainda nao tem modelos de HTR configurados.
- `ESCRIPTORIUM_SEGMENT_PATH_TEMPLATE` and `ESCRIPTORIUM_TRANSCRIBE_PATH_TEMPLATE` are configurable because task-start endpoints can vary by deployment details. In the local stack, the defaults are `/documents/{document_id}/segment/` and `/documents/{document_id}/transcribe/`.
- `ESCRIPTORIUM_SEGMENT_MODEL_ID` and `ESCRIPTORIUM_TRANSCRIBE_MODEL_ID` let you send explicit model ids when your instance requires them.
- In this local eScriptorium build, segmentation works without an explicit model, but transcription returns `400` until `ESCRIPTORIUM_TRANSCRIBE_MODEL_ID` points to a valid HTR model.
- The current worker extracts PDF pages and returns page metadata.
- When the PDF has embedded text, the worker tries to read it with `pdftotext`.
- When the PDF does not have embedded text, the worker falls back to a synthetic local-pilot transcription and mock `layout_data`.
- This mock mode is enabled by `MANUSCRIPT_WORKER_ENABLE_MOCK_LAYOUT=1`.
- If no real HTR lines are available yet, the worker still falls back to `pdftotext` or the synthetic local pilot text.
