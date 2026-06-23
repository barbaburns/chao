{{--
The Template for displaying product archives, including the main shop page which is a post type archive

This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.6.0
--}}

@php
  global $product;
@endphp

@php
  if (empty($product) || !$product->is_visible()) {
    return;
  }
@endphp

<div class="product-slider">
  <div @php wc_product_class( 'swiper-container swiper-product', $product ) @endphp data-instance="{{ $product->get_id() }}">
    <div class="swiper-wrapper">
      @include('woocommerce.content-product-basic')
      <!-- @include('partials.product-loop-extra-content', ['id' => $product->get_id()]) -->
    </div>
    <div class="swiper-pagination-{{ $product->get_id() }}"></div>
  </div>
  <div class="swiper-button-prev swiper-button-prev-{{ $product->get_id() }}">
    @include('icon::arrow-left')
  </div>

  <div class="swiper-button-next swiper-button-next-{{ $product->get_id() }}">
    @include('icon::arrow-right')
  </div>
</div>
