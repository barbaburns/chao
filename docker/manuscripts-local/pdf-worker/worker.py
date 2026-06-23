import glob
import os
import re
import shutil
import struct
import subprocess
import time
import uuid

import requests
from flask import Flask, jsonify, request, send_from_directory

app = Flask(__name__)

STORAGE_DIR = os.getenv("STORAGE_DIR", "/data")
ESCRIPTORIUM_BASE_URL = os.getenv("ESCRIPTORIUM_BASE_URL", "").rstrip("/")
ESCRIPTORIUM_TOKEN = os.getenv("ESCRIPTORIUM_TOKEN", "")
ESCRIPTORIUM_PROJECT_ID = os.getenv("ESCRIPTORIUM_PROJECT_ID", "").strip()
ESCRIPTORIUM_MAIN_SCRIPT = os.getenv("ESCRIPTORIUM_MAIN_SCRIPT", "Latin").strip() or "Latin"
ESCRIPTORIUM_READ_DIRECTION = os.getenv("ESCRIPTORIUM_READ_DIRECTION", "ltr").strip() or "ltr"
ESCRIPTORIUM_LINE_OFFSET = int(os.getenv("ESCRIPTORIUM_LINE_OFFSET", "0") or "0")
ESCRIPTORIUM_TRANSCRIPTION_ID = os.getenv("ESCRIPTORIUM_TRANSCRIPTION_ID", "").strip()
ESCRIPTORIUM_POLL_SECONDS = float(os.getenv("ESCRIPTORIUM_POLL_SECONDS", "0") or "0")
ESCRIPTORIUM_POLL_ATTEMPTS = int(os.getenv("ESCRIPTORIUM_POLL_ATTEMPTS", "8") or "8")
ESCRIPTORIUM_SEGMENT_PATH_TEMPLATE = os.getenv(
    "ESCRIPTORIUM_SEGMENT_PATH_TEMPLATE",
    "/documents/{document_id}/segment/",
).strip()
ESCRIPTORIUM_TRANSCRIBE_PATH_TEMPLATE = os.getenv(
    "ESCRIPTORIUM_TRANSCRIBE_PATH_TEMPLATE",
    "/documents/{document_id}/transcribe/",
).strip()
ESCRIPTORIUM_SEGMENT_MODEL_ID = os.getenv("ESCRIPTORIUM_SEGMENT_MODEL_ID", "").strip()
ESCRIPTORIUM_TRANSCRIBE_MODEL_ID = os.getenv("ESCRIPTORIUM_TRANSCRIBE_MODEL_ID", "").strip()
ESCRIPTORIUM_SEGMENT_OVERRIDE = os.getenv("ESCRIPTORIUM_SEGMENT_OVERRIDE", "1") != "0"
ENABLE_MOCK_LAYOUT = os.getenv("MANUSCRIPT_WORKER_ENABLE_MOCK_LAYOUT", "1") != "0"
DEFAULT_PAGE_TEXTS = [
    "Casa grande. Outra casa na margem.",
    "Carta velha ao fundo.",
    "Memoria da viagem e do arquivo.",
    "Barbosa escreve nova carta.",
]


def ensure_dir(path):
    os.makedirs(path, exist_ok=True)
    return path


def download_pdf(pdf_url, target_path):
    response = requests.get(pdf_url, stream=True, timeout=120)
    response.raise_for_status()

    with open(target_path, "wb") as handle:
        for chunk in response.iter_content(chunk_size=1024 * 1024):
            if chunk:
                handle.write(chunk)


def extract_pdf_pages(pdf_path, output_prefix):
    command = [
        "pdftoppm",
        "-png",
        "-r",
        "200",
        pdf_path,
        output_prefix,
    ]

    result = subprocess.run(command, capture_output=True, text=True)

    if result.returncode != 0:
        raise RuntimeError(result.stderr.strip() or "pdftoppm failed")

    return sorted(glob.glob(f"{output_prefix}-*.png"))


