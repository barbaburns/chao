# Theme Upgrade Phase 1 Diagnostic Design

## Objective

Define a low-risk first phase for modernizing the `chao` theme base while protecting WooCommerce, checkout, payments, and the editorial manuscript workflow.

This phase does not upgrade the stack yet. It produces a reliable technical and operational inventory so later upgrade work can be sequenced safely.

## Current Context

The theme currently runs on an older Roots stack with legacy dependencies and customizations:

- Sage 9 / `roots/sage-lib ~9.0.9`
- `illuminate/* 5.6.*`
- `soberwp/controller ~2.1.0`
- Webpack 3 build tooling
- Soil 3.9.0 plugin with enabled theme supports
- Custom WooCommerce template overrides
- Custom manuscript admin/frontend features
- Local workarounds for third-party plugin compatibility, including Pagar.me

Recent work already touched:

- manuscript import-before-save flow
- manuscript viewer/search frontend
- cleanup of unused theme files
- translation timing fixes for ACF and Pagar.me-related bootstrap behavior

This means the codebase is active and business-critical, not a good candidate for a big-bang migration.

## Goal of Phase 1

Phase 1 produces five outputs:

1. A map of business-critical site flows that must remain stable during modernization.
2. A frozen inventory of the current technical stack and major dependencies.
3. A map of theme-to-plugin and theme-to-framework coupling points.
4. A manual regression checklist for repeated validation in later phases.
5. A prioritized backlog for Phase 2 hardening and later structural upgrades.

## Non-Goals

Phase 1 does not:

- upgrade Sage
- upgrade Illuminate
- replace Soil
- refactor WooCommerce templates
- replace build tooling
- change manuscript behavior
- change checkout or payment behavior

It may include small evidence-gathering probes, but no architectural migration work.

## Approach

The diagnostic is risk-first rather than stack-first.

Instead of starting from Composer or frontend tooling alone, the work begins by identifying the site areas that would create the highest business impact if broken:

- product archive and single product
- cart and checkout
- account/login flows
- Pagar.me payment flow
- manuscript post type admin flow
- manuscript frontend viewer and search

Only after those surfaces are mapped do we trace the underlying technical dependencies that support them.

## Deliverables

### 1. Critical Flow Map

Create a short inventory of the site flows that must be preserved across every later phase.

Each flow should include:

- user goal
- relevant URLs or screens
- related theme templates
- related plugins/services
- validation method

Minimum required flows:

- shop archive
- single product
- add to cart
- cart
- checkout
- payment confirmation path
- account login/reset
- manuscript admin import flow
- manuscript frontend search/highlight/viewer

### 2. Technical Baseline Inventory

Record the current versions and architecture assumptions for:

- PHP requirement declared by the theme
- Composer dependencies used by the theme
- Node/build dependencies
- Roots/Sage stack pieces
- Soil modules in active use
- WooCommerce override surface
- ACF usage model
- manuscript subsystem files

The inventory should distinguish:

- runtime dependencies
- development/build dependencies
- optional/replaceable dependencies

### 3. Coupling and Risk Map

Identify where later modernization is most likely to break behavior.

The map should explicitly cover:

- Sage bootstrap and helper assumptions
- Blade/controller coupling
- Soil theme supports and module usage
- WooCommerce hook customizations
- WooCommerce overridden templates
- Pagar.me compatibility workarounds
- ACF initialization order and options usage
- manuscript subsystem dependencies

Each coupling point should be tagged with a risk level:

- High: likely to affect checkout, product rendering, admin publishing, or payments
- Medium: likely to affect page rendering, editor UX, or build flow
- Low: mostly internal cleanup or tooling modernization

### 4. Regression Checklist

Create a compact repeatable checklist that can be run after each later phase.

The checklist should include at minimum:

- open shop archive
- open a product page
- add product to cart
- open cart
- load checkout
- verify payment UI renders
- open account login/reset
- save a manuscript post
- run manuscript source-folder import
- open manuscript single
- execute manuscript search
- navigate manuscript viewer

The checklist is intended for manual execution and should stay short enough to run repeatedly.

### 5. Prioritized Upgrade Backlog

Convert the findings into an ordered backlog for the next phases.

The backlog should separate:

- safe hardening tasks
- medium-risk modernization tasks
- structural migrations

Expected examples:

- remove or replace low-value Soil usage
- isolate WooCommerce overrides and payment-sensitive surfaces
- reduce framework coupling in bootstrap/helpers
- plan a path away from `illuminate 5.6`
- plan build-tool modernization

## Work Sequence

Phase 1 should run in this order:

1. Capture baseline versions and active dependencies.
2. Build the critical-flow map.
3. Trace coupling points behind those flows.
4. Draft the regression checklist.
5. Produce the prioritized backlog.

This order ensures that recommendations are grounded in business-critical behavior instead of generic framework hygiene.

## Validation

Phase 1 is complete when:

- all critical flows are listed
- current technical baseline is documented
- high-risk couplings are explicitly mapped
- a regression checklist exists
- next-phase tasks are prioritized

Phase 1 is not complete just because deprecated warnings are catalogued; it must produce a usable decision framework for the next implementation phase.

## Risks and Mitigations

### Risk: false confidence from static analysis

Some WordPress usage is dynamic, especially templates, hooks, and plugin integrations.

Mitigation:

- combine static inspection with known runtime flows
- avoid declaring dependencies unused unless confirmed with strong evidence

### Risk: focusing too much on framework age

Old dependencies are important, but business risk comes from where they intersect with product, checkout, and editorial workflows.

Mitigation:

- always map technical findings back to concrete site flows

### Risk: underestimating WooCommerce and payment coupling

Theme-level hook customizations can have larger effects than they appear.

Mitigation:

- treat checkout, account, and payment rendering as protected surfaces throughout the project

## Expected Output of the Next Phase

If Phase 1 succeeds, Phase 2 should begin with a scoped hardening pass rather than a blind upgrade.

That hardening pass is expected to target:

- safe dependency and bootstrap cleanup
- low-risk deprecated reduction
- isolation of payment- and WooCommerce-sensitive customizations
- preparation for larger framework and tooling upgrades later
