# Product Manuscript Gallery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the old single iconography product extra with a manuscript gallery backed by `manuscript_gallery`.

**Architecture:** A small helper in the product extra controller will normalize the ACF gallery field into a predictable array for Blade templates. The two product extra templates and related SCSS will consume that normalized list and render multiple manuscript images consistently.

**Tech Stack:** PHP, WordPress ACF, Blade, SCSS

---

### Task 1: Document and test the new helper contract

**Files:**
- Create: `tests/product_extra_manuscript_test.php`
- Modify: `app/Controllers/Partials/ProductExtra.php`

- [ ] **Step 1: Write the failing test**

Create a small PHP script that stubs `get_field()` and `wp_get_attachment_image()` and asserts:
- `getProductManuscript()` returns `false` when `manuscript_gallery` is empty.
- `getProductManuscript()` returns an array with an `images` list when the gallery contains valid images.

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_extra_manuscript_test.php`
Expected: FAIL because `getProductManuscript()` does not exist yet.

- [ ] **Step 3: Write minimal implementation**

Add `getProductManuscript($id)` to `app/Controllers/Partials/ProductExtra.php`, reading `manuscript_gallery` and returning normalized HTML image entries.

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS with `product_extra_manuscript_test: ok`

### Task 2: Update templates to render the gallery

**Files:**
- Modify: `resources/views/partials/product-extra.blade.php`
- Modify: `resources/views/partials/product-loop-extra-content.blade.php`

- [ ] **Step 1: Replace old helper usage**

Swap `getProductIconography()` and related variables/classes for `getProductManuscript()`.

- [ ] **Step 2: Render the image list**

Loop through the normalized `images` array and render one `<figure>` per manuscript image in both templates.

- [ ] **Step 3: Verify template syntax**

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS remains green after the view updates.

### Task 3: Update ACF JSON labels and styles

**Files:**
- Modify: `resources/acf-json/group_5e4d0f1f91554.json`
- Modify: `resources/assets/styles/components/_product.scss`
- Modify: `resources/assets/styles/layouts/_custom.scss`

- [ ] **Step 1: Align exported field group JSON**

Make sure the JSON reflects the current `Manuscrito` tab and `manuscript_gallery` field metadata.

- [ ] **Step 2: Adapt styles for multiple images**

Replace the old single-figure width rules with a simple responsive gallery layout for the manuscript block.

- [ ] **Step 3: Run verification commands**

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS

Run: `composer test`
Expected: exit 0
