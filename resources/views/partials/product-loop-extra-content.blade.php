@if(wp_is_mobile() && $description = App::getProductShortDescription())
  <div class="swiper-slide">
    <div class="product-short-description">
      <p>{!! $description !!}</p>
    </div>
  </div>
@endif

@if($excerpt = App::getProductExcerpt($id))
  <div class="swiper-slide">
    <blockquote class="product-excerpt">
      <!-- <p>
        @component('components.link', ['link' => $excerpt['url']])
          {!! $excerpt['text'] !!}
        @endcomponent
      </p> -->
      <?php echo wp_kses_post( get_field('excerpt_text') ); ?>
    </blockquote>
  </div>
@endif

@if($clipping = App::getProductClipping($id))
  <div class="swiper-slide">
    <blockquote class="product-clipping">
      <p>
        @if($clipping['url'])
          <a href="{{ $clipping['url'] }}">
        @endif

          {!! $clipping['title'] !!}

        @if($clipping['url'])
          </a>
        @endif
      </p>
      @if($clipping['source'])
        <footer>
          <cite>{!! $clipping['source'] !!}</cite>
        </footer>
      @endif
    </blockquote>
  </div>
@endif

@if($video = App::getProductVideo($id))
  <div class="swiper-slide">
    <div class="swiper-slide-video">
      <div class="responsive-video">
        {!! $video['player'] !!}
      </div>
    </div>
  </div>
@endif

@if($audio = App::getProductAudio($id))
  <div class="swiper-slide">
    <div class="product-audio">
      @if($audio['title'])
        <p>{{ $audio['title'] }}</p>
      @endif
      {!! $audio['player'] !!}
      @if($audio['description'])
        <p>{!! $audio['description'] !!}</p>
      @endif
    </div>
  </div>
@endif

@if($manuscript = App::getProductManuscript($id))
  @foreach($manuscript['images'] as $image)
    <div class="swiper-slide">
      <div class="product-manuscript">
        <figure>
          {!! $image['image'] !!}

          @if($image['caption'])
            <figcaption>{{ $image['caption'] }}</figcaption>
          @endif
        </figure>
      </div>
    </div>
  @endforeach
@endif
