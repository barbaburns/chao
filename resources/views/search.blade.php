@extends('layouts.app')

@section('content')
  <div class="grid-header">
    @include('partials.page-header')
  </div>

  <div class="grid-content">
    @if (!have_posts())
      <p class="alert">
        {{ __('Sorry, no results were found.', 'chao') }}
      </p>
      {!! get_search_form(false) !!}
    @else
      @include('partials.page-header', [
        'head' => 'h2',
        'title' => 'Resultados para <span class="text-red">'.get_search_query().'</span>',
      ])
    @endif

    @while(have_posts()) @php the_post() @endphp
      @include('partials.content-search')
    @endwhile

    {!! App::getPagination() !!}
  </div>
@endsection
