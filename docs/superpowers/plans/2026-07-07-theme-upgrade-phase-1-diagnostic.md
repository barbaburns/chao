# Theme Upgrade Phase 1 Diagnostic Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Produce a risk-first diagnostic package for the `chao` theme upgrade project without changing theme behavior.

**Architecture:** This phase creates documentation artifacts, not runtime changes. It inventories the active stack, maps business-critical flows to theme/plugin coupling points, and produces a compact regression checklist plus a prioritized backlog for later upgrade phases.

**Tech Stack:** WordPress theme PHP, WooCommerce, ACF, Pagar.me, Soil, Sage 9, Composer metadata, npm/webpack metadata, markdown docs, shell inspection commands

---

## File Structure

- Create: `docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`
  - Main diagnostic report containing the critical-flow map, technical baseline, coupling/risk map, and prioritized backlog.
- Create: `docs/superpowers/checklists/2026-07-07-theme-upgrade-regression.md`
  - Manual regression checklist for store, account, checkout, payment, and manuscript flows.
- Modify: `docs/superpowers/specs/2026-07-07-theme-upgrade-phase-1-diagnostic-design.md`
  - Only if execution reveals a spec contradiction that needs to be clarified before later phases.
- Test/Verify with commands:
  - `git status --short`
  - `cat composer.json`
  - `cat package.json`
  - `sed -n '1,120p' /Volumes/kawase/WordPress_dev/Local/chao/app/public/wp-content/plugins/soil/soil.php`
  - `rg -n "woocommerce|Pagarme|soil-|manuscrito|acf_add|wp_enqueue|wc-product-gallery|comments_template" app resources/views resources/functions.php`

### Task 1: Create the Phase 1 Diagnostic Report Skeleton

**Files:**
- Create: `docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`
- Test: `git diff -- docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`

- [ ] **Step 1: Write the initial report skeleton**

Create `docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md` with:

```md
# Theme Upgrade Phase 1 Diagnostic Report

## Scope

This report documents the current technical baseline of the `chao` theme and maps upgrade risk against business-critical flows.

## 1. Critical Flow Map

## 2. Technical Baseline Inventory

## 3. Coupling and Risk Map

## 4. Prioritized Upgrade Backlog

## 5. Open Questions
```

- [ ] **Step 2: Verify the new report file exists and is the only change in this task**

Run:

```bash
git diff -- docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
```

Expected:

```text
A new markdown report file containing the five top-level sections above.
```

- [ ] **Step 3: Commit the report skeleton**

```bash
git add docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
git commit -m "docs: add theme upgrade diagnostic report skeleton"
```

### Task 2: Inventory the Current Theme and Plugin Baseline

**Files:**
- Modify: `docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`
- Test: `cat composer.json`
- Test: `cat package.json`
- Test: `sed -n '1,120p' /Volumes/kawase/WordPress_dev/Local/chao/app/public/wp-content/plugins/soil/soil.php`

- [ ] **Step 1: Capture the current stack facts from Composer, npm, and Soil**

Run:

```bash
cat composer.json
cat package.json
sed -n '1,120p' /Volumes/kawase/WordPress_dev/Local/chao/app/public/wp-content/plugins/soil/soil.php
```

Expected:

```text
Composer shows Sage 9-era PHP dependencies, package.json shows webpack 3-era tooling, and Soil shows version 3.9.0.
```

- [ ] **Step 2: Write the baseline inventory section**

Update the `## 2. Technical Baseline Inventory` section to include:

