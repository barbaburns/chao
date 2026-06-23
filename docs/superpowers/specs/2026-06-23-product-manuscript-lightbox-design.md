# Product Manuscript Lightbox Design

**Context**

The single-product manuscript block already renders as a dedicated Swiper slider. The main product gallery on the same page already uses the WooCommerce lightbox support enabled by the theme through `wc-product-gallery-lightbox`.

**Goal**

Keep the manuscript slider on the single product page, constrain it to `max-width: 400px`, and make each manuscript image open in the same fullscreen lightbox pattern already used by the product gallery, while keeping manuscript navigation isolated to manuscript images only.

**Scope**

- Update only the single-product manuscript block.
- Reuse the WooCommerce lightbox pattern already present on product pages.
- Keep manuscript fullscreen navigation limited to manuscript images.
- Do not change the main product gallery behavior.
- Do not change the loop manuscript rendering.

**Architecture**

The manuscript slider remains a Swiper instance in `resources/views/partials/product-extra.blade.php`, but each slide image becomes a link to the full-size attachment with the data expected by the WooCommerce lightbox flow. The manuscript block will expose its own grouped lightbox items so fullscreen navigation stays within manuscript images instead of merging with the main product gallery.

**Data Contract**

`App::getProductManuscript()` will be extended to expose the information needed by the lightbox markup:

- rendered slide image HTML
- full-size image URL
- image width
- image height
- caption

This keeps the Blade partial simple and avoids recalculating attachment metadata in the template.

**Template Changes**

- Keep the Swiper structure in `resources/views/partials/product-extra.blade.php`.
- Wrap each manuscript slide image in an anchor targeting the full-size image.
- Add the lightbox-related attributes needed for the WooCommerce/PhotoSwipe pattern.
- Keep captions visible in the slider and available to the fullscreen viewer when supported.

**Styling Changes**

- Update `resources/assets/styles/components/_product.scss` so `.product-manuscript-slider` uses `max-width: 400px`.
- Preserve responsive behavior so the block still works on smaller screens.
- Style the clickable slide so it behaves like a gallery item rather than a plain static figure.

**JavaScript Changes**

- Prefer reusing the existing WooCommerce lightbox bootstrapping without adding a new dependency.
- Only if the manuscript block does not get picked up automatically by the existing WooCommerce lightbox flow should we add a small product-page-specific initializer to register the manuscript group.

**Testing and Verification**

- Extend `tests/product_extra_manuscript_test.php` to validate the new helper fields for fullscreen viewing.
- Extend `tests/product_manuscript_slider_markup_test.php` to verify the manuscript slider renders lightbox links and grouping attributes.
- Run `php tests/product_extra_manuscript_test.php`.
- Run `php tests/product_manuscript_slider_markup_test.php`.
- Run `composer test`.
- Run stylelint on touched SCSS files.
- Run `npm run build`.

**Out of Scope**

- Changing the main WooCommerce product gallery markup or behavior.
- Merging manuscript images into the main product gallery lightbox set.
- Adding a separate third-party lightbox library unless reuse of the WooCommerce pattern proves impossible.
