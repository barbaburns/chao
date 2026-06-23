# Product Manuscript Slider Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the single-product manuscript gallery into a dedicated Swiper slider without changing the loop manuscript output.

**Architecture:** The single-product manuscript block will render Swiper markup directly in the Blade partial, with one slide per image and a dedicated pagination element. A new `productManuscriptSwiper()` initializer will live beside the existing slider helpers and will be called only from the product route so the slider stays isolated from other product carousels.

**Tech Stack:** PHP, Blade, Swiper, JavaScript, SCSS

---

## File Structure

- `resources/views/partials/product-extra.blade.php`
  - Single-product extra content. This is where the manuscript block will switch from a static figure list to Swiper markup.
- `resources/assets/scripts/partials/slider.js`
  - Shared slider initializers. This will receive a dedicated `productManuscriptSwiper()` function.
- `resources/assets/scripts/routes/product.js`
  - Product-page route hook. This will call the new manuscript slider initializer only on product pages.
- `resources/assets/styles/components/_product.scss`
  - Product-specific layout and manuscript block styling. This will change from grid-list styling to slide-oriented styling.
- `tests/product_extra_manuscript_test.php`
  - Existing helper regression test. This remains green throughout to confirm the PHP manuscript data contract stays unchanged.
- `tests/product_manuscript_slider_markup_test.php`
  - New lightweight regression test that asserts the single-product manuscript Blade partial contains the expected Swiper structure and no longer renders the static list markup.

### Task 1: Lock the manuscript Blade markup contract

**Files:**
- Create: `tests/product_manuscript_slider_markup_test.php`
- Modify: `resources/views/partials/product-extra.blade.php`
- Test: `tests/product_manuscript_slider_markup_test.php`

- [ ] **Step 1: Write the failing test**

Create `tests/product_manuscript_slider_markup_test.php` with the following content:

```php
<?php

$template = file_get_contents(dirname(__DIR__) . '/resources/views/partials/product-extra.blade.php');

function assertTrueValue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

assertTrueValue(strpos($template, 'product-manuscript-slider') !== false, 'Product manuscript template should declare a dedicated slider container');
assertTrueValue(strpos($template, 'swiper-wrapper') !== false, 'Product manuscript template should render a swiper wrapper');
assertTrueValue(strpos($template, 'swiper-slide') !== false, 'Product manuscript template should render swiper slides');
assertTrueValue(strpos($template, 'swiper-pagination-product-manuscript') !== false, 'Product manuscript template should expose scoped pagination');
assertTrueValue(strpos($template, '<div class="product-manuscript">') === false, 'Product manuscript template should no longer use the static manuscript list container');

echo 'product_manuscript_slider_markup_test: ok' . PHP_EOL;
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: FAIL with `Product manuscript template should declare a dedicated slider container`

- [ ] **Step 3: Write minimal implementation**

Update the manuscript block in `resources/views/partials/product-extra.blade.php` to this structure:

```blade
    @if($manuscript)
      <div class="product-extra product-extra-manuscript">
        <h2>Manuscrito</h2>
        <div class="swiper-container product-manuscript-slider">
          <div class="swiper-wrapper">
            @foreach($manuscript['images'] as $image)
              <div class="swiper-slide">
                <figure class="product-manuscript-slide">
                  {!! $image['image'] !!}

                  @if($image['caption'])
                    <figcaption>{{ $image['caption'] }}</figcaption>
                  @endif
                </figure>
              </div>
            @endforeach
          </div>
          <div class="swiper-pagination-product-manuscript"></div>
        </div>
      </div>
    @endif
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: PASS with `product_manuscript_slider_markup_test: ok`

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS with `product_extra_manuscript_test: ok`

- [ ] **Step 5: Commit**

```bash
git add tests/product_manuscript_slider_markup_test.php resources/views/partials/product-extra.blade.php
git commit -m "test: lock manuscript slider markup"
```

### Task 2: Add dedicated Swiper initialization for the product manuscript block

**Files:**
- Modify: `resources/assets/scripts/partials/slider.js`
- Modify: `resources/assets/scripts/routes/product.js`
- Test: `tests/product_manuscript_slider_markup_test.php`

- [ ] **Step 1: Write the failing test**

Extend `tests/product_manuscript_slider_markup_test.php` with these assertions:

