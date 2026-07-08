# Correios Tracking Code Email Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the WooCommerce Correios tracking email render `{tracking_code}` as plain text instead of a clickable link in both HTML and plain text emails.

**Architecture:** Add a single WooCommerce filter in the theme hook file so the plugin keeps generating the email normally while the theme replaces the linked tracking-code HTML with the raw tracking code string. Cover the change with a standalone PHP regression test that exercises the registered filter callback directly.

**Tech Stack:** PHP, WordPress hooks, WooCommerce theme integration, standalone PHP regression tests

---

### Task 1: Add Regression Coverage For The Tracking Code Email Hook

**Files:**
- Create: `tests/correios_tracking_email_plain_code_test.php`
- Test: `tests/correios_tracking_email_plain_code_test.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

$GLOBALS['registered_filters'] = [];

function add_filter($hook, $callback, $priority = 10, $acceptedArgs = 1)
{
    $GLOBALS['registered_filters'][$hook][] = [
        'callback' => $callback,
        'priority' => $priority,
        'accepted_args' => $acceptedArgs,
    ];
}

function add_action($hook, $callback, $priority = 10, $acceptedArgs = 1)
{
}

function remove_action($hook, $callback, $priority = 10)
{
}

require_once dirname(__DIR__) . '/app/woocommerce.php';

$callbacks = $GLOBALS['registered_filters']['woocommerce_correios_email_tracking_code_url'] ?? [];

if ($callbacks === []) {
    fwrite(STDERR, "Expected WooCommerce Correios tracking-code email filter to be registered.\n");
    exit(1);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/correios_tracking_email_plain_code_test.php`
Expected: FAIL with a message saying the WooCommerce Correios tracking-code email filter is not registered.

- [ ] **Step 3: Write minimal implementation**

```php
add_filter('woocommerce_correios_email_tracking_code_url', function ($html, $trackingCode) {
    return $trackingCode;
}, 10, 3);
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/correios_tracking_email_plain_code_test.php`
Expected: PASS and print an `ok` line.

- [ ] **Step 5: Commit**

```bash
git add tests/correios_tracking_email_plain_code_test.php app/woocommerce.php docs/superpowers/specs/2026-07-07-correios-tracking-code-email-design.md docs/superpowers/plans/2026-07-07-correios-tracking-code-email.md
git commit -m "fix: remove Correios tracking email links"
```
