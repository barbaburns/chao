# Product Manuscript Slider Design

**Context**

The product page currently renders the `Manuscrito` gallery as a static vertical list in `resources/views/partials/product-extra.blade.php`. The project already uses Swiper and has an established pattern for initializing sliders through `resources/assets/scripts/partials/slider.js` and page routes.

**Goal**

Render the manuscript gallery as a dedicated Swiper slider on the single product page only, while leaving the product loop manuscript output unchanged.

**Scope**

- Update only the single product manuscript block in `resources/views/partials/product-extra.blade.php`.
- Reuse the existing Swiper dependency already bundled by the theme.
- Keep the existing PHP helper contract from `getProductManuscript()` unchanged.
- Do not change the loop extra content manuscript rendering.

**Architecture**

The manuscript section on the product page will render Swiper-compatible markup with a dedicated container, wrapper, slides, and pagination element. A new slider initializer will be added to `resources/assets/scripts/partials/slider.js` and called from the product route in `resources/assets/scripts/routes/product.js`, so the slider is only instantiated on product pages where the manuscript block exists.

**Template Changes**

- Replace the static `.product-manuscript` figure list in `resources/views/partials/product-extra.blade.php` with:
  - a dedicated manuscript slider container;
  - one `swiper-slide` per manuscript image;
  - a pagination element scoped to this slider instance.
- Preserve image captions inside each slide.

**JavaScript Changes**

- Add a `productManuscriptSwiper()` function in `resources/assets/scripts/partials/slider.js`.
- Instantiate Swiper only when the manuscript slider container exists.
- Reuse the existing pagination visibility helper so bullets hide automatically when there is only one slide.
- Call `productManuscriptSwiper()` from the product page route in `resources/assets/scripts/routes/product.js`.

**Styling Changes**

- Update `resources/assets/styles/components/_product.scss` so the manuscript block styles the slide layout instead of a CSS grid list.
- Reuse the existing Swiper pagination styles where possible.
- Keep the manuscript area full-width in the single product layout.

**Testing and Verification**

- Keep `tests/product_extra_manuscript_test.php` green to confirm the PHP helper contract remains intact.
- Run `composer test`.
- Run stylelint for the touched SCSS files.
- Run a production or development JS build check if needed after changing slider initialization code.

**Out of Scope**

- Converting the manuscript gallery in `resources/views/partials/product-loop-extra-content.blade.php` into a slider.
- Changing ACF field structure or helper return shape.
- Refactoring unrelated product sliders.