def normalize_text(value):
    return re.sub(r"\s+", " ", (value or "").strip())


def safe_positive_int(value, default=0):
    try:
        parsed = int(value)
    except (TypeError, ValueError):
        return default

    return parsed if parsed > 0 else default


def extract_pdf_text_for_page(pdf_path, page_number):
    if not pdf_path or not os.path.exists(pdf_path):
        return ""

    command = [
        "pdftotext",
        "-f",
        str(page_number),
        "-l",
        str(page_number),
        "-layout",
        pdf_path,
        "-",
    ]
    result = subprocess.run(command, capture_output=True, text=True)

    if result.returncode != 0:
        return ""

    return normalize_text(result.stdout)


def escriptorium_enabled():
    return (
        ESCRIPTORIUM_BASE_URL != ""
        and ESCRIPTORIUM_TOKEN != ""
        and ESCRIPTORIUM_PROJECT_ID != ""
    )


def escriptorium_headers():
    return {
        "Accept": "application/json",
        "Authorization": f"Token {ESCRIPTORIUM_TOKEN}",
    }


def escriptorium_api_url(path):
    normalized_path = path if path.startswith("/") else f"/{path}"
    return f"{ESCRIPTORIUM_BASE_URL}/api{normalized_path}"


def escriptorium_request(method, path, **kwargs):
    response = requests.request(
        method,
        escriptorium_api_url(path),
        headers={**escriptorium_headers(), **kwargs.pop("headers", {})},
        timeout=120,
        **kwargs,
    )
    response.raise_for_status()

    if response.status_code == 204 or response.text.strip() == "":
        return None

    return response.json()


def escriptorium_paginated_results(path):
    next_url = escriptorium_api_url(path)
    results = []

    while next_url:
        response = requests.get(next_url, headers=escriptorium_headers(), timeout=120)
        response.raise_for_status()
        payload = response.json()

        if isinstance(payload, dict) and isinstance(payload.get("results"), list):
            results.extend(payload.get("results", []))
            next_url = payload.get("next")
            continue

        if isinstance(payload, list):
            results.extend(payload)
            break

        break

    return results


def format_escriptorium_path(template, context):
    if not template:
        return ""

    try:
        return template.format(**context)
    except KeyError:
        return ""


def build_escriptorium_segment_payload(part_id):
    payload = {
        "parts": [part_id],
        "override": ESCRIPTORIUM_SEGMENT_OVERRIDE,
    }

    model_id = safe_positive_int(ESCRIPTORIUM_SEGMENT_MODEL_ID, 0)

    if model_id > 0:
        payload["model"] = model_id

    return payload


def build_escriptorium_transcribe_payload(part_id):
    model_id = safe_positive_int(ESCRIPTORIUM_TRANSCRIBE_MODEL_ID, 0)

    if model_id <= 0:
        return None

    payload = {
        "parts": [part_id],
        "model": model_id,
    }

    transcription_id = safe_positive_int(ESCRIPTORIUM_TRANSCRIPTION_ID, 0)

    if transcription_id > 0:
        payload["transcription"] = transcription_id

    return payload


def extract_task_id(task_payload):
    if not isinstance(task_payload, dict):
        return ""

    direct_candidates = [
        task_payload.get("task_id"),
        task_payload.get("task"),
        task_payload.get("pk"),
        task_payload.get("id"),
    ]

    for candidate in direct_candidates:
        if isinstance(candidate, (str, int)) and str(candidate).strip() != "":
            return str(candidate).strip()

    nested_task = task_payload.get("task")

    if isinstance(nested_task, dict):
        for key in ["pk", "id", "task_id"]:
            candidate = nested_task.get(key)

            if isinstance(candidate, (str, int)) and str(candidate).strip() != "":
                return str(candidate).strip()

    return ""


