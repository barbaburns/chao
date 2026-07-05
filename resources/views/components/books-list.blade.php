@php
  $variant = $variant ?? 'default';
@endphp

@if ( $books )

  <section class="related-books">
    
    <h2>Obras relacionadas</h2>

    @php
      woocommerce_product_loop_start();
    @endphp

    <div class="swiper-container" id="swiper-autores">
      <div class="swiper-wrapper">
        @foreach ( $books as $book )
          <div class="swiper-slide">
            @php
              $post_object = get_post( $book->ID );
              setup_postdata($GLOBALS['post'] =& $post_object);
              $GLOBALS['product'] = function_exists('wc_get_product') ? wc_get_product($post_object->ID) : null;
            @endphp

            @if($variant === 'manuscript')
              @include('woocommerce.content-product-related-manuscript')
            @else
              @include('woocommerce.content-product-simple')
            @endif
          </div>
        @endforeach
      </div>
    </div>
    <div class="swiper-pagination"></div>
    @php
      woocommerce_product_loop_end();
    @endphp
  </section>
@endif

@php
  wp_reset_postdata();
@endphp
