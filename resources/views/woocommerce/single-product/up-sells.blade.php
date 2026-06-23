{{--
Single Product Up-Sells

This template can be overridden by copying it to yourtheme/woocommerce/single-product/up-sells.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see         https://docs.woocommerce.com/document/template-structure/
@package     WooCommerce/Templates
@version     3.0.0
--}}

@if( $upsells )

	<section class="up-sells upsells products">

		<h2>Obras relacionadas</h2>

		@php
  		woocommerce_product_loop_start();
    @endphp

    <div class="swiper-container" id="swiper-related-products">
      <div class="swiper-wrapper">
			  @foreach ( $upsells as $upsell )
          <div class="swiper-slide">
            @php
              $post_object = get_post( $upsell->get_id() );

              setup_postdata( $GLOBALS['post'] =& $post_object );
            @endphp

            @include('woocommerce.content-product-simple')
          </div>
			  @endforeach
      </div>
    </div>
    <div class="swiper-pagination-related-products"></div>

		@php
      woocommerce_product_loop_end();
    @endphp

	</section>
@endif

@php
  wp_reset_postdata();
@endphp
