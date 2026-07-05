@php
  global $product;

  if (empty($product) && function_exists('wc_get_product')) {
    $product = wc_get_product(get_the_ID());
  }
@endphp

@php
  if (empty($product) || !$product->is_visible()) {
    return;
  }
@endphp

<div @php wc_product_class('product-related-manuscript', $product) @endphp>
  <a class="woocommerce-LoopProduct-link" href="{{ get_permalink($product->get_id()) }}">
    {!! $product->get_image('woocommerce_thumbnail') !!}

    <div class="product-info">
      <h3 class="woocommerce-loop-product__title">{{ get_the_title($product->get_id()) }}</h3>

      @if($product->get_price_html())
        <span class="price">{!! $product->get_price_html() !!}</span>
      @endif
    </div>
  </a>
</div>
