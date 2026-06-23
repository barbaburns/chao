@extends('layouts.app')

@section('content')
  <div class="grid-header">
    @include('partials.page-header', ['title' => App::getPageTitle(93)])
    @include('partials.page-header-text', ['field' => 'header_blog'])
  </div>

  <div class="grid-sidebar">

    @if(is_category('livreiros'))
      <div class="dropdown">
        <a href="{{ App::getPageLink('cadastro-de-livreiros') }}" class="btn dropdown-toggle">Cadastro de livreiros</a>
      </div>
    @endif

    @if(is_category('professores'))
    <div class="dropdown">
       <a href="{{ App::getPageLink('cadastro-de-professores') }}" class="btn dropdown-toggle">Cadastro de professores</a>
    </div>
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
    
    {!! get_search_form(false) !!}

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
