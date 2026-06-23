import sys
import types


class _DummyFlaskApp:
    def get(self, *_args, **_kwargs):
        def decorator(func):
            return func
        return decorator

    def post(self, *_args, **_kwargs):
        def decorator(func):
            return func
        return decorator


flask_stub = types.SimpleNamespace(
    Flask=lambda *_args, **_kwargs: _DummyFlaskApp(),
    jsonify=lambda payload: payload,
    request=types.SimpleNamespace(url_root="http://127.0.0.1:8002/"),
    send_from_directory=lambda *_args, **_kwargs: None,
)

requests_stub = types.SimpleNamespace(get=lambda *_args, **_kwargs: None)

sys.modules.setdefault("flask", flask_stub)
sys.modules.setdefault("requests", requests_stub)

import worker


def assert_true(condition, message):
    if not condition:
        raise AssertionError(message)


def assert_equal(expected, actual, message):
    if expected != actual:
        raise AssertionError(f"{message}\nExpected: {expected!r}\nActual:   {actual!r}")


layout = worker.build_mock_layout_data(
    "Casa grande. Outra casa na margem.",
    image_width=1200,
    image_height=1800,
)

fallback_payload = worker.build_page_transcription_payload(
    page_number=2,
    manuscript_id=9226,
    pdf_path="/tmp/missing.pdf",
    image_path="/tmp/missing.png",
)
escriptorium_lines = [
    {
        "pk": 101,
        "baseline": [[20, 140], [260, 140]],
        "mask": [[18, 118], [266, 118], [266, 154], [18, 154]],
        "transcriptions": [
            {
                "pk": 301,
                "transcription": 88,
                "content": "Caderno antigo",
                "graphs": [
                    {"c": "C", "poly": [[20, 120], [46, 120], [46, 150], [20, 150]], "confidence": 0.97},
                    {"c": "a", "poly": [[48, 120], [62, 120], [62, 150], [48, 150]], "confidence": 0.96},
                    {"c": "d", "poly": [[64, 120], [80, 120], [80, 150], [64, 150]], "confidence": 0.95},
                    {"c": "e", "poly": [[82, 120], [96, 120], [96, 150], [82, 150]], "confidence": 0.95},
                    {"c": "r", "poly": [[98, 120], [108, 120], [108, 150], [98, 150]], "confidence": 0.94},
                    {"c": "n", "poly": [[110, 120], [126, 120], [126, 150], [110, 150]], "confidence": 0.94},
                    {"c": "o", "poly": [[128, 120], [144, 120], [144, 150], [128, 150]], "confidence": 0.94},
                    {"c": " ", "poly": [[146, 120], [154, 120], [154, 150], [146, 150]], "confidence": 1.0},
                    {"c": "a", "poly": [[156, 120], [170, 120], [170, 150], [156, 150]], "confidence": 0.95},
                    {"c": "n", "poly": [[172, 120], [188, 120], [188, 150], [172, 150]], "confidence": 0.94},
                    {"c": "t", "poly": [[190, 120], [200, 120], [200, 150], [190, 150]], "confidence": 0.93},
                    {"c": "i", "poly": [[202, 120], [208, 120], [208, 150], [202, 150]], "confidence": 0.93},
                    {"c": "g", "poly": [[210, 120], [226, 120], [226, 150], [210, 150]], "confidence": 0.92},
                    {"c": "o", "poly": [[228, 120], [244, 120], [244, 150], [228, 150]], "confidence": 0.92},
                ],
            }
        ],
    }
]
escriptorium_payload = worker.normalize_escriptorium_part(
    part_payload={"pk": 11, "workflow": {"transcribe": "done"}},
    transcription_id=88,
    lines_payload=escriptorium_lines,
)
selected_payload = worker.choose_page_transcription_payload(escriptorium_payload, fallback_payload)

assert_equal("Casa grande. Outra casa na margem.", layout["text"], "Mock layout should keep the original text")
assert_true(len(layout["lines"]) >= 1, "Mock layout should create at least one line")
assert_true(len(layout["lines"][0]["words"]) >= 1, "Mock layout should create word boxes")
assert_equal("Casa", layout["lines"][0]["words"][0]["text"], "Mock layout should keep word text")
assert_true(layout["lines"][0]["words"][0]["bbox"]["width"] > 0, "Mock layout should assign positive bbox width")
assert_true(fallback_payload["raw_text"] != "", "Fallback transcription should provide synthetic text when PDF text is unavailable")
assert_true(len(fallback_payload["layout_data"]["lines"]) >= 1, "Fallback transcription should include layout lines")
assert_true(fallback_payload["layout_data"]["lines"][0]["words"][0]["bbox"]["height"] > 0, "Fallback transcription should include positive word boxes")
assert_equal("Caderno antigo", escriptorium_payload["raw_text"], "eScriptorium payload should concatenate real transcribed text")
assert_equal("Caderno", escriptorium_payload["layout_data"]["lines"][0]["words"][0]["text"], "eScriptorium payload should derive word tokens from character graphs")
assert_true(escriptorium_payload["layout_data"]["lines"][0]["bbox"]["width"] > 0, "eScriptorium payload should expose line bounding boxes")
assert_equal("escriptorium-kraken", escriptorium_payload["text_source"], "eScriptorium payload should tag the real provider source")
assert_equal("Caderno antigo", selected_payload["raw_text"], "Worker should prefer the real HTR text over the local fallback when available")
assert_equal(
    {"parts": [88], "override": True},
    worker.build_escriptorium_segment_payload(88),
    "Segmentation payload should target the current part through the document-level endpoint",
)
assert_equal(
    None,
    worker.build_escriptorium_transcribe_payload(88),
    "Transcription payload should be skipped when no HTR model is configured",
)

