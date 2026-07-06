# Manuscript Import Before Save Design

## Objective

Allow the editor to import a manuscript source folder from the `manuscrito` admin screen before manually saving the post.

The desired flow is:

1. type the source subfolder name
2. click `Importar da subpasta`
3. see the import result update in the admin via JavaScript
4. save or update the post afterward

This must work both for existing manuscript posts and for brand-new manuscript entries.

## Chosen Approach

Use an on-demand draft creation flow.

When the editor clicks import:

- if the manuscript already has a post ID, reuse that post
- if the manuscript is still new, create a draft first
- save the typed subfolder value to that draft immediately
- run the existing folder import against that post ID
- return the updated import state as JSON
- refresh only the import metabox UI in JavaScript, without a full page reload

This approach keeps the import state stored in normal post meta and avoids building a temporary server-side cache layer.

## Admin UX

The manuscript admin screen should behave as follows:

- the subfolder field remains editable as it is today
- the import button is always available as soon as the editor types a non-empty value
- clicking import does not require a prior post save
- the import summary updates in place after success or failure
- the editor can continue editing title, text, featured image, and other fields before clicking `Salvar` or `Atualizar`

For new manuscripts, the first import effectively creates a draft behind the scenes. This is acceptable and expected in this flow.

## Request Flow

### Existing manuscript post

1. JavaScript reads the current post ID from the editor context.
2. JavaScript reads the current typed subfolder value from the ACF field.
3. JavaScript sends an authenticated admin request with:
   - post ID
   - subfolder value
   - nonce
4. Backend saves the subfolder value to post meta for that post.
5. Backend runs the current source-folder import logic.
6. Backend returns JSON with:
   - success or error status
   - resolved subfolder label
   - import summary counts
   - warnings
   - last import metadata
7. JavaScript updates the import metabox UI without reloading the page.

### New manuscript post

1. JavaScript detects that no post ID exists yet.
2. JavaScript requests draft creation first.
3. Backend creates a `manuscrito` draft and returns the new post ID.
4. JavaScript continues with the same import request used for existing posts.
5. The editor remains on the current screen, but now operates against the newly created draft.
6. Import results appear in the metabox immediately.

## Backend Changes

The backend should be split into two explicit admin endpoints:

- `create manuscript draft`
- `import manuscript source folder from typed value`

The import endpoint must no longer depend exclusively on the previously saved ACF value. It should accept the subfolder value from the current request, sanitize it, persist it to the post, and then call the existing importer.

The existing saved-field-based fallback can remain as a secondary path for older flows, but the new button behavior must prefer the typed request value whenever provided.

## Frontend And Storage Impact

Imported page snapshots, warnings, and source metadata should continue to be stored in the same post meta keys already used by the current manuscript viewer.

This means:

- no frontend viewer changes are required
- no search/highlight storage changes are required
- no new temporary cache format is required

The only storage behavior change is that an unsaved editor action can now create a draft and persist import-related post meta earlier in the workflow.

## JavaScript Responsibilities

The admin-side script should:

- detect the current manuscript editor screen
- read the current subfolder field value directly from the DOM
- disable the import button while the request is running
- show transient loading state
- call draft-creation when needed
- call import endpoint with the current typed value
- replace or refresh the import metabox content from the returned JSON
- show inline error feedback if the request fails

The script should not require a page reload to expose the button or the updated summary.

## Error Handling

The new flow must handle:

- empty subfolder input
- invalid subfolder name
- unreadable or missing source folder
- draft creation failure
- permission failure
- expired nonce
- import warnings such as unmatched image/XML files

If import fails on a brand-new manuscript after the draft is created, the draft remains available so the editor does not lose progress.

## Testing

The implementation should add or update tests for:

- draft creation for a new `manuscrito`
- import request using typed subfolder value without prior save
- import request on an existing post
- admin JSON response shape for success and error
- metabox HTML refresh or render payload generation
- permission and nonce rejection
- persistence of the typed subfolder value onto the post during import

## Non-Goals

This change does not include:

- background imports
- session-based temporary caches
- importing without creating a persistent draft
- changing the frontend manuscript viewer behavior
- replacing the current source-folder import engine

## Verification

The flow is complete when:

- an editor can type a subfolder and import without clicking save first
- the same flow works on a brand-new manuscript entry
- the import summary updates in the admin via JavaScript
- the imported snapshot is already attached to the created or existing manuscript post
- the editor can save the post afterward without losing the imported manuscript state
