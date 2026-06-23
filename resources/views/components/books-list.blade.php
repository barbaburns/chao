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
            @endphp

            @include('woocommerce.content-product-simple')
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