skipped_transcribe = worker.trigger_escriptorium_task(
    "/documents/{document_id}/transcribe/",
    {"document_id": 77, "part_id": 88},
    None,
    "Configure ESCRIPTORIUM_TRANSCRIBE_MODEL_ID to enable HTR transcription.",
)

assert_equal("skipped", skipped_transcribe["status"], "Worker should skip transcription requests when no HTR model is configured")
assert_equal(
    "/documents/77/transcribe/",
    skipped_transcribe["path"],
    "Worker should still report the document-level transcription path when skipping",
)

task_calls = []
original_enabled = worker.escriptorium_enabled
original_ensure_document = worker.ensure_escriptorium_document
original_upload_part = worker.upload_escriptorium_part
original_start_workflow = getattr(worker, "start_escriptorium_part_workflow", None)
original_collect_result = getattr(worker, "collect_escriptorium_part_result", None)
original_find_document = worker.find_escriptorium_document_by_name
original_request = worker.escriptorium_request
original_project_id = worker.ESCRIPTORIUM_PROJECT_ID

worker.escriptorium_enabled = lambda: True
worker.ensure_escriptorium_document = lambda manuscript_id: {"pk": 77, "name": f"doc-{manuscript_id}"}
worker.upload_escriptorium_part = lambda document_id, image_path, page_number: {
    "pk": 88,
    "workflow": {"segment": "queued", "transcribe": "queued"},
}
worker.start_escriptorium_part_workflow = lambda document_id, part_id: task_calls.append(
    ("workflow", document_id, part_id)
)
worker.collect_escriptorium_part_result = lambda document_id, part_id: {
    "raw_text": "Texto real do HTR",
    "layout_data": {
        "text": "Texto real do HTR",
        "lines": [
            {
                "text": "Texto real do HTR",
                "bbox": {"x": 10, "y": 20, "width": 120, "height": 18},
                "words": [
                    {"text": "Texto", "bbox": {"x": 10, "y": 20, "width": 45, "height": 18}},
                ],
            }
        ],
    },
    "text_source": "escriptorium-kraken",
    "htr_status": "done",
}

try:
    htr_payload = worker.submit_page_to_escriptorium(
        image_path="/tmp/page-1.png",
        manuscript_id=9226,
        page_number=1,
    )
finally:
    worker.escriptorium_enabled = original_enabled
    worker.ensure_escriptorium_document = original_ensure_document
    worker.upload_escriptorium_part = original_upload_part

    if original_start_workflow is not None:
        worker.start_escriptorium_part_workflow = original_start_workflow

    if original_collect_result is not None:
        worker.collect_escriptorium_part_result = original_collect_result

assert_equal([("workflow", 77, 88)], task_calls, "Worker should trigger the eScriptorium workflow before polling the result")
assert_equal("Texto real do HTR", htr_payload["raw_text"], "Worker should return the collected HTR text when the workflow completes")
assert_equal("done", htr_payload["status"], "Worker should expose the final workflow status after polling")

document_calls = []
worker.find_escriptorium_document_by_name = lambda _document_name: None
worker.ESCRIPTORIUM_PROJECT_ID = "1"

def fake_escriptorium_request(method, path, **kwargs):
    document_calls.append((method, path, kwargs.get("json")))

    if method == "get" and path == "/projects/1/":
        return {"id": 1, "slug": "admins-project"}

    if method == "post" and path == "/documents/":
        return {"pk": 99, "name": "doc-9226"}

    raise AssertionError(f"Unexpected eScriptorium call: {method} {path}")

worker.escriptorium_request = fake_escriptorium_request

try:
    document_payload = worker.ensure_escriptorium_document(9226)
finally:
    worker.find_escriptorium_document_by_name = original_find_document
    worker.escriptorium_request = original_request
    worker.ESCRIPTORIUM_PROJECT_ID = original_project_id

assert_equal({"pk": 99, "name": "doc-9226"}, document_payload, "Worker should return the created eScriptorium document payload")
assert_equal(("get", "/projects/1/", None), document_calls[0], "Worker should resolve numeric project references before creating the document")
assert_equal("post", document_calls[1][0], "Worker should create the document after resolving the project reference")
assert_equal("/documents/", document_calls[1][1], "Worker should post documents to the documents endpoint")
assert_equal("admins-project", document_calls[1][2]["project"], "Worker should send the project slug expected by the eScriptorium API")
assert_equal("Latin", document_calls[1][2]["main_script"], "Worker should keep the configured main script name")
assert_true("tags" not in document_calls[1][2], "Worker should omit free-text tags that the API does not accept on document creation")

print("test_worker_mock_layout: ok")
