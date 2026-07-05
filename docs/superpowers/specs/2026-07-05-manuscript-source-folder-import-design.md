# Manuscript Source Folder Import Design

## Objective

Replace the current ZIP upload workflow with a server-folder workflow for manuscript imports.

The editorial team will upload the manuscript assets directly to a fixed base folder on the server, already unpacked. In the WordPress admin, the editor will inform only the manuscript subfolder name and trigger the import from there.

## Source Folder Format

The server will contain a fixed manuscript source base folder outside the normal media-upload workflow. Each manuscript will live in its own subfolder.

Expected structure:

```text
<manuscripts-base>/
  venancia-caderno/
    venanciaCaderno_0051B.jpg
    venanciaCaderno_0051B.xml
    venanciaCaderno_0052A.jpg
    venanciaCaderno_0052A.xml
```

Rules:

- one subfolder per manuscript
- files are uploaded already unpacked
- images and HTR XML files are paired by the same basename
- the frontend viewer reads images directly from that original subfolder

## Admin UX

The `manuscrito` edit screen should stop asking for a ZIP file.

Instead, the editor should get:

- a simple field for the manuscript source subfolder name
- a metabox showing the configured subfolder
- an import button labeled for folder-based import
- compact import feedback with errors and mismatched file counts

The editor should not need to provide full server paths. The system resolves the absolute path from a fixed base folder plus the provided subfolder name.

## Import Flow

The importer should:

1. resolve the absolute manuscript source folder from the fixed base path and the saved subfolder slug
2. scan the subfolder contents
3. pair image files and XML files by basename
4. parse XML text and coordinates the same way the current manuscript search flow already does
5. save a new import snapshot in post meta
6. keep image references pointing to the original source subfolder instead of copying files into uploads

The frontend should continue to consume the snapshot the same way it does now, including `image_url`, page text, search results, and highlight data.

## Path And URL Strategy

The physical source files stay in the manuscript source base folder.

The implementation must provide a stable way to convert:

- `source subfolder + filename`

into:

- a public image URL for the viewer

The preferred approach is to keep the source folder separate from the regular WordPress media library while still exposing a controlled public URL path for manuscript page images.

## Validation And Error Handling

The import flow should explicitly handle:

- missing or empty subfolder name
- configured subfolder not found
- unreadable source folder
- image without matching XML
- XML without matching image
- unsupported nested subdirectories
- unsupported file extensions

Admin notices and the import metabox should report folder-oriented language instead of ZIP-oriented language.

## Snapshot Requirements

The saved snapshot should still include:

- source label for the last import
- page count
- page rows
- parsed raw text
- image URL per page
- import warnings
- timestamp

The summary table in the admin should continue to show a compact page list plus counts for unmatched image/XML pairs.

## Non-Goals

This change does not add:

- ZIP uploads larger than media limits
- background workers
- external OCR/HTR services
- automatic server-side decompression
- frontend transcription display

## Verification

The workflow is complete when:

- the editorial team can upload unpacked files to the server base folder
- the editor can type only the manuscript subfolder name in wp-admin
- the import succeeds without using the media library ZIP upload
- the frontend viewer loads manuscript images from the original source subfolder
- search and highlight continue to work from the imported XML-derived snapshot