def trigger_escriptorium_task(path_template, context, payload, skip_reason=""):
    path = format_escriptorium_path(path_template, context)

    if path == "":
        return {
            "requested": False,
            "task_id": "",
            "status": "skipped",
            "path": "",
        }

    if payload is None:
        return {
            "requested": False,
            "task_id": "",
            "status": "skipped",
            "path": path,
            "reason": skip_reason,
        }

    try:
        response_payload = escriptorium_request("post", path, json=payload or {})

        return {
            "requested": True,
            "task_id": extract_task_id(response_payload),
            "status": "queued",
            "path": path,
            "response": response_payload if isinstance(response_payload, dict) else {},
        }
    except Exception as exc:
        return {
            "requested": True,
            "task_id": "",
            "status": "error",
            "path": path,
            "error": str(exc),
        }


def start_escriptorium_part_workflow(document_id, part_id):
    context = {
        "document_id": document_id,
        "part_id": part_id,
    }

    return {
        "segment": trigger_escriptorium_task(
            ESCRIPTORIUM_SEGMENT_PATH_TEMPLATE,
            context,
            build_escriptorium_segment_payload(part_id),
        ),
        "transcribe": trigger_escriptorium_task(
            ESCRIPTORIUM_TRANSCRIBE_PATH_TEMPLATE,
            context,
            build_escriptorium_transcribe_payload(part_id),
            "Configure ESCRIPTORIUM_TRANSCRIBE_MODEL_ID to enable HTR transcription.",
        ),
    }


def workflow_status_value(part_payload, workflow_key):
    workflow = (part_payload or {}).get("workflow") or {}

    if not isinstance(workflow, dict):
        return ""

    return str(workflow.get(workflow_key, "") or "").strip().lower()


def workflow_looks_finished(part_payload):
    segment_status = workflow_status_value(part_payload, "segment")
    transcribe_status = workflow_status_value(part_payload, "transcribe")
    finished_states = {"done", "finished", "completed", "success"}
    failed_states = {"error", "failed", "canceled", "cancelled", "crashed"}

    if transcribe_status in failed_states or segment_status in failed_states:
        return True

    if transcribe_status in finished_states:
        return True

    return False


def collect_escriptorium_part_result(document_id, part_id):
    attempts = max(1, ESCRIPTORIUM_POLL_ATTEMPTS)
    sleep_seconds = ESCRIPTORIUM_POLL_SECONDS if ESCRIPTORIUM_POLL_SECONDS > 0 else 2.0
    last_part_payload = {}
    last_lines_payload = []

    for attempt in range(attempts):
        last_part_payload = fetch_escriptorium_part_payload(document_id, part_id)
        last_lines_payload = fetch_escriptorium_part_lines(document_id, part_id)
        normalized_payload = normalize_escriptorium_part(
            part_payload=last_part_payload,
            transcription_id=ESCRIPTORIUM_TRANSCRIPTION_ID or None,
            lines_payload=last_lines_payload,
        )

        if normalized_payload["raw_text"] != "":
            return normalized_payload

        if workflow_looks_finished(last_part_payload) or attempt == attempts - 1:
            return normalized_payload

        time.sleep(sleep_seconds)

    return normalize_escriptorium_part(
        part_payload=last_part_payload,
        transcription_id=ESCRIPTORIUM_TRANSCRIPTION_ID or None,
        lines_payload=last_lines_payload,
    )


def build_default_page_text(page_number, manuscript_id):
    base_text = DEFAULT_PAGE_TEXTS[(page_number - 1) % len(DEFAULT_PAGE_TEXTS)]

    return f"{base_text} Piloto local do manuscrito {manuscript_id}, pagina {page_number}."


def read_png_size(image_path):
    if not image_path or not os.path.exists(image_path):
        return 2400, 3400

    with open(image_path, "rb") as handle:
        header = handle.read(24)

    if header[:8] != b"\x89PNG\r\n\x1a\n" or header[12:16] != b"IHDR":
        return 2400, 3400

    width, height = struct.unpack(">II", header[16:24])

    return int(width), int(height)


