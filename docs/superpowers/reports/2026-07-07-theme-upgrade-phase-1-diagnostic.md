# Theme Upgrade Phase 1 Diagnostic Report

## Scope

This report documents the current technical baseline of the `chao` theme and maps upgrade risk against business-critical flows.

## 1. Critical Flow Map

| Flow | User Goal | Relevant URLs/Screens | Theme Surface | Plugin/Service Coupling | Validation Method |
| --- | --- | --- | --- | --- | --- |
| Shop archive | Browse available books and enter product detail | `/shop/`, product category/tag archives | `resources/views/woocommerce/archive-product.blade.php`, `content-product*.blade.php`, `app/woocommerce.php` | WooCommerce | Open the shop archive and confirm product cards, pagination, and links render |
| Single product | Evaluate a book and its conversion content | Product permalink, gallery, tabs/comments area | `resources/views/woocommerce/content-single-product.blade.php`, `resources/views/partials/product-extra.blade.php`, `app/Controllers/Partials/ProductExtra.php` | WooCommerce, ACF | Open a product page and verify gallery, price block, manuscript CTA, and related content |
| add to cart | Add a selected product to the purchase journey without UI or hook regressions | Product-page add-to-cart form and any archive/cart entry points | Single-product WooCommerce templates plus `app/woocommerce.php` hooks | WooCommerce | Add a product to the cart and confirm cart state updates without template or JS errors |
| Cart | Review selected items before checkout | `/cart/` | WooCommerce cart templates under `resources/views/woocommerce/cart/` plus theme styles/hooks | WooCommerce | Open cart and confirm line items, totals, and quantity controls render correctly |
| Checkout | Complete purchase with the expected customer and payment fields | `/checkout/` | WooCommerce checkout templates under `resources/views/woocommerce/checkout/` and field customizations in `resources/functions.php` | WooCommerce, Pagar.me | Open checkout and confirm customer fields, order review, and payment area render cleanly |
| payment confirmation path | Reach the post-payment confirmation state safely after checkout submission | Order-received / thank-you screen and provider return path | Checkout bootstrap in `resources/functions.php` plus WooCommerce order confirmation templates/emails surface | WooCommerce, Pagar.me | Confirm the payment UI initializes, submit through a safe test path, and verify the order confirmation screen loads |
| account login/reset | Access authentication, recovery, and basic account surfaces for returning customers | `/minha-conta/`, WooCommerce login form, lost-password/reset screens, account dashboard | `resources/views/page-minha-conta.blade.php`, `resources/views/woocommerce/myaccount/`, WooCommerce global login templates, `app/woocommerce.php` | WooCommerce | Open login and lost-password/reset flows, then confirm the logged-in account dashboard renders forms, messages, navigation, and content without layout issues |
| manuscript admin import flow | Import and save manuscript source content in wp-admin | Manuscript edit screen and import action on the admin post screen | `app/Manuscripts/Admin.php`, `app/acf.php` | ACF, custom manuscript subsystem | Open a manuscript in admin, save successfully, and trigger source import without admin errors |
| manuscript frontend search/highlight/viewer | Search manuscript content and navigate highlighted results in the reader | Single manuscript permalink, inline search UI, viewer pagination/highlight state | `resources/views/partials/content-single-manuscrito.blade.php`, `app/Manuscripts/Frontend.php`, `app/Manuscripts/Search.php`, `resources/assets/scripts/routes/single-manuscrito.js` | custom manuscript subsystem | Open a manuscript single, run a search, confirm highlight behavior, and navigate viewer pages |

## 2. Technical Baseline Inventory

### runtime dependencies

- Theme architecture: Sage 9-era Blade/controller stack
- PHP requirement declared by the theme: `>=7.1`
- Composer runtime dependencies:
  - `illuminate/support 5.6.*`
  - `roots/sage-lib ~9.0.9`
  - `soberwp/controller ~2.1.0`
  - `roots/sage-woocommerce ^1.0`
- WooCommerce runtime coupling: hooks/customizations in `app/woocommerce.php` plus the WooCommerce view layer carried in `resources/views/woocommerce/`
- ACF usage model: options pages, local field groups, load-field filters, and manuscript import/admin bootstrap through `app/acf.php`
- Pagar.me runtime coupling: checkout field customizations and the local compatibility workaround in `resources/functions.php`
- Manuscript subsystem files: `app/Manuscripts/Admin.php`, `app/Manuscripts/Frontend.php`, `app/Manuscripts/Search.php`, and `resources/assets/scripts/routes/single-manuscrito.js`

### development/build dependencies

- Node build metadata: `package.json` version `9.0.9`
- Bundler generation: webpack 3
- CSS pipeline: Sass + PostCSS + Stylelint
- Script linting: ESLint 4-era toolchain

### optional/replaceable dependencies

- Soil: version `3.9.0`
- Soil modules in active use: `soil-clean-up`, `soil-jquery-cdn`, `soil-nav-walker`, `soil-nice-search`, `soil-relative-urls`, `soil-google-analytics`
- Legacy build tooling remains replaceable: webpack 3-era bundling and the ESLint 4-era stack are modernization targets rather than core business logic

### Key integration surfaces

- WooCommerce override surface: archive, `content-product*`, `content-single-product`, checkout, cart, myaccount, reviews, emails, and global login templates under `resources/views/woocommerce/`
- ACF remains a required data model layer for options, product metadata, and manuscript editorial flows even if field registration details later move
- The manuscript subsystem is custom and replaceable only with deliberate migration because admin import and frontend reader/search behavior live in theme code today

### Active Custom Subsystems

- Product content extensions in `app/Controllers/Partials/ProductExtra.php`
- Manuscript admin flow in `app/Manuscripts/Admin.php`
- Manuscript frontend/viewer/search in `app/Manuscripts/Frontend.php`, `app/Manuscripts/Search.php`, and `resources/assets/scripts/routes/single-manuscrito.js`

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

## 5. Open Questions

- Which Soil features still deliver enough value to justify keeping the plugin?
- Is the preferred long-term path an in-place Sage 9 modernization or a structured migration to a newer Roots architecture?
- Which WooCommerce template overrides are business-critical versus stylistic?
- What is the acceptable maintenance window for checkout/payment regression testing during later phases?
