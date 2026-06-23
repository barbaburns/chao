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

<div @php wc_product_class( 'product-simple-layout', $product ) @endphp>
  {{--
  Hook: woocommerce_before_shop_loop_item.
  @hooked woocommerce_template_loop_product_link_open - 10
  --}}
  @php
  do_action( 'woocommerce_before_shop_loop_item' );
  @endphp

  {{--
  Hook: woocommerce_before_shop_loop_item_title.
  @hooked woocommerce_show_product_loop_sale_flash - 10
  @hooked woocommerce_template_loop_product_thumbnail - 10
  --}}
  @php
  do_action( 'woocommerce_before_shop_loop_item_title' );
  @endphp

  <div class="product-info">
    {{--
    Hook: woocommerce_shop_loop_item_title.
    @hooked woocommerce_template_loop_product_title - 10
    --}}
    @php
      do_action( 'woocommerce_shop_loop_item_title' );
    @endphp

    {{--
    Hook: woocommerce_after_shop_loop_item_title.
    @hooked woocommerce_template_loop_rating - 5
    @hooked woocommerce_template_loop_price - 10
    --}}
    @php
      do_action( 'woocommerce_after_shop_loop_item_title' );
    @endphp

    {{--
    Hook: woocommerce_after_shop_loop_item.
    @hooked woocommerce_template_loop_product_link_close - 5
    @hooked woocommerce_template_loop_add_to_cart - 10
    --}}
    @php
      do_action( 'woocommerce_after_shop_loop_item' );
    @endphp
  </div>
</div>
