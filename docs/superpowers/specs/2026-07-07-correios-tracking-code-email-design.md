# Correios Tracking Code Email Design

## Objective

Remove the clickable link from the `{tracking_code}` placeholder used by the WooCommerce Correios tracking email, keeping only the raw tracking code in both HTML and plain text email formats.

## Current Behavior

The `woocommerce-correios` plugin builds `{tracking_code}` inside `WC_Correios_Tracking_Email::get_tracking_codes()`.
That method delegates each code to `get_tracking_code_url()`, which wraps the code in an anchor pointing to the order view URL.
Because the placeholder is already HTML before the email templates render, changing only the plain text template is not enough.

## Proposed Change

Register a theme-level filter for `woocommerce_correios_email_tracking_code_url` in [`app/woocommerce.php`](/Volumes/kawase/WordPress_dev/Local/chao/app/public/wp-content/themes/chao/app/woocommerce.php).
The filter will ignore the generated HTML and return only the original tracking code string.

## Scope

- Affects the WooCommerce Correios tracking email placeholder `{tracking_code}`.
- Applies to both HTML and plain text email formats, because both formats use the same placeholder source.
- Does not change tracking links in the admin order screen or in the customer account tracking history.

## Verification

- Add a regression test that loads the WooCommerce theme hooks file, asserts the filter is registered, and verifies the callback returns the raw tracking code instead of the original HTML anchor.
