# Product Manuscript Lightbox Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Keep the manuscript slider on the single product page, cap it at `max-width: 400px`, and open manuscript images in the same fullscreen lightbox pattern as the product gallery while keeping navigation isolated to manuscript images.

**Architecture:** The manuscript helper will expose the full-size image metadata needed by the lightbox, and the Blade partial will render each slide as a lightbox link. On the frontend, we will reuse the PhotoSwipe lightbox already loaded by WooCommerce on product pages, but initialize a dedicated manuscript-only gallery from the product route so fullscreen navigation stays separate from the main product gallery.

**Tech Stack:** PHP, Blade, JavaScript, WooCommerce PhotoSwipe lightbox, Swiper, SCSS

---

## File Structure

- `app/Controllers/Partials/ProductExtra.php`
  - Product extra helper layer. This is where the manuscript gallery data contract will be extended with full-size image metadata for the lightbox.
- `resources/views/partials/product-extra.blade.php`
  - Single-product manuscript block. This will keep the Swiper structure and wrap each slide image in a lightbox link.
- `resources/assets/scripts/partials/slider.js`
  - Shared product-slider behavior. This will gain a dedicated `productManuscriptLightbox()` initializer that opens a PhotoSwipe gallery from manuscript slide links.
- `resources/assets/scripts/routes/product.js`
  - Product-page route hook. This will call the new manuscript lightbox initializer alongside the manuscript slider initializer.
- `resources/assets/styles/components/_product.scss`
  - Product manuscript slider styling. This will enforce the `max-width: 400px` constraint and style lightbox-trigger links.
- `tests/product_extra_manuscript_test.php`
  - Helper regression test. This will be extended to lock the new fullscreen-viewer fields.
- `tests/product_manuscript_slider_markup_test.php`
  - Structural template/JS/CSS regression test. This will be extended to cover the manuscript lightbox markup and initialization wiring.

### Task 1: Extend the manuscript helper contract for fullscreen viewing

**Files:**
- Modify: `tests/product_extra_manuscript_test.php`
- Modify: `app/Controllers/Partials/ProductExtra.php`
- Test: `tests/product_extra_manuscript_test.php`

- [ ] **Step 1: Write the failing test**

Update `tests/product_extra_manuscript_test.php` so the gallery fixtures include full-image metadata and the assertions require the helper to expose it:

```php
$GLOBALS['product_extra_manuscript_fields'][11]['manuscript_gallery'] = [
    [
        'ID' => 101,
        'caption' => 'Primeira pagina',
        'url' => 'https://example.test/uploads/manuscript-101-full.jpg',
        'width' => 1600,
        'height' => 2400,
    ],
    [
        'ID' => 202,
        'caption' => '',
        'url' => 'https://example.test/uploads/manuscript-202-full.jpg',
        'width' => 1200,
        'height' => 1800,
    ],
    [
        'caption' => 'Sem ID',
    ],
];
```

Add these assertions after the existing image/caption assertions:

```php
assertSameValue('https://example.test/uploads/manuscript-101-full.jpg', $manuscript['images'][0]['full_url'], 'The helper should expose the full image url for the lightbox');
assertSameValue(1600, $manuscript['images'][0]['width'], 'The helper should expose the full image width for the lightbox');
assertSameValue(2400, $manuscript['images'][0]['height'], 'The helper should expose the full image height for the lightbox');
assertSameValue('https://example.test/uploads/manuscript-202-full.jpg', $manuscript['images'][1]['full_url'], 'The helper should keep full image urls for each manuscript slide');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_extra_manuscript_test.php`
Expected: FAIL with `The helper should expose the full image url for the lightbox`

- [ ] **Step 3: Write minimal implementation**

Update the manuscript mapper in `app/Controllers/Partials/ProductExtra.php` to return the new fields directly from the ACF gallery array:

```php
        $images = array_values(array_filter(array_map(function ($image) {
            if (empty($image['ID'])) {
                return null;
            }

            return [
                'image' => wp_get_attachment_image($image['ID'], 'large'),
                'full_url' => esc_url($image['url'] ?? ''),
                'width' => (int) ($image['width'] ?? 0),
                'height' => (int) ($image['height'] ?? 0),
                'caption' => esc_html($image['caption'] ?? ''),
            ];
        }, $gallery)));
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS with `product_extra_manuscript_test: ok`

- [ ] **Step 5: Commit**

```bash
git add tests/product_extra_manuscript_test.php app/Controllers/Partials/ProductExtra.php
git commit -m "test: expose manuscript lightbox metadata"
```

### Task 2: Render manuscript slides as isolated lightbox items and wire the fullscreen viewer

**Files:**
- Modify: `tests/product_manuscript_slider_markup_test.php`
- Modify: `resources/views/partials/product-extra.blade.php`
- Modify: `resources/assets/scripts/partials/slider.js`
- Modify: `resources/assets/scripts/routes/product.js`
- Test: `tests/product_manuscript_slider_markup_test.php`

- [ ] **Step 1: Write the failing test**

Extend `tests/product_manuscript_slider_markup_test.php` with these patterns and assertions:

```php
$manuscriptLightboxPattern = '/<a[^>]*class="product-manuscript-lightbox"[^>]*data-product-manuscript-lightbox[^>]*data-product-manuscript-width="\\{\\{ \\$image\\[\\'width\\'\\] \\}\\}"[^>]*data-product-manuscript-height="\\{\\{ \\$image\\[\\'height\\'\\] \\}\\}"[^>]*href="\\{\\{ \\$image\\[\\'full_url\\'\\] \\}\\}"[^>]*>/s';
$productManuscriptLightboxPattern = '/function productManuscriptLightbox\\(\\)\\s*\\{(?P<body>.*?)\\n\\}/s';
```

Add these assertions:

```php
assertTrueValue(preg_match($manuscriptLightboxPattern, $template) === 1, 'Product manuscript template should render manuscript lightbox links with fullscreen metadata');