```md
## 2. Technical Baseline Inventory

### Theme Runtime Stack

- Theme architecture: Sage 9-era Blade/controller stack
- PHP requirement declared by the theme: `>=7.1`
- Composer runtime dependencies:
  - `illuminate/support 5.6.*`
  - `roots/sage-lib ~9.0.9`
  - `soberwp/controller ~2.1.0`
  - `roots/sage-woocommerce ^1.0`

### Frontend Build Stack

- Node build metadata: `package.json` version `9.0.9`
- Bundler generation: webpack 3
- CSS pipeline: Sass + PostCSS + Stylelint
- Script linting: ESLint 4-era toolchain

### Plugin/Integration Baseline

- WooCommerce: custom template overrides and hook customizations in the theme
- Soil: version `3.9.0`
- ACF: options pages, local field groups, load-field filters, manuscript-specific field bootstrap
- Pagar.me: local compatibility workaround in `resources/functions.php`

### Active Custom Subsystems

- Product content extensions in `app/Controllers/Partials/ProductExtra.php`
- Manuscript admin flow in `app/Manuscripts/Admin.php`
- Manuscript frontend/viewer/search in `app/Manuscripts/Frontend.php`, `Search.php`, and `resources/assets/scripts/routes/single-manuscrito.js`
```

- [ ] **Step 3: Verify the report now contains the baseline inventory**

Run:

```bash
rg -n "Technical Baseline Inventory|illuminate/support 5.6|webpack 3|Soil: version `3.9.0`|Pagar.me" docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
```

Expected:

```text
Matches for each of the inventory headings and key dependency/version markers.
```

- [ ] **Step 4: Commit the baseline inventory**

```bash
git add docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
git commit -m "docs: capture theme upgrade baseline inventory"
```

### Task 3: Map Business-Critical Flows

**Files:**
- Modify: `docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`
- Test: `rg -n "woocommerce|manuscrito|comments_template|Pagarme|my-account|checkout" app resources/views resources/functions.php`

- [ ] **Step 1: Gather references for critical business flows**

Run:

```bash
rg -n "woocommerce|manuscrito|comments_template|Pagarme|my-account|checkout" app resources/views resources/functions.php
```

Expected:

```text
Matches showing WooCommerce templates, manuscript templates, and integration/workaround touchpoints.
```

- [ ] **Step 2: Write the critical-flow map**

Update the `## 1. Critical Flow Map` section to include:

```md
## 1. Critical Flow Map

| Flow | User Goal | Theme Surface | Plugin/Service Coupling | Validation Method |
| --- | --- | --- | --- | --- |
| Shop archive | Browse books | `resources/views/woocommerce/archive-product.blade.php`, `content-product*.blade.php`, `app/woocommerce.php` | WooCommerce | Open shop archive and confirm product grid renders |
| Single product | View book details and conversion blocks | `resources/views/woocommerce/content-single-product.blade.php`, product partials, `app/Controllers/Partials/ProductExtra.php` | WooCommerce, ACF | Open a product page and verify gallery, manuscript CTA, buy links, and comments |
| Cart | Review selected items | WooCommerce cart templates and theme styles/hooks | WooCommerce | Add product and load cart |
| Checkout | Complete purchase | WooCommerce checkout templates and field customizations in `resources/functions.php` | WooCommerce, Pagar.me | Load checkout and confirm fields and payment UI render |
| Account/login | Sign in, recover account | WooCommerce my-account templates and custom theme overrides | WooCommerce | Open login, lost password, and account screens |
| Payment flow | Submit payment and render provider UI safely | Theme bootstrap plus local Pagar.me workaround in `resources/functions.php` | Pagar.me | Confirm checkout payment interface initializes without fatal errors |
| Manuscript admin | Save/import manuscript content | `app/Manuscripts/Admin.php`, `app/acf.php`, admin JS | ACF, custom manuscript subsystem | Save a manuscript post and trigger source import |
| Manuscript frontend | Search and navigate manuscript pages | `resources/views/partials/content-single-manuscrito.blade.php`, `app/Manuscripts/Frontend.php`, `Search.php`, viewer JS | custom manuscript subsystem | Open manuscript single, search, and navigate viewer |
```

- [ ] **Step 3: Verify all required flows are listed**

Run:

```bash
rg -n "Shop archive|Single product|Cart|Checkout|Account/login|Payment flow|Manuscript admin|Manuscript frontend" docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
```

Expected:

```text
Eight matches, one for each critical flow row.
```

- [ ] **Step 4: Commit the critical-flow map**

```bash
git add docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
git commit -m "docs: map critical flows for theme upgrade"
```

