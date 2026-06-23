@if($get_home_banner)
  <figure class="home-banner">
    {!! $get_home_banner['image'] !!}
    {!! $get_home_banner['image_mobile'] !!}

    @if($get_home_banner['title'])
      <figcaption>
        {{ $get_home_banner['title'] }}

        @if($get_home_banner['url_title'])
          <a href="{{ $get_home_banner['url'] }}">
            {{ $get_home_banner['url_title'] ?: 'Conheça' }} >>
          </a>
        @endif
      </figcaption>
    @endif
  </figure>
@endif
