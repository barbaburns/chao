{{--
Single Product stock.

This template can be overridden by copying it to yourtheme/woocommerce/single-product/stock.php.

HOWEVER, on occasion WooCommerce will need to update template files and you
(the theme developer) will need to copy the new files to your theme to
maintain compatibility. We try to do this as little as possible, but it does
happen. When this occurs the version of the template file will be bumped and
the readme will list any important changes.

@see     https://docs.woocommerce.com/document/template-structure/
@package WooCommerce/Templates
@version 3.0.0
--}}

@php
  $text_availability = wp_kses_post($availability);
@endphp

<p class="stock {{ esc_attr($class) }}"> {{ $text_availability }}</p>

@if('Fora de estoque' == $text_availability)
  </div> {{-- fecha a div entry-summary-right --}}
@endif
