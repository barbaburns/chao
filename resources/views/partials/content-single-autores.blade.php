<div class="grid-header">
  @include('partials.page-header', ['title' => 'Autores'])
  @include('partials.page-header-text', ['field' => 'header_authors'])
</div>

@if(has_post_thumbnail())
  <figure class="feautured-image">
    @php the_post_thumbnail('thumb-square') @endphp
  </figure>
@endif

<div class="grid-sidebar">
  {!! get_search_form(false) !!}
  @component('components.dropdown', [
    'id' => 'dropdown-authors',
    'items' => $get_all_authors
  ])
    Outros autores
  @endcomponent
</div>

<article @php post_class('grid-content') @endphp>
  <h2 class="entry-title">{!! get_the_title() !!}</h2>

  <div class="entry-content">
    @php the_content() @endphp
  </div>
  
</article>

<div class="slider" style="grid-area:slider">
  @include('components.books-list', [
      'books' => $get_related_books
    ]) 
</div>