def build_bbox_from_points(points):
    normalized_points = []

    for point in points or []:
        if not isinstance(point, (list, tuple)) or len(point) < 2:
            continue

        try:
            x = float(point[0])
            y = float(point[1])
        except (TypeError, ValueError):
            continue

        normalized_points.append((x, y))

    if not normalized_points:
        return {}

    min_x = min(point[0] for point in normalized_points)
    max_x = max(point[0] for point in normalized_points)
    min_y = min(point[1] for point in normalized_points)
    max_y = max(point[1] for point in normalized_points)

    width = max_x - min_x
    height = max_y - min_y

    if width <= 0 or height <= 0:
        return {}

    return {
        "x": round(min_x, 2),
        "y": round(min_y, 2),
        "width": round(width, 2),
        "height": round(height, 2),
    }


def build_bbox_from_baseline(points):
    bbox = build_bbox_from_points(points)

    if bbox:
        return bbox

    normalized_points = []

    for point in points or []:
        if not isinstance(point, (list, tuple)) or len(point) < 2:
            continue

        try:
            normalized_points.append((float(point[0]), float(point[1])))
        except (TypeError, ValueError):
            continue

    if not normalized_points:
        return {}

    min_x = min(point[0] for point in normalized_points)
    max_x = max(point[0] for point in normalized_points)
    avg_y = sum(point[1] for point in normalized_points) / len(normalized_points)

    return {
        "x": round(min_x, 2),
        "y": round(avg_y - 12, 2),
        "width": round(max_x - min_x, 2),
        "height": 24.0,
    }


def choose_line_transcription(line_payload, transcription_id=None):
    transcriptions = line_payload.get("transcriptions") or []

    if not isinstance(transcriptions, list):
        return {}

    requested_id = str(transcription_id or "").strip()

    if requested_id != "":
        for transcription in transcriptions:
            if str(transcription.get("transcription", "")) == requested_id:
                return transcription

    return transcriptions[0] if transcriptions else {}


def build_word_boxes_from_graphs(graphs):
    words = []
    current_text = []
    current_points = []

    for graph in graphs or []:
        character = str(graph.get("c", ""))
        poly = graph.get("poly") or []

        if character.isspace():
            if current_text and current_points:
                bbox = build_bbox_from_points(current_points)

                if bbox:
                    words.append(
                        {
                            "text": "".join(current_text),
                            "bbox": bbox,
                        }
                    )

            current_text = []
            current_points = []
            continue

        current_text.append(character)
        current_points.extend(poly if isinstance(poly, list) else [])

    if current_text and current_points:
        bbox = build_bbox_from_points(current_points)

        if bbox:
            words.append(
                {
                    "text": "".join(current_text),
                    "bbox": bbox,
                }
            )

    return words


def build_word_boxes_from_text(line_text, line_bbox):
    clean_text = normalize_text(line_text)

    if clean_text == "" or not line_bbox:
        return []

    words = [word for word in clean_text.split(" ") if word]

    if not words:
        return []

    gap = 12.0
    usable_width = max(float(line_bbox["width"]) - (gap * max(0, len(words) - 1)), float(len(words) * 20))
    total_chars = max(sum(max(1, len(word)) for word in words), 1)
    cursor_x = float(line_bbox["x"])
    boxes = []

    for word in words:
        width = max(20.0, usable_width * (max(1, len(word)) / total_chars))
        boxes.append(
            {
                "text": word,
                "bbox": {
                    "x": round(cursor_x, 2),
                    "y": round(float(line_bbox["y"]), 2),
                    "width": round(width, 2),
                    "height": round(float(line_bbox["height"]), 2),
                },
            }
        )
        cursor_x += width + gap

    return boxes


