<section class="product-page-info">
  @component('components.collapse', [
    'id' => 'collapse-content',
    'title' => 'Sinopse',
    'open' => true,
  ])
    @php the_content() @endphp
  @endcomponent

  @if($authors_list = SingleProduct::getProductAuthors())
    @foreach($authors_list as $authors)
      @component('components.collapse', [
        'id' => 'collapse-'.$authors['role_class'],
        'title' => $authors['role'],
        'open' => false,
      ])
        @foreach($authors['authors'] as $author)
       
          <p>
            <a href="{{ $author['url'] }}">{{ $author['title'] }}</a>
            
            {!! $author['resumo'] !!}
            
            @if($author['extra_url'])
              <a href="{{ $author['extra_url'] }}">{{ $author['extra_url_text'] }}</a>
            @endif
          </p>
        @endforeach
      @endcomponent
    @endforeach
  @endif
</section>
