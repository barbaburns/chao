@php
  $clipping = App::getProductClipping($id);
  $audio = App::getProductAudio($id);
  $video = App::getProductVideo($id);
  $excerpt = App::getProductExcerpt($id);
  $manuscript = App::getProductManuscript($id);
@endphp

@if($clipping || $audio || $video || $excerpt || $manuscript)
  <section class="product-extra">
    @if($clipping)
      <div class="product-extra product-extra-clipping">
        <h2>Imprensa</h2>
        <blockquote class="product-clipping">
          <p>
            @if($clipping['url'])
              <a href="{{ $clipping['url'] }}" target="_blank">
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

    @if($audio)
      <div class="product-extra product-extra-audio">
        <h2>Áudio</h2>
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

    @if($video)
      <div class="product-extra product-extra-video">
        <h2>Vídeo</h2>
        @if($video['title'])
          <p>{!! $video['title'] !!}</p>
        @endif
        <div class="responsive-video">
          {!! $video['player'] !!}
        </div>
        @if($video['caption'])
          <p>{!! $video['caption'] !!}</p>
        @endif
      </div>
    @endif

    @if($excerpt)
      <div class="product-extra product-extra-excerpt">
        <h2>Trecho</h2>
        <blockquote class="product-excerpt">

            <!-- @component('components.link', ['link' => $excerpt['url']])
              {!! $excerpt['text'] !!}
            @endcomponent -->
            <?php echo wp_kses_post( the_field('excerpt_text') ); ?>

        </blockquote>
      </div>
    @endif

    @if($manuscript)
      <div class="product-extra product-extra-manuscript">
        <h2>Manuscrito</h2>
        <div class="product-manuscript">
          @foreach($manuscript['images'] as $image)
            <figure>
              {!! $image['image'] !!}

              @if($image['caption'])
                <figcaption>{{ $image['caption'] }}</figcaption>
              @endif
            </figure>
          @endforeach
        </div>
      </div>
    @endif
  </section>
@endif
