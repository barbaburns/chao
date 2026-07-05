@php
  $search = $manuscript_search ?? [
    'query' => '',
    'has_query' => false,
    'page_count' => 0,
    'pages_with_text_count' => 0,
    'match_count' => 0,
    'matches' => [],
    'pages' => [],
  ];
  $viewerPages = array_values(array_map(function ($page) {
    $layoutData = isset($page['layout_data']) && is_array($page['layout_data']) ? $page['layout_data'] : [];
    $pageMeta = isset($layoutData['page']) && is_array($layoutData['page']) ? $layoutData['page'] : [];

    return [
      'pageNumber' => (int) ($page['page_number'] ?? 0),
      'imageUrl' => (string) ($page['image_url'] ?? ''),
      'imageAlt' => 'Pagina ' . (int) ($page['page_number'] ?? 0) . ' de ' . get_the_title(),
      'pageWidth' => (float) ($pageMeta['width'] ?? 0),
      'pageHeight' => (float) ($pageMeta['height'] ?? 0),
      'highlightBoxes' => array_values((array) ($page['highlight_boxes'] ?? [])),
    ];
  }, array_filter($search['pages'], function ($page) {
    return !empty($page['image_url']);
  })));
  $categoryList = get_the_category_list('|');
  $tagList = get_the_tag_list('', '|');
@endphp

<article @php post_class('manuscript-single') @endphp>
  @include('partials.page-header', ['title' => 'Manuscritos'])

  <header class="post-header">
    @if($categoryList)
      <div class="post-categories">
        {!! $categoryList !!}
      </div>
    @endif
    @if($tagList)
      <div class="post-tags">
        {!! $tagList !!}
      </div>
    @endif
    <h2 class="entry-title">{!! get_the_title() !!}</h2>

  </header>

  @if(has_post_thumbnail())
    <figure class="feautured-image">
      @php the_post_thumbnail('thumb-square') @endphp
    </figure>
  @endif

  <div class="entry-content manuscript-single__content">
    @php the_content() @endphp

    <section class="manuscript-single__search" aria-labelledby="manuscript-search-title">
        <div class="manuscript-single__search-header">
            <h3 id="manuscript-search-title">Busca no manuscrito</h3>
        </div>

        <form class="manuscript-single__search-form" method="get" action="{{ get_permalink() }}">
            <label class="screen-reader-text" for="manuscript-search-input">Buscar palavra no manuscrito</label>
            <input
                id="manuscript-search-input"
                type="search"
                name="busca"
                value="{{ $search['query'] }}"
                placeholder="Ex.: carta"
            >
            <button type="submit" class="btn">Buscar</button>
        </form>

        <div class="manuscript-single__search-meta">
            @if($search['has_query'])
                <span>{{ $search['match_count'] }} paginas com ocorrencia para "{{ $search['query'] }}"</span>
                <a class="manuscript-single__search-clear" href="{{ get_permalink() }}">Limpar busca</a>
            @endif
        </div>
    </section>

    @if($search['viewer_enabled'])
      <section
        class="manuscript-single__viewer"
        id="manuscript-viewer"
        aria-labelledby="manuscript-viewer-title"
        data-manuscript-viewer
        data-initial-page-number="{{ $search['initial_page_number'] }}"
      >
        <script type="application/json" data-manuscript-pages>{!! wp_json_encode($viewerPages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

        <div class="manuscript-single__viewer-header">
          <h3 id="manuscript-viewer-title">Visualizador</h3>
          <p>Use o mouse, trackpad, gesto de toque ou, com o visualizador em foco, as teclas +, - e 0 para zoom.</p>
        </div>

        <div class="manuscript-single__viewer-toolbar">
          <div class="manuscript-single__viewer-nav">
            <span data-manuscript-page-label>Pagina</span>
          </div>

          <div class="manuscript-single__viewer-actions">
            <form class="manuscript-single__viewer-jump" data-manuscript-page-jump-form>
              <label class="screen-reader-text" for="manuscript-page-input">Ir para pagina</label>
              <input
                id="manuscript-page-input"
                type="number"
                min="1"
                max="{{ count($viewerPages) }}"
                step="1"
                inputmode="numeric"
                data-manuscript-page-input
              >
              <span class="manuscript-single__viewer-jump-total">de {{ count($viewerPages) }}</span>
              <button type="submit" class="btn">Ir</button>
            </form>

            <div class="manuscript-single__viewer-mode-actions">
              <button type="button" class="btn manuscript-single__viewer-compact-button" data-manuscript-fit-page data-manuscript-tooltip="Ajustar pagina inteira" aria-label="Ajustar pagina inteira">□</button>
              <button type="button" class="btn manuscript-single__viewer-compact-button" data-manuscript-zoom-actual data-manuscript-tooltip="Zoom 100%" aria-label="Zoom 100%">1:1</button>
              <button type="button" class="btn manuscript-single__viewer-compact-button" data-manuscript-fit-width data-manuscript-tooltip="Ajustar largura" aria-label="Ajustar largura">↔</button>
              <button type="button" class="btn manuscript-single__viewer-compact-button" data-manuscript-fullscreen data-manuscript-tooltip="Tela cheia" aria-label="Tela cheia">⤢</button>
            </div>
          </div>
        </div>

        <div class="manuscript-single__viewer-shell">
          <div class="manuscript-single__viewer-osd" data-manuscript-osd></div>
          <button
            type="button"
            class="manuscript-single__viewer-edge-button manuscript-single__viewer-edge-button--prev"
            data-manuscript-prev
            aria-label="Pagina anterior"
            title="Pagina anterior"
          >
            <span class="manuscript-single__viewer-edge-icon" aria-hidden="true">←</span>
          </button>
          <button
            type="button"
            class="manuscript-single__viewer-edge-button manuscript-single__viewer-edge-button--next"
            data-manuscript-next
            aria-label="Proxima pagina"
            title="Proxima pagina"
          >
            <span class="manuscript-single__viewer-edge-icon" aria-hidden="true">→</span>
          </button>
        </div>
      </section>
    @endif

    @if($search['has_query'])
      <section class="manuscript-single__results" aria-labelledby="manuscript-results-title">
        <h3 id="manuscript-results-title">Resultados</h3>

        @if(!empty($search['matches']))
          <ol class="manuscript-single__results-list">
            @foreach($search['matches'] as $match)
              <li class="manuscript-single__result">
                <div class="manuscript-single__result-meta">
                  <a href="#manuscript-viewer" data-manuscript-go-page="{{ $match['page_number'] }}">Pagina {{ $match['page_number'] }}</a>
                  <span>{{ $match['occurrences'] }} ocorrencia(s)</span>
                </div>
                <p class="manuscript-single__result-excerpt">{!! $match['excerpt_html'] !!}</p>
              </li>
            @endforeach
          </ol>
        @else
          <p class="manuscript-single__empty">Nenhuma pagina indexada contem essa palavra exata no momento.</p>
        @endif
      </section>
    @endif

  </div>

  @include('components.books-list', [
    'books' => $related_books ?? [],
    'variant' => 'manuscript'
  ])
</article>
