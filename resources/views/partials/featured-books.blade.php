@if($get_featured_books)
  <div class="featured-book">
    <div class="grid-header">
      @include('partials.page-header', [
        'head'  => 'h2',
        'title' => App::getPageTitle(16)
      ])
      @include('partials.page-header-text', ['field' => 'header_our_books'])

      <a href="{{ App::storeLink() }}">{{ $get_featured_link }}</a>
    </div>

    <div class="product-slider">
      <div class="swiper-container" id="swiper-product-home">
        <div class="swiper-wrapper">
          @foreach($get_featured_books as $product)
            @php
              setup_postdata($GLOBALS['post'] =& $product);
            @endphp
              @include('woocommerce.content-product-basic')
            @php
              wp_reset_postdata();
            @endphp
          @endforeach
        </div>
        <div class="swiper-pagination-product-home"></div>
      </div>
      <div class="swiper-button-prev swiper-button-prev-product-home">
        @include('icon::arrow-left')
      </div>

      <div class="swiper-button-next swiper-button-next-product-home">
        @include('icon::arrow-right')
      </div>
    </div>
  </div>
@endif