```php
$sliderScript = file_get_contents(dirname(__DIR__) . '/resources/assets/scripts/partials/slider.js');
$productRoute = file_get_contents(dirname(__DIR__) . '/resources/assets/scripts/routes/product.js');

assertTrueValue(strpos($sliderScript, 'function productManuscriptSwiper()') !== false, 'Slider helpers should define a product manuscript swiper initializer');
assertTrueValue(strpos($sliderScript, ".product-manuscript-slider") !== false, 'Slider helpers should target the manuscript slider selector');
assertTrueValue(strpos($sliderScript, '.swiper-pagination-product-manuscript') !== false, 'Slider helpers should target the manuscript pagination selector');
assertTrueValue(strpos($productRoute, 'productManuscriptSwiper();') !== false, 'Product route should initialize the manuscript slider');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: FAIL with `Slider helpers should define a product manuscript swiper initializer`

- [ ] **Step 3: Write minimal implementation**

Add this function to `resources/assets/scripts/partials/slider.js`:

```js
function productManuscriptSwiper() {
  if ($('.product-manuscript-slider').length) {
    new Swiper('.product-manuscript-slider', withBulletVisibility({
      autoplay: false,
      speed: 500,
      pagination: {
        el: '.swiper-pagination-product-manuscript',
        type: 'bullets',
        clickable: true,
      },
    }));
  }
}
```

Update the export at the bottom of the same file to:

```js
export { shortTextSwiper, productSwiper, homeProductsSwiper, relatedProductsSwiper, productManuscriptSwiper }
```

Update `resources/assets/scripts/routes/product.js` to:

```js
import { relatedProductsSwiper, productManuscriptSwiper } from '../partials/slider.js';

export default {
  init() {
    // JavaScript to be fired on the home page
  },
  finalize() {
    relatedProductsSwiper();
    productManuscriptSwiper();
  },
};
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: PASS with `product_manuscript_slider_markup_test: ok`

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS with `product_extra_manuscript_test: ok`

- [ ] **Step 5: Commit**

```bash
git add resources/assets/scripts/partials/slider.js resources/assets/scripts/routes/product.js tests/product_manuscript_slider_markup_test.php
git commit -m "feat: initialize manuscript slider on product page"
```

### Task 3: Update manuscript slider styling and verify the frontend bundle

**Files:**
- Modify: `resources/assets/styles/components/_product.scss`
- Test: `tests/product_extra_manuscript_test.php`

- [ ] **Step 1: Write the failing test**

Extend `tests/product_manuscript_slider_markup_test.php` with this assertion:

```php
$productStyles = file_get_contents(dirname(__DIR__) . '/resources/assets/styles/components/_product.scss');

assertTrueValue(strpos($productStyles, '.product-manuscript-slider') !== false, 'Product styles should define manuscript slider styles');
assertTrueValue(strpos($productStyles, '.product-manuscript-slide') !== false, 'Product styles should define manuscript slide styles');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: FAIL with `Product styles should define manuscript slider styles`

- [ ] **Step 3: Write minimal implementation**

Replace the current manuscript grid styles in `resources/assets/styles/components/_product.scss` with:

```scss
.product-manuscript-slider {
  width: 100%;

  .swiper-slide {
    height: auto;
  }

  .swiper-pagination-product-manuscript {
    margin-top: $default-margin / 2;
  }
}

.product-manuscript-slide {
  margin: 0;
  width: 100%;

  img {
    display: block;
    height: auto;
    width: 100%;
  }

  figcaption {
    margin-top: $default-margin / 3;
  }
}
```

- [ ] **Step 4: Run tests and checks to verify they pass**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: PASS with `product_manuscript_slider_markup_test: ok`

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS with `product_extra_manuscript_test: ok`

Run: `composer test`
Expected: exit 0 with `PHP syntax check passed.`

Run: `./node_modules/.bin/stylelint resources/assets/styles/components/_product.scss`
Expected: exit 0

Run: `npm run build`
Expected: exit 0 and a successful webpack build

- [ ] **Step 5: Commit**

```bash
git add resources/assets/styles/components/_product.scss tests/product_manuscript_slider_markup_test.php
git commit -m "style: convert manuscript gallery into swiper slider"
```
