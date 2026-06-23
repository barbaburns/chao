<header class="banner">
  <div class="visible-area">
    <a class="brand" href="{{ home_url('/') }}" aria-label="{{ get_bloginfo('name', 'display') }}">
      @include('icon::logo')
      @include('icon::logo-horizontal')
    </a>

    <button aria-label="Abrir/fechar menu principal" id="btn-open-menu">
      @include('icon::menu')
    </button>

    @if(App\isBlog())
      @if(!get_post_type_archive_link('post') == 'mais')
        <div class="header-blog-title">Mais</div>
      @else
        <div class="header-blog-title"><a href="{!! get_post_type_archive_link('post') !!}">Mais</a></div>
      @endif
    @endif

    @include('partials.social')
  </div>

  <div class="hidden-area">
    <nav class="nav-primary" aria-hidden="true">
      {!! get_search_form(false) !!}

      @if (has_nav_menu('primary_navigation'))
        {!! wp_nav_menu(['theme_location' => 'primary_navigation', 'menu_class' => 'nav']) !!}
      @endif
    </nav>

    @include('partials.social')

    @include('partials.book-area')
  </div>
</header>
