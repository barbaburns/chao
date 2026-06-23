@extends('layouts.app')

@section('content')
  @include('partials.page-header')

  @if (!have_posts())
    <p class="alert">
      {{ __('Sorry, but the page you were trying to view does not exist.', 'chao') }}
    </p>
    {!! get_search_form(false) !!}
  @endif
@endsection
