<form role="search" method="get" class="search-form" action="{{ esc_url( home_url( '/' ) ) }}">
  <label>
    <span class="screen-reader-text">{{ _x('Search for:', 'label') }}</span>
    <input type="search" class="search-field" placeholder="{!! esc_attr_x('Busca', 'placeholder', 'chao') !!}" value="{{ get_search_query() }}" name="s" />
  </label>
  <button type="submit" class="search-submit" aria-label="{!! esc_attr_x('search', 'aria', 'chao') !!}">
    @include('icon::search')
  </button>
</form>
