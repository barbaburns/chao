# Product Manuscript Link Design

**Context**

The single-product manuscript block already renders as a dedicated Swiper slider, and the current requirement is to keep that slider behavior unchanged. A new ACF field with slug `manuscrito` now points to a `manuscrito` post type entry through a Post Object field.

**Goal**

Keep the manuscript slider on the single product page with `max-width: 400px` and make clicking manuscript images redirect to the single page of the `manuscrito` post selected in the new ACF field.

**Scope**

- Update only the single-product manuscript block.
- Reuse the current Swiper slider behavior without adding lightbox or fullscreen behavior.
- Read the `manuscrito` Post Object field and link manuscript slider images to that entry.
- Keep the loop manuscript rendering unchanged.

**Architecture**

`App::getProductManuscript()` will read both `manuscript_gallery` and the `manuscrito` Post Object field, resolve the manuscript permalink, and expose it in the view model. The single-product manuscript Blade partial will keep the existing Swiper markup and wrap each image in a link only when the manuscript URL exists. If the `manuscrito` field is empty or invalid, the slider remains visible and images render without links.

**Data Contract**

`App::getProductManuscript()` will continue returning the `images` array and will add a top-level `url` field for the manuscript permalink:

- `images`
- `url`

Each item in `images` still contains:

- rendered image HTML
- caption

**Template Changes**

- Keep the existing Swiper structure in `resources/views/partials/product-extra.blade.php`.
- Wrap each slide image in an anchor to `{{ $manuscript['url'] }}` only when that URL exists.
- Leave captions and slide structure unchanged.

**Styling Changes**

- Keep the current `max-width: 400px` slider width constraint.
- Add only minimal clickable-link styling if needed so the link does not visually break the current slider layout.

**Testing and Verification**

- Extend `tests/product_extra_manuscript_test.php` to validate the manuscript permalink in the helper return value.
- Extend `tests/product_manuscript_slider_markup_test.php` to verify that the single-product manuscript slider renders links when the manuscript URL is available.
- Run `php tests/product_extra_manuscript_test.php`.
- Run `php tests/product_manuscript_slider_markup_test.php`.
- Run `composer test`.
- Run stylelint on touched SCSS files.
- Run `npm run build`.

**Out of Scope**

- Reintroducing lightbox or fullscreen viewer behavior.
- Changing the main product gallery behavior.
- Changing the manuscript loop block outside the single-product page.
