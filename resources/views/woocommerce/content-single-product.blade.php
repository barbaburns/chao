{{--
The template for displaying product content in the single-product.php template

This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see     https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.6.0
--}}

@php
  global $product;
@endphp

@if($get_featured_image)
  <figure class="featured-image">
    {!! $get_featured_image['image'] !!}
    {!! $get_featured_image['image_mobile'] !!}
  </figure>
@endif

{{--
Hook: woocommerce_before_single_product.
@hooked wc_print_notices - 10
--}}
@php
  do_action( 'woocommerce_before_single_product' );
@endphp

@if(post_password_required())
  {!! get_the_password_form(); !!}
  @php return; @endphp
@endif

<div id="product-{{ the_ID() }}" @php wc_product_class('main-product', $product ) @endphp>
  {{--
  Hook: woocommerce_before_single_product_summary.
  @hooked woocommerce_show_product_sale_flash - 10
  @hooked woocommerce_show_product_images - 20
  --}}
  @php
    do_action( 'woocommerce_before_single_product_summary' );
  @endphp

  <div class="summary entry-summary">
    {{--
    Hook: woocommerce_single_product_summary.
    @hooked woocommerce_template_single_title - 5
    @hooked woocommerce_template_single_rating - 10
    @hooked woocommerce_template_single_price - 10
    @hooked woocommerce_template_single_excerpt - 20
    @hooked woocommerce_template_single_add_to_cart - 30
    @hooked woocommerce_template_single_meta - 40
    @hooked woocommerce_template_single_sharing - 50
    @hooked WC_Structured_Data::generate_product_data() - 60
    --}}
    @php
      do_action( 'woocommerce_single_product_summary' );
    @endphp

    @include('partials.product-info')

    @include('partials.product-extra', ['id' => $product->get_id()])

    @php comments_template('/partials/comments.blade.php') @endphp
  </div>

  {{--
  Hook: woocommerce_after_single_product_summary.
  @hooked woocommerce_output_product_data_tabs - 10
  @hooked woocommerce_upsell_display - 15
  @hooked woocommerce_output_related_products - 20
  --}}
  @php
    do_action( 'woocommerce_after_single_product_summary' );
  @endphp
</div>

@php
  woocommerce_output_related_products();
  woocommerce_upsell_display();
@endphp

@php
  do_action( 'woocommerce_after_single_product' )
@endphp