$productManuscriptLightboxMatches = extractMatch($productManuscriptLightboxPattern, $sliderScript, 'Slider script should define a productManuscriptLightbox function');
$productManuscriptLightboxBody = $productManuscriptLightboxMatches['body'];

assertTrueValue(strpos($productManuscriptLightboxBody, "document.querySelectorAll('[data-product-manuscript-lightbox]')") !== false, 'productManuscriptLightbox should gather manuscript lightbox links');
assertTrueValue(strpos($productManuscriptLightboxBody, "new PhotoSwipe(") !== false, 'productManuscriptLightbox should open a PhotoSwipe gallery');
assertTrueValue(strpos($productManuscriptLightboxBody, "PhotoSwipeUI_Default") !== false, 'productManuscriptLightbox should reuse the WooCommerce PhotoSwipe UI');
assertTrueValue(strpos($productManuscriptLightboxBody, "event.preventDefault();") !== false, 'productManuscriptLightbox should intercept slide clicks');
assertTrueValue(strpos($productRouteScript, 'productManuscriptLightbox();') !== false, 'Product route should initialize the manuscript lightbox');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: FAIL with `Product manuscript template should render manuscript lightbox links with fullscreen metadata`

- [ ] **Step 3: Write minimal implementation**

Update the slide markup in `resources/views/partials/product-extra.blade.php` so each image becomes a dedicated manuscript lightbox link:

```blade
              <div class="swiper-slide">
                <figure class="product-manuscript-slide">
                  <a
                    href="{{ $image['full_url'] }}"
                    class="product-manuscript-lightbox"
                    data-product-manuscript-lightbox
                    data-product-manuscript-width="{{ $image['width'] }}"
                    data-product-manuscript-height="{{ $image['height'] }}"
                    data-product-manuscript-caption="{{ $image['caption'] }}"
                  >
                    {!! $image['image'] !!}
                  </a>

                  @if($image['caption'])
                    <figcaption>{{ $image['caption'] }}</figcaption>
                  @endif
                </figure>
              </div>
```

Add this function to `resources/assets/scripts/partials/slider.js`:

```js
function productManuscriptLightbox() {
  var lightboxLinks = Array.from(document.querySelectorAll('[data-product-manuscript-lightbox]'));

  if (!lightboxLinks.length || typeof PhotoSwipe === 'undefined' || typeof PhotoSwipeUI_Default === 'undefined') {
    return;
  }

  var pswpElement = document.querySelector('.pswp');

  if (!pswpElement) {
    return;
  }

  var items = lightboxLinks.map(function(link) {
    return {
      src: link.getAttribute('href'),
      w: parseInt(link.dataset.productManuscriptWidth, 10) || 0,
      h: parseInt(link.dataset.productManuscriptHeight, 10) || 0,
      title: link.dataset.productManuscriptCaption || '',
    };
  });

  lightboxLinks.forEach(function(link, index) {
    link.addEventListener('click', function(event) {
      event.preventDefault();

      var gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, {
        index: index,
      });

      gallery.init();
    });
  });
}
```

Update the export in the same file to:

```js
export { shortTextSwiper, productSwiper, homeProductsSwiper, relatedProductsSwiper, productManuscriptSwiper, productManuscriptLightbox }
```

Update `resources/assets/scripts/routes/product.js` to:

```js
import { relatedProductsSwiper, productManuscriptSwiper, productManuscriptLightbox } from '../partials/slider.js';

export default {
  init() {
    // JavaScript to be fired on the home page
  },
  finalize() {
    relatedProductsSwiper();
    productManuscriptSwiper();
    productManuscriptLightbox();
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
git add tests/product_manuscript_slider_markup_test.php resources/views/partials/product-extra.blade.php resources/assets/scripts/partials/slider.js resources/assets/scripts/routes/product.js
git commit -m "feat: open manuscript slides in fullscreen lightbox"
```

### Task 3: Constrain the manuscript slider width and verify the integrated build

**Files:**
- Modify: `tests/product_manuscript_slider_markup_test.php`
- Modify: `resources/assets/styles/components/_product.scss`
- Test: `tests/product_manuscript_slider_markup_test.php`

- [ ] **Step 1: Write the failing test**

Extend `tests/product_manuscript_slider_markup_test.php` so the SCSS assertions require both the new width cap and clickable-link styling:

```php
assertTrueValue(strpos($productStyles, 'max-width: 400px;') !== false, 'Product manuscript slider styles should cap the slider width at 400px');
assertTrueValue(strpos($productStyles, '.product-manuscript-lightbox') !== false, 'Product manuscript styles should define the manuscript lightbox link');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: FAIL with `Product manuscript slider styles should cap the slider width at 400px`

- [ ] **Step 3: Write minimal implementation**

Update `resources/assets/styles/components/_product.scss` to constrain the slider and make the image clearly clickable:

```scss
.product-manuscript-slider {
  display: block;
  max-width: 400px;
  width: 100%;

  .swiper-wrapper {
    display: flex;
  }

  .swiper-slide {
    height: auto;
  }
}

.product-manuscript-lightbox {
  cursor: zoom-in;
  display: block;
}
```

Leave the existing `.product-manuscript` and `.product-manuscript-slide` rules in place unless a test proves they must change further.

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
git add tests/product_manuscript_slider_markup_test.php resources/assets/styles/components/_product.scss
git commit -m "style: cap manuscript slider and lightbox trigger"
```