def normalize_escriptorium_line(line_payload, transcription_id=None):
    transcription = choose_line_transcription(line_payload, transcription_id)
    line_text = normalize_text(transcription.get("content", ""))
    line_bbox = build_bbox_from_points(line_payload.get("mask") or [])

    if not line_bbox:
        line_bbox = build_bbox_from_baseline(line_payload.get("baseline") or [])

    word_boxes = build_word_boxes_from_graphs(transcription.get("graphs") or [])

    if not word_boxes and line_text != "" and line_bbox:
        word_boxes = build_word_boxes_from_text(line_text, line_bbox)

    if not line_bbox and word_boxes:
        line_bbox = build_bbox_from_points([
            [word["bbox"]["x"], word["bbox"]["y"]]
            for word in word_boxes
        ] + [
            [
                word["bbox"]["x"] + word["bbox"]["width"],
                word["bbox"]["y"] + word["bbox"]["height"],
            ]
            for word in word_boxes
        ])

    return {
        "text": line_text,
        "bbox": line_bbox,
        "words": word_boxes,
        "transcription_id": transcription.get("transcription"),
        "line_id": line_payload.get("pk"),
    }


def normalize_escriptorium_part(part_payload, transcription_id=None, lines_payload=None):
    lines_payload = lines_payload if isinstance(lines_payload, list) else []
    normalized_lines = []

    for line_payload in lines_payload:
        if not isinstance(line_payload, dict):
            continue

        normalized_line = normalize_escriptorium_line(line_payload, transcription_id=transcription_id)

        if normalized_line["text"] == "" and not normalized_line["words"]:
            continue

        normalized_lines.append(normalized_line)

    raw_text = normalize_text(" ".join(
        line["text"] for line in normalized_lines if line["text"] != ""
    ))

    return {
        "raw_text": raw_text,
        "layout_data": {
            "text": raw_text,
            "lines": normalized_lines,
        },
        "text_source": "escriptorium-kraken" if raw_text != "" else "empty",
        "htr_status": (
            ((part_payload or {}).get("workflow") or {}).get("transcribe")
            or "ready"
        ),
    }


def find_escriptorium_document_by_name(document_name):
    for document in escriptorium_paginated_results("/documents/"):
        if str(document.get("name", "")).strip() == document_name:
            return document

    return None


def resolve_escriptorium_project_reference():
    project_reference = str(ESCRIPTORIUM_PROJECT_ID or "").strip()

    if project_reference == "":
        return ""

    if project_reference.isdigit():
        project_payload = escriptorium_request("get", f"/projects/{project_reference}/")
        slug = str((project_payload or {}).get("slug", "")).strip()

        if slug != "":
            return slug

    return project_reference


def build_escriptorium_document_payload(manuscript_id):
    return {
        "name": f"doc-{manuscript_id}",
        "project": resolve_escriptorium_project_reference(),
        "main_script": ESCRIPTORIUM_MAIN_SCRIPT,
        "read_direction": ESCRIPTORIUM_READ_DIRECTION,
        "line_offset": ESCRIPTORIUM_LINE_OFFSET,
    }


def ensure_escriptorium_document(manuscript_id):
    document_name = f"doc-{manuscript_id}"
    existing = find_escriptorium_document_by_name(document_name)

    if existing:
        return existing

    return escriptorium_request(
        "post",
        "/documents/",
        json=build_escriptorium_document_payload(manuscript_id),
    )


def find_escriptorium_part_by_name(document_id, part_name):
    for part in escriptorium_paginated_results(f"/documents/{document_id}/parts/"):
        if str(part.get("name", "")).strip() == part_name:
            return part

    return None


def upload_escriptorium_part(document_id, image_path, page_number):
    part_name = f"page-{page_number}"
    existing = find_escriptorium_part_by_name(document_id, part_name)

    if existing:
        return existing

    with open(image_path, "rb") as image_handle:
        response = requests.post(
            escriptorium_api_url(f"/documents/{document_id}/parts/"),
            headers=escriptorium_headers(),
            data={
                "name": part_name,
                "typology": "",
                "source": os.path.basename(image_path),
            },
            files={
                "image": (os.path.basename(image_path), image_handle, "image/png"),
            },
            timeout=120,
        )

    response.raise_for_status()
    return response.json()


