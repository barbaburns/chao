@php
  $search = $manuscript_search ?? [
    'query' => '',
    'has_query' => false,
    'page_count' => 0,
    'pages_with_text_count' => 0,
    'match_count' => 0,
    'matches' => [],
    'pages' => [],
    'viewer_pages' => [],
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
  }, array_filter($search['viewer_pages'], function ($page) {
    return !empty($page['image_url']);
  })));
  $hasViewerPages = !empty($viewerPages);
  $categoryList = get_the_category_list('|');
  $tagList = get_the_tag_list('', '|');
@endphp

<article @php post_class('manuscript-single') @endphp>
    @include('partials.page-header', ['title' => 'Manuscritos'])

    @if(has_post_thumbnail())
        <figure class="feautured-image">
        @php the_post_thumbnail('thumb-square') @endphp
        </figure>
    @endif

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

  <div class="entry-content manuscript-single__content">
    @php the_content() @endphp

    @if($search['viewer_enabled'])
      <section
        class="manuscript-single__viewer{{ $hasViewerPages ? '' : ' manuscript-single__viewer--empty' }}"
        id="manuscript-viewer"
        aria-labelledby="manuscript-viewer-title"
        @if($hasViewerPages)
          data-manuscript-viewer
          data-initial-page-number="{{ $search['initial_page_number'] }}"
        @endif
      >
        <h3 id="manuscript-viewer-title" class="screen-reader-text">Visualizador do manuscrito</h3>
        @if($hasViewerPages)
          <script type="application/json" data-manuscript-pages>{!! wp_json_encode($viewerPages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
          <span class="screen-reader-text" data-manuscript-page-label>Pagina</span>

          <div class="manuscript-single__viewer-shell">
            <div class="manuscript-single__viewer-osd" data-manuscript-osd></div>
            <div class="manuscript-single__viewer-overlay-controls" data-manuscript-overlay-controls>
              <div class="manuscript-single__viewer-actions">
                @if(!$search['has_query'])
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
                @endif

                <div class="manuscript-single__viewer-mode-actions">
                  <button
                    type="button"
                    class="btn manuscript-single__viewer-compact-button manuscript-single__viewer-search-toggle"
                    data-manuscript-search-toggle
                    data-manuscript-tooltip="Buscar"
                    aria-label="Buscar no manuscrito"
                    aria-expanded="false"
                    aria-controls="manuscript-search-overlay"
                  >
                    @include('icon::search')
                  </button>
                  <button
                    type="button"
                    class="btn manuscript-single__viewer-compact-button manuscript-single__viewer-search-reset"
                    data-manuscript-search-reset
                    data-manuscript-clear-url="{{ get_permalink() }}"
                    data-manuscript-tooltip="voltar ao manuscrito"
                    aria-label="Voltar ao manuscrito"
                    @if(!$search['has_query']) hidden @endif
                  >↩</button>
                  <button type="button" class="btn manuscript-single__viewer-compact-button" data-manuscript-fit-page data-manuscript-tooltip="Ajustar pagina inteira" aria-label="Ajustar pagina inteira">□</button>
                  <button type="button" class="btn manuscript-single__viewer-compact-button" data-manuscript-zoom-out data-manuscript-tooltip="Diminuir zoom" aria-label="Diminuir zoom">-</button>
                  <button type="button" class="btn manuscript-single__viewer-compact-button" data-manuscript-zoom-in data-manuscript-tooltip="Aumentar zoom" aria-label="Aumentar zoom">+</button>
                  <button type="button" class="btn manuscript-single__viewer-compact-button" data-manuscript-fit-width data-manuscript-tooltip="Ajustar largura" aria-label="Ajustar largura">
                    <svg class="manuscript-single__viewer-fit-width-icon" viewBox="0 0 24 16" aria-hidden="true" focusable="false">
                      <path d="M2 8h20M6 3 1 8l5 5M18 3l5 5-5 5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                    </svg>
                  </button>
                  <button type="button" class="btn manuscript-single__viewer-compact-button fullscreen" data-manuscript-fullscreen data-manuscript-tooltip="Tela cheia" aria-label="Tela cheia">
                    <svg class="manuscript-single__viewer-fullscreen-icon manuscript-single__viewer-fullscreen-icon--enter" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                      <path d="M1 5V1h4M11 1h4v4M15 11v4h-4M5 15H1v-4" fill="none" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <svg class="manuscript-single__viewer-fullscreen-icon manuscript-single__viewer-fullscreen-icon--exit" viewBox="0 0 16 16" aria-hidden="true" focusable="false" hidden>
                      <path d="M1 5h4V1M11 1v4h4M15 11h-4v4M5 15v-4H1" fill="none" stroke="currentColor" stroke-width="2"/>
                    </svg>
                  </button>
                </div>
              </div>

              <form
                class="manuscript-single__viewer-search-form"
                id="manuscript-search-overlay"
                data-manuscript-search-overlay
                method="get"
                action="{{ get_permalink() }}"
                hidden
              >
                <div class="manuscript-single__viewer-search-header">
                  <span class="manuscript-single__viewer-search-title">Buscar no manuscrito</span>
                  <button
                    type="button"
                    class="btn manuscript-single__viewer-search-close"
                    data-manuscript-search-close
                    aria-label="Fechar busca"
                  >×</button>
                </div>

                <label class="screen-reader-text" for="manuscript-search-input-overlay">Buscar palavra no manuscrito</label>
                <div class="manuscript-single__viewer-search-row">
                  <input
                    id="manuscript-search-input-overlay"
                    type="search"
                    name="busca"
                    value=""
                    placeholder="{{ $search['has_query'] ? 'Nova busca' : 'Ex.: carta' }}"
                    data-manuscript-search-input
                  >
                  <button type="submit" class="btn">Buscar</button>
                </div>

                <div class="manuscript-single__viewer-search-meta">
                  @if($search['has_query'])
                    <span>Busca ativa: "{{ $search['query'] }}"</span>
                    <a class="manuscript-single__search-clear" href="{{ get_permalink() }}">Limpar busca</a>
                  @else
                    <span>Digite uma palavra para destacar ocorrencias na imagem.</span>
                  @endif
                </div>
              </form>
            </div>

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
        @elseif($search['has_query'])
          <div class="manuscript-single__viewer-empty" role="status">
            <p class="manuscript-single__viewer-empty-title">Nenhum resultado encontrado para "{{ $search['query'] }}"</p>

            <form
              class="manuscript-single__viewer-search-form manuscript-single__viewer-search-form--static"
              method="get"
              action="{{ get_permalink() }}"
            >
              <div class="manuscript-single__viewer-search-header">
                <span class="manuscript-single__viewer-search-title">Buscar no manuscrito</span>
              </div>

              <label class="screen-reader-text" for="manuscript-search-input-empty">Buscar palavra no manuscrito</label>
              <div class="manuscript-single__viewer-search-row">
                <input
                  id="manuscript-search-input-empty"
                  type="search"
                  name="busca"
                  value=""
                  placeholder="Nova busca"
                >
                <button type="submit" class="btn">Buscar</button>
              </div>

              <div class="manuscript-single__viewer-search-meta">
                <span>Busca ativa: "{{ $search['query'] }}"</span>
                <a class="manuscript-single__search-clear" href="{{ get_permalink() }}">Limpar busca</a>
              </div>
            </form>
          </div>
        @endif
      </section>
    @endif
  </div>

  @include('components.books-list', [
    'books' => $related_books ?? [],
    'variant' => 'manuscript'
  ])
</article>
