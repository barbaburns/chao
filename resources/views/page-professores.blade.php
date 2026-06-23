@extends('layouts.app')
pg prof
@section('content')
  @while(have_posts()) @php the_post() @endphp
    <div class="grid-header">
      @include('partials.page-header')
      @include('partials.page-header-text', ['field' => 'header_teachers'])
    </div>
    <div class="grid-content">
      @include('partials.content-page')
    </div>
  @endwhile
@endsection
