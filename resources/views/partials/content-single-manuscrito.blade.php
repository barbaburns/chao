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
  $statusLabels = [
    'pendente' => 'Pendente',
    'em_revisao' => 'Em revisao',
    'revisado' => 'Revisado',
  ];
  $viewerPages = array_values(array_map(function ($page) {
    return [
      'pageNumber' => (int) ($page['page_number'] ?? 0),
      'imageUrl' => (string) ($page['image_url'] ?? ''),
      'imageAlt' => 'Pagina ' . (int) ($page['page_number'] ?? 0) . ' de ' . get_the_title(),
      'highlightBoxes' => array_values($page['highlight_boxes'] ?? []),
    ];
  }, array_filter($search['pages'], function ($page) {
    return !empty($page['image_url']);
  })));
@endphp

<article @php post_class('manuscript-single') @endphp>
  @include('partials.page-header', ['title' => 'Manuscritos'])

  <header class="manuscript-single__header">
    <h2 class="entry-title">{!! get_the_title() !!}</h2>
  </header>

  <div class="manuscript-single__intro">
    @php the_content() @endphp
  </div>

  <section class="manuscript-single__search" aria-labelledby="manuscript-search-title">
    <div class="manuscript-single__search-header">
      <h3 id="manuscript-search-title">Busca no manuscrito</h3>
      <p>Digite uma palavra exata para localizar em quais paginas ela aparece na transcricao revisada.</p>
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
      <span>{{ $search['page_count'] }} paginas extraidas</span>
      <span>{{ $search['pages_with_text_count'] }} paginas com texto indexado</span>
      @if($search['has_query'])
        <span>{{ $search['match_count'] }} paginas com ocorrencia para "{{ $search['query'] }}"</span>
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
        <p>Use os controles para navegar pelas paginas e ajustar a escala da imagem.</p>
      </div>

      <div class="manuscript-single__viewer-toolbar">
        <div class="manuscript-single__viewer-nav">
          <button type="button" class="btn" data-manuscript-prev>Anterior</button>
          <span data-manuscript-page-label>Pagina</span>
          <button type="button" class="btn" data-manuscript-next>Proxima</button>
        </div>

        <div class="manuscript-single__viewer-zoom">
          <button type="button" class="btn" data-manuscript-scale-mode="fit-width">Fit width</button>
          <button type="button" class="btn" data-manuscript-scale-mode="fit-height">Fit height</button>
          <button type="button" class="btn" data-manuscript-scale-mode="actual">100%</button>
          <button type="button" class="btn" data-manuscript-zoom-out>-</button>
          <button type="button" class="btn" data-manuscript-zoom-in>+</button>
        </div>
      </div>

      <p class="manuscript-single__viewer-highlight-label" data-manuscript-highlight-label></p>

      <div class="manuscript-single__viewer-frame" data-manuscript-frame>
        <div class="manuscript-single__viewer-stage" data-manuscript-stage>
          <div class="manuscript-single__viewer-canvas" data-manuscript-canvas>
            <img data-manuscript-image alt="">
            <div class="manuscript-single__viewer-overlay" data-manuscript-overlay></div>
          </div>
        </div>
      </div>

      <ol class="manuscript-single__viewer-index">
        @foreach($viewerPages as $page)
            <li>
              <button
                type="button"
                class="manuscript-single__viewer-index-button"
                data-manuscript-page-button
                data-page-number="{{ $page['pageNumber'] }}"
              >
                Pagina {{ $page['pageNumber'] }}
              </button>
            </li>
        @endforeach
      </ol>
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
                <span>{{ $statusLabels[$match['editorial_status']] ?? $match['editorial_status'] }}</span>
                @if(!empty($match['highlight_box_count']))
                  <span>{{ $match['highlight_box_count'] }} destaque(s) visual(is)</span>
                @endif
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

  <section class="manuscript-single__pages" aria-labelledby="manuscript-pages-title">
    <h3 id="manuscript-pages-title">Transcricoes por pagina</h3>

    @if(!empty($search['pages']))
      <div class="manuscript-single__pages-list">
        @foreach($search['pages'] as $page)
          <article
            class="manuscript-single__page"
            id="{{ $page['anchor_id'] }}"
            data-manuscript-page-panel
            data-page-number="{{ $page['page_number'] }}"
          >
            <header class="manuscript-single__page-header">
              <div>
                <strong>Pagina {{ $page['page_number'] }}</strong>
                @if(!empty($page['filename']))
                  <span>{{ $page['filename'] }}</span>
                @endif
              </div>
              <span class="manuscript-single__status manuscript-single__status--{{ $page['editorial_status'] }}">
                {{ $statusLabels[$page['editorial_status']] ?? $page['editorial_status'] }}
              </span>
            </header>

            @if(!empty($page['image_url']))
              <p class="manuscript-single__page-actions">
                <a href="#manuscript-viewer" data-manuscript-go-page="{{ $page['page_number'] }}">Abrir esta pagina no visualizador</a>
              </p>
            @endif

            <p class="manuscript-single__page-meta">
              <strong>Fonte do texto:</strong>
              @if($page['effective_text_source'] === 'reviewed')
                revisao manual
              @elseif($page['effective_text_source'] === 'raw')
                HTR bruto
              @else
                indisponivel
              @endif
            </p>

            @if(!empty($page['effective_text']))
              <div class="manuscript-single__page-text">{!! $page['effective_text_html'] !!}</div>
            @else
              <p class="manuscript-single__empty">Esta pagina ainda nao tem transcricao revisada salva.</p>
            @endif

            @if(!empty($page['review_notes']))
              <p class="manuscript-single__page-notes"><strong>Observacoes:</strong> {{ $page['review_notes'] }}</p>
            @endif
          </article>
        @endforeach
      </div>
    @else
      <p class="manuscript-single__empty">Envie o PDF para o worker e salve a revisao editorial para habilitar a busca.</p>
    @endif
  </section>
</article>