### Task 4: Document Coupling Points and Risk Levels

**Files:**
- Modify: `docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`
- Test: `rg -n "soil-|sage|controller|woocommerce_|acf_add|Pagarme|manuscript" app resources/views resources/functions.php`

- [ ] **Step 1: Gather coupling evidence**

Run:

```bash
rg -n "soil-|sage|controller|woocommerce_|acf_add|Pagarme|manuscript" app resources/views resources/functions.php
```

Expected:

```text
Matches showing bootstrap, WooCommerce hooks, ACF initialization, Pagar.me workaround, and manuscript subsystem entry points.
```

- [ ] **Step 2: Write the coupling and risk map**

Update the `## 3. Coupling and Risk Map` section to include:

```md
## 3. Coupling and Risk Map

| Coupling Point | Description | Risk | Why It Matters |
| --- | --- | --- | --- |
| Sage 9 bootstrap/helpers | Theme bootstraps through legacy Sage helpers and Blade wiring in `resources/functions.php`, `app/setup.php`, and `app/helpers.php` | High | Any framework upgrade can break template loading and global helper assumptions |
| Blade/controller layer | Theme data is shaped through Sober Controller classes and Blade conventions | Medium | Rendering can drift if Controller/Sage compatibility changes |
| Soil theme supports | `soil-clean-up`, `soil-jquery-cdn`, `soil-nav-walker`, `soil-relative-urls`, and others are enabled in `app/setup.php` | Medium | Soil is legacy and may contribute deprecated behavior or navigation/render drift |
| WooCommerce hook customizations | Product/archive/single behavior is extensively altered in `app/woocommerce.php` | High | Small hook-order changes can affect product rendering and checkout conversion |
| WooCommerce overridden templates | Theme carries custom WooCommerce views across archive, product, account, cart, and checkout surfaces | High | Template drift is a major regression vector during upgrades |
| Pagar.me workaround | Theme patches plugin bootstrap behavior in `resources/functions.php` | High | Payment flow is protected business-critical surface and sensitive to init timing |
| ACF initialization and options usage | ACF powers site options, product fields, and manuscript admin fields | Medium | Initialization order and field availability affect both admin and frontend data |
| Manuscript subsystem | Custom post type, admin import flow, search, and viewer form an isolated but active subsystem | Medium | Modernization must not regress editorial workflows already stabilized recently |
| Build pipeline | webpack 3-era JS/CSS pipeline drives current assets | Medium | Toolchain upgrades can break asset output even without PHP regressions |
```

- [ ] **Step 3: Verify all risk levels are represented**

Run:

```bash
rg -n "High|Medium" docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
```

Expected:

```text
Multiple matches showing both High and Medium risk items in the coupling table.
```

- [ ] **Step 4: Commit the coupling map**

```bash
git add docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
git commit -m "docs: map upgrade coupling risks"
```

### Task 5: Create the Manual Regression Checklist

**Files:**
- Create: `docs/superpowers/checklists/2026-07-07-theme-upgrade-regression.md`
- Test: `git diff -- docs/superpowers/checklists/2026-07-07-theme-upgrade-regression.md`

- [ ] **Step 1: Write the regression checklist**

Create `docs/superpowers/checklists/2026-07-07-theme-upgrade-regression.md` with:

```md
# Theme Upgrade Regression Checklist

- [ ] Open the shop archive and confirm product cards render normally.
- [ ] Open at least one single product and confirm title, price area, attachments, and extra content render.
- [ ] Confirm the manuscript CTA block on the product page renders and links correctly when present.
- [ ] Add a product to the cart.
- [ ] Open the cart and confirm totals and quantity controls render.
- [ ] Open checkout and confirm customer fields render without layout or JS errors.
- [ ] Confirm the Pagar.me payment UI appears and initializes.
- [ ] Open the account login page.
- [ ] Open lost-password/reset-password flow.
- [ ] Open a manuscript post in admin.
- [ ] Save the manuscript post successfully.
- [ ] Trigger manuscript source import successfully.
- [ ] Open a manuscript single on the frontend.
- [ ] Run a manuscript search query.
- [ ] Navigate pages in the manuscript viewer.
```