def fetch_escriptorium_part_lines(document_id, part_id):
    return escriptorium_paginated_results(f"/documents/{document_id}/parts/{part_id}/lines/")


def fetch_escriptorium_part_payload(document_id, part_id):
    return escriptorium_request("get", f"/documents/{document_id}/parts/{part_id}/")


def split_text_into_lines(raw_text, max_words=4):
    words = [word for word in raw_text.split(" ") if word]

    if not words:
        return []

    lines = []
    current = []

    for word in words:
        current.append(word)
        should_break = len(current) >= max_words or word.endswith((".", "?", "!", ";", ":"))

        if should_break:
            lines.append(" ".join(current))
            current = []

    if current:
        lines.append(" ".join(current))

    return lines


def build_mock_layout_data(raw_text, image_width=2400, image_height=3400):
    raw_text = normalize_text(raw_text)

    if raw_text == "":
        return {"text": "", "lines": []}

    lines = []
    margin_x = max(40, int(image_width * 0.06))
    margin_y = max(60, int(image_height * 0.08))
    line_height = max(28, int(image_height * 0.018))
    line_gap = max(14, int(line_height * 0.7))
    char_width = max(14, int(image_width * 0.009))
    word_gap = max(16, int(char_width * 0.8))

    for line_index, line_text in enumerate(split_text_into_lines(raw_text), start=0):
        cursor_x = margin_x
        cursor_y = margin_y + line_index * (line_height + line_gap)
        line_words = []

        for word in [word for word in line_text.split(" ") if word]:
            clean_word = word.strip()
            word_width = max(char_width * 2, len(clean_word) * char_width)
            bbox = {
                "x": cursor_x,
                "y": cursor_y,
                "width": word_width,
                "height": line_height,
            }
            line_words.append({"text": clean_word, "bbox": bbox})
            cursor_x += word_width + word_gap

        if not line_words:
            continue

        line_width = (line_words[-1]["bbox"]["x"] + line_words[-1]["bbox"]["width"]) - margin_x
        lines.append(
            {
                "text": line_text,
                "bbox": {
                    "x": margin_x,
                    "y": cursor_y,
                    "width": line_width,
                    "height": line_height,
                },
                "words": line_words,
            }
        )

    return {
        "text": raw_text,
        "lines": lines,
    }


def build_page_transcription_payload(page_number, manuscript_id, pdf_path, image_path):
    extracted_text = extract_pdf_text_for_page(pdf_path, page_number)
    raw_text = extracted_text

    if raw_text == "" and ENABLE_MOCK_LAYOUT:
        raw_text = build_default_page_text(page_number, manuscript_id)

    image_width, image_height = read_png_size(image_path)
    layout_data = build_mock_layout_data(raw_text, image_width=image_width, image_height=image_height)

    return {
        "raw_text": raw_text,
        "layout_data": layout_data,
        "text_source": "pdf-text" if extracted_text else ("mock-layout" if raw_text else "empty"),
    }


