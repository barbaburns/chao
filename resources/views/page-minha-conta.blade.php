@extends('layouts.app')

@section('content')
  @while(have_posts()) @php the_post() @endphp
    @if(is_user_logged_in())
      @include('partials.page-header')
      @php
        do_action('woocommerce_account_navigation');
      @endphp
      @include('partials.content-page')
    @else
      <div class="grid-header">
        @include('partials.page-header')
      </div>
      <div class="grid-content">
        @include('partials.content-page')
      </div>
    @endif
  @endwhile
@endsection
