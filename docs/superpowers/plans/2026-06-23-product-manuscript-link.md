# Product Manuscript Link Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Keep the single-product manuscript slider as-is and make clicking manuscript images redirect to the single page of the `manuscrito` Post Object selected in ACF.

**Architecture:** The manuscript helper will keep returning the gallery images and will add one top-level manuscript permalink derived from the `manuscrito` Post Object field. The Blade partial will preserve the existing Swiper structure and wrap each slide image in a link only when that permalink exists, leaving the slider non-clickable when the field is empty or invalid.

**Tech Stack:** PHP, Blade, Swiper, SCSS, ACF

---

## File Structure

- `app/Controllers/Partials/ProductExtra.php`
  - Product extra helper. This is where the `manuscrito` Post Object field will be read and normalized into a single permalink.
- `resources/views/partials/product-extra.blade.php`
  - Single-product manuscript slider markup. This will keep the Swiper structure and conditionally wrap the image in an anchor.
- `resources/assets/styles/components/_product.scss`
  - Product manuscript slider styling. This may receive minimal link styling only if the wrapped image needs it.
- `tests/product_extra_manuscript_test.php`
  - Helper regression test. This will validate the new top-level manuscript permalink.
- `tests/product_manuscript_slider_markup_test.php`
  - Template regression test. This will validate that the slider renders linked images when the manuscript URL exists and that the Swiper structure remains intact.

### Task 1: Expose the manuscript permalink in the helper contract

**Files:**
- Modify: `tests/product_extra_manuscript_test.php`
- Modify: `app/Controllers/Partials/ProductExtra.php`
- Test: `tests/product_extra_manuscript_test.php`

- [ ] **Step 1: Write the failing test**

Update `tests/product_extra_manuscript_test.php` so the fixture includes a `manuscrito` Post Object value and the helper is expected to return its permalink:

```php
$GLOBALS['product_extra_manuscript_fields'][11]['manuscrito'] = 501;

function get_permalink($postId)
{
    return $postId === 501 ? 'https://example.test/manuscritos/caderno-azul/' : '';
}
```

Add this assertion after the gallery assertions:

```php
assertSameValue('https://example.test/manuscritos/caderno-azul/', $manuscript['url'], 'The helper should expose the permalink of the selected manuscript post');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_extra_manuscript_test.php`
Expected: FAIL with `The helper should expose the permalink of the selected manuscript post`

- [ ] **Step 3: Write minimal implementation**

Update `app/Controllers/Partials/ProductExtra.php` so `getProductManuscript()` reads the `manuscrito` field, resolves the permalink, and returns it alongside `images`:

```php
        $manuscriptPost = get_field('manuscrito', $id);
        $manuscriptUrl = '';

        if ($manuscriptPost) {
            $manuscriptUrl = esc_url(get_permalink(is_object($manuscriptPost) ? $manuscriptPost->ID : $manuscriptPost));
        }

        return [
            'images' => $images,
            'url' => $manuscriptUrl,
        ];
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS with `product_extra_manuscript_test: ok`

- [ ] **Step 5: Commit**

```bash
git add tests/product_extra_manuscript_test.php app/Controllers/Partials/ProductExtra.php
git commit -m "test: expose manuscript post permalink"
```

### Task 2: Link manuscript slider images to the manuscript single page

**Files:**
- Modify: `tests/product_manuscript_slider_markup_test.php`
- Modify: `resources/views/partials/product-extra.blade.php`
- Test: `tests/product_manuscript_slider_markup_test.php`

- [ ] **Step 1: Write the failing test**

Extend `tests/product_manuscript_slider_markup_test.php` to require linked slider images:

```php
$manuscriptLinkPattern = '/<a\\s+href="\\{\\{ \\$manuscript\\[\'url\'\\] \\}\\}"[^>]*>\\s*\\{!! \\$image\\[\'image\'\\] !!\\}/s';

assertTrueValue(preg_match($manuscriptLinkPattern, $template) === 1, 'Product manuscript template should wrap slide images in a manuscript permalink when available');
```

Add a guard that the Swiper structure still exists:

```php
assertTrueValue(strpos($template, 'swiper-container product-manuscript product-manuscript-slider') !== false, 'Product manuscript slider container should stay intact');
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: FAIL with `Product manuscript template should wrap slide images in a manuscript permalink when available`

- [ ] **Step 3: Write minimal implementation**

Update the slide image markup in `resources/views/partials/product-extra.blade.php` to:

```blade
                <figure class="product-manuscript-slide">
                  @if($manuscript['url'])
                    <a href="{{ $manuscript['url'] }}">
                      {!! $image['image'] !!}
                    </a>
                  @else
                    {!! $image['image'] !!}
                  @endif

                  @if($image['caption'])
                    <figcaption>{{ $image['caption'] }}</figcaption>
                  @endif
                </figure>
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: PASS with `product_manuscript_slider_markup_test: ok`

Run: `php tests/product_extra_manuscript_test.php`
Expected: PASS with `product_extra_manuscript_test: ok`

- [ ] **Step 5: Commit**

```bash
git add tests/product_manuscript_slider_markup_test.php resources/views/partials/product-extra.blade.php
git commit -m "feat: link manuscript slider to manuscript page"
```

### Task 3: Keep the slider styling intact and verify the full integration

**Files:**
- Modify: `tests/product_manuscript_slider_markup_test.php`
- Modify: `resources/assets/styles/components/_product.scss`
- Test: `tests/product_manuscript_slider_markup_test.php`

- [ ] **Step 1: Write the failing test**

Extend `tests/product_manuscript_slider_markup_test.php` so it requires the width cap and a minimal linked-image styling hook:

```php
assertTrueValue(strpos($productStyles, 'max-width: 400px;') !== false, 'Product manuscript slider styles should keep the 400px width cap');
assertTrueValue(strpos($productStyles, '.product-manuscript-slide a') !== false || strpos($productStyles, '.product-manuscript-slide img') !== false, 'Product manuscript styles should keep linked slide images visually stable');
```

- [ ] **Step 2: Run test to verify it fails only if styling is missing**

Run: `php tests/product_manuscript_slider_markup_test.php`
Expected: PASS if existing styling already satisfies the assertions, otherwise FAIL with the relevant missing-style message

- [ ] **Step 3: Write minimal implementation only if needed**

If the test from Step 2 fails, add the smallest possible rule to `resources/assets/styles/components/_product.scss`, for example:

```scss
.product-manuscript-slide a {
  display: block;
}
```

Do not change the existing slider sizing unless the test proves it is missing.

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
git commit -m "style: preserve manuscript slider link layout"
```