def submit_page_to_escriptorium(image_path, manuscript_id, page_number):
    if not escriptorium_enabled():
        return {
            "provider": "escriptorium-kraken",
            "base_url": ESCRIPTORIUM_BASE_URL,
            "document_id": f"doc-{manuscript_id}",
            "page_id": f"page-{page_number}",
            "job_id": f"job-{uuid.uuid4()}",
            "status": "queued",
            "token_configured": bool(ESCRIPTORIUM_TOKEN),
            "image_path": image_path,
        }

    document = ensure_escriptorium_document(manuscript_id)
    document_id = document.get("pk") or document.get("id") or f"doc-{manuscript_id}"
    part = upload_escriptorium_part(document_id, image_path, page_number)
    part_id = part.get("pk") or part.get("id") or f"page-{page_number}"
    workflow_requests = start_escriptorium_part_workflow(document_id, part_id)
    normalized_payload = collect_escriptorium_part_result(document_id, part_id)

    return {
        "provider": "escriptorium-kraken",
        "base_url": ESCRIPTORIUM_BASE_URL,
        "document_id": document_id,
        "page_id": part_id,
        "job_id": f"job-{uuid.uuid4()}",
        "status": normalized_payload["htr_status"],
        "token_configured": bool(ESCRIPTORIUM_TOKEN),
        "image_path": image_path,
        "raw_text": normalized_payload["raw_text"],
        "layout_data": normalized_payload["layout_data"],
        "text_source": normalized_payload["text_source"],
        "workflow_requests": workflow_requests,
    }


def build_public_page_url(job_id, filename):
    return f"{request.url_root.rstrip('/')}/jobs/{job_id}/pages/{filename}"


def choose_page_transcription_payload(htr_payload, fallback_payload):
    raw_text = normalize_text((htr_payload or {}).get("raw_text", ""))

    if raw_text != "":
        return {
            "raw_text": raw_text,
            "layout_data": (htr_payload or {}).get("layout_data") or {"text": raw_text, "lines": []},
            "text_source": (htr_payload or {}).get("text_source") or "escriptorium-kraken",
        }

    return fallback_payload


@app.get("/health")
def health():
    return jsonify({"ok": True})


@app.post("/jobs/pdf")
def process_pdf():
    payload = request.get_json(force=True)

    manuscript_id = payload.get("manuscript_id")
    pdf_url = payload.get("pdf_url")

    if not manuscript_id or not pdf_url:
        return jsonify({"error": "manuscript_id and pdf_url are required"}), 400

    job_id = str(uuid.uuid4())
    job_dir = ensure_dir(os.path.join(STORAGE_DIR, "jobs", job_id))
    pages_dir = ensure_dir(os.path.join(job_dir, "pages"))
    pdf_path = os.path.join(job_dir, "source.pdf")
    output_prefix = os.path.join(pages_dir, "page")

    try:
        download_pdf(pdf_url, pdf_path)
        page_files = extract_pdf_pages(pdf_path, output_prefix)

        pages = []

        for index, image_path in enumerate(page_files, start=1):
            htr_job = submit_page_to_escriptorium(
                image_path=image_path,
                manuscript_id=manuscript_id,
                page_number=index,
            )
            fallback_transcription = build_page_transcription_payload(
                page_number=index,
                manuscript_id=manuscript_id,
                pdf_path=pdf_path,
                image_path=image_path,
            )
            transcription = choose_page_transcription_payload(htr_job, fallback_transcription)

            pages.append(
                {
                    "page_number": index,
                    "filename": os.path.basename(image_path),
                    "image_path": image_path,
                    "image_url": build_public_page_url(job_id, os.path.basename(image_path)),
                    "raw_text": transcription["raw_text"],
                    "layout_data": transcription["layout_data"],
                    "text_source": transcription["text_source"],
                    "htr": htr_job,
                }
            )

        return jsonify(
            {
                "job_id": job_id,
                "manuscript_id": manuscript_id,
                "page_count": len(pages),
                "pages": pages,
            }
        )
    except Exception as exc:
        shutil.rmtree(job_dir, ignore_errors=True)
        return jsonify({"error": str(exc)}), 500


@app.get("/jobs/<job_id>/pages/<filename>")
def serve_page_image(job_id, filename):
    pages_dir = os.path.join(STORAGE_DIR, "jobs", job_id, "pages")

    return send_from_directory(pages_dir, filename)


if __name__ == "__main__":
    ensure_dir(STORAGE_DIR)
    app.run(host="0.0.0.0", port=8002, debug=True)
