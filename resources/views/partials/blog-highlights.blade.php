@if($get_blog_highlights)
  <div class="blog-highlights">
    <div class="blog-highlights-content">
      <div class="grid-header">
        <header class="page-header">
          <h2><a href="{!! get_permalink( get_page_by_path( 'mais' ) ) !!}">Mais</a></h2>
        </header>
        
        @include('partials.page-header-text', ['field' => 'header_blog'])

        <a href="{{ App::blogLink() }}">Veja todos os posts</a>
      </div>

      <div class="grid-content">
        @foreach($get_blog_highlights as $item)
          <article class="{{ $item['class'] }}">
            @if ($item['image'])
              <figure class="feautured-image">
                {!! $item['image'] !!}
              </figure>
            @endif
            <header>
              <time class="updated" datetime="{{ $item['date_raw'] }}">{{ $item['date'] }}</time>
              <h3>
                <a href="{{ $item['url'] }}">
                  {{ $item['title'] }}
                </a>
              </h3>
              <span class="underline">leia mais</span>
            </header>
          </article>
        @endforeach
      </div>
    </div>
  </div>
@endif
