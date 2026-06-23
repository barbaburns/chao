<header class="page-header">
  @if (is_single() && get_post_type() == 'post')
    <h1><a href="{!! get_post_type_archive_link('post') !!}">{!! $title !!}</a></h1>
  
  @elseif (is_single() && get_post_type() == 'autores')
    <h1><a href="{!! get_post_type_archive_link('autores') !!}">{!! $title !!}</a></h1>

  @elseif (is_single() && get_post_type() == 'equipe')
    <h1><a href="{!! get_permalink( get_page_by_path( 'a-chao' ) ) !!}">{!! $title !!}</a></h1>

  @elseif (is_front_page())
    <h2><a href="{!! get_permalink( get_page_by_path( 'nossos-livros' ) ) !!}">{!! $title !!}</a></h2>

  @else
  <{{ isset($head) ? $head : 'h1' }}>
    {!! isset($title) ? $title : App::title() !!}
  </{{ isset($head) ? $head : 'h1' }}>

  @endif
  
  
</header>
