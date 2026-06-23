@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    <div class="grid-header">
      @include('partials.page-header')
      @include('partials.page-header-text', ['field' => 'header_publisher'])
    </div>
    @include('partials.team-list')
    <div class="grid-content">
      @include('partials.content-page')
    </div>
  @endwhile
@endsection
