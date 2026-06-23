@if($get_short_texts)
  <div class="short-texts">
    <div class="short-texts-wrapper">
      <div class="swiper-container" id="swiper-short-texts">
        <div class="swiper-wrapper">
          @foreach($get_short_texts as $item)
            <div class="swiper-slide">
              &#8212;

              @component('components.link', ['link' => $item['url']])
                {{ $item['text'] }}
              @endcomponent

              &#8212;
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
@endif
