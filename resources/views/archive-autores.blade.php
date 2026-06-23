@extends('layouts.app')

@section('content')
  <div class="grid-header">
    @include('partials.page-header')
    @include('partials.page-header-text', ['field' => 'header_authors'])
  </div>
  <div class="grid-sidebar">
    {!! get_search_form(false) !!}
    {!! App::getPagination() !!}
  </div>
  <div class="grid-content">
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