- [ ] **Step 2: Verify the checklist exists and contains all required checkpoints**

Run:

```bash
rg -n "shop archive|single product|cart|checkout|Pagar.me|account login|manuscript" docs/superpowers/checklists/2026-07-07-theme-upgrade-regression.md
```

Expected:

```text
Matches covering store, payment, account, and manuscript checkpoints.
```

- [ ] **Step 3: Commit the regression checklist**

```bash
git add docs/superpowers/checklists/2026-07-07-theme-upgrade-regression.md
git commit -m "docs: add theme upgrade regression checklist"
```

### Task 6: Produce the Prioritized Backlog

**Files:**
- Modify: `docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`
- Test: `sed -n '1,260p' docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`

- [ ] **Step 1: Write the prioritized backlog section**

Update the `## 4. Prioritized Upgrade Backlog` section to include:

```md
## 4. Prioritized Upgrade Backlog

### Safe Hardening Tasks

1. Reduce deprecated noise that has low behavioral risk.
2. Replace or isolate low-value Soil features where the theme can own behavior directly.
3. Continue pruning dead code and legacy template leftovers before framework upgrades.

### Medium-Risk Modernization Tasks

1. Isolate WooCommerce hook customizations into a clearer upgrade surface.
2. Review overridden WooCommerce templates against the current plugin version.
3. Document and stabilize payment bootstrap assumptions around Pagar.me.
4. Review ACF options/local-field bootstrap for future compatibility.

### Structural Migration Tasks

1. Define a path away from `illuminate 5.6.*`.
2. Evaluate whether Sage 9 should be incrementally upgraded or re-platformed to a newer Roots base.
3. Modernize the JS/CSS build pipeline away from webpack 3-era tooling.
4. Reassess the long-term role of Soil in navigation, cleanup, and URL behavior.
```

- [ ] **Step 2: Add the open questions section**

Update the `## 5. Open Questions` section to include:

```md
## 5. Open Questions

- Which Soil features still deliver enough value to justify keeping the plugin?
- Is the preferred long-term path an in-place Sage 9 modernization or a structured migration to a newer Roots architecture?
- Which WooCommerce template overrides are business-critical versus stylistic?
- What is the acceptable maintenance window for checkout/payment regression testing during later phases?
```

- [ ] **Step 3: Verify the final report reads as a complete diagnostic package**

Run:

```bash
sed -n '1,260p' docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
```

Expected:

```text
A complete report containing the critical-flow map, technical baseline, coupling/risk map, prioritized backlog, and open questions.
```

- [ ] **Step 4: Commit the final diagnostic report**

```bash
git add docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md
git commit -m "docs: finalize theme upgrade phase 1 diagnostic report"
```

### Task 7: Final Verification and Handoff

**Files:**
- Verify: `docs/superpowers/reports/2026-07-07-theme-upgrade-phase-1-diagnostic.md`
- Verify: `docs/superpowers/checklists/2026-07-07-theme-upgrade-regression.md`

- [ ] **Step 1: Re-read the approved spec and confirm plan coverage**

Run:

```bash
sed -n '1,260p' docs/superpowers/specs/2026-07-07-theme-upgrade-phase-1-diagnostic-design.md
sed -n '1,260p' docs/superpowers/plans/2026-07-07-theme-upgrade-phase-1-diagnostic.md
```

Expected:

```text
The plan covers the five spec outputs: critical flows, baseline inventory, coupling/risk map, regression checklist, and prioritized backlog.
```

- [ ] **Step 2: Verify the working tree is clean except for the planned diagnostic artifacts**

Run:

```bash
git status --short
```

Expected:

```text
Only the new diagnostic report/checklist/plan files are pending if the tasks are not yet committed; otherwise the working tree is clean.
```

- [ ] **Step 3: Commit the implementation plan**

```bash
git add docs/superpowers/plans/2026-07-07-theme-upgrade-phase-1-diagnostic.md
git commit -m "docs: add theme upgrade phase 1 diagnostic plan"
```
