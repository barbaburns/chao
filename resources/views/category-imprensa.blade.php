@extends('layouts.app')
hooray
@section('content')
  <div class="grid-header">
    @include('partials.page-header', ['title' => App::getPageTitle(93)])
    @include('partials.page-header-text', ['field' => 'header_blog'])
  </div>

  <div class="grid-sidebar">
    
    <form role="search" method="get" class="search-form" action="{{ esc_url( home_url( '/categoria/imprensa/' ) ) }}">
      <label>
        <span class="screen-reader-text">{{ _x('Search for:', 'label') }}</span>
        <input type="search" class="search-field" placeholder="{!! esc_attr_x('Busca', 'placeholder', 'chao') !!}" value="{{ get_search_query() }}" name="s" />
        <input type="hidden" name="post_type" value="post" />
      </label>
      <button type="submit" class="search-submit" aria-label="{!! esc_attr_x('search', 'aria', 'chao') !!}">
        @include('icon::search')
      </button>
    </form>

    @if(is_category('livreiros'))
      <a href="{{ App::getPageLink('livreiros') }}" class="btn">Cadastro de livreiros</a>
    @endif

    @if(is_category('professores'))
      <a href="{{ App::getPageLink('professores') }}" class="btn">Cadastro de professores</a>
    @endif

    @component('components.dropdown', [
      'id' => 'dropdown-categories',
      'items' => App::getAllCategories( "imprensa")
    ])
      Categorias
    @endcomponent

    <!-- @component('components.dropdown', [
      'id' => 'dropdown-tags',
      'items' => App::getAllTags()
    ])
      Tags
    @endcomponent -->

    @component('components.dropdown', [
    'id' => 'dropdown-archive',
    'items' => App::getAllMonths()
    ])
      Arquivo
    @endcomponent

    @include('components.posts-list', [
    'title' => 'Posts populares:',
    'posts' => App::getPopularPosts()
    ])

    {!! App::getPagination() !!}
  </div>

  <div class="grid-content">
    @if(!is_home())
      @include('partials.page-header', ['head' => 'h2'])
    @endif

    @if (!have_posts())
      <div class="alert alert-warning">
        {{ __('Sorry, no results were found.', 'chao') }}
      </div>
      {!! get_search_form(false) !!}
    @endif

    @while (have_posts()) @php the_post() @endphp
      @include('partials.content-'.get_post_type())
    @endwhile
  </div>
@endsection
