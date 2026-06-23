{{--
Informações básicas do produto
Template para ser usado como um slide
--}}

<div @php wc_product_class( 'swiper-slide product-image', $product ) @endphp>
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

    @php
      $resolvedProductId = \App\resolve_product_id($product_id ?? null, $product ?? null);
      $soon = $resolvedProductId ? get_field('em_breve', $resolvedProductId) : false;
    @endphp

    @if($soon)

      <div class="remove">
        {{--
        Hook: woocommerce_after_shop_loop_item.
        @hooked woocommerce_template_loop_product_link_close - 5
        @hooked woocommerce_template_loop_add_to_cart - 10
        --}}

        @php
          do_action('woocommerce_after_shop_loop_item');
        @endphp
      </div>

    @else

      {{--
      Hook: woocommerce_after_shop_loop_item.
      @hooked woocommerce_template_loop_product_link_close - 5
      @hooked woocommerce_template_loop_add_to_cart - 10
      --}}

      @php
        do_action('woocommerce_after_shop_loop_item');
      @endphp

    @endif
  </div>
</div>
