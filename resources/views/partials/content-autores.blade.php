<?php
$resumo = get_field('resumo'); ?>

<article @php post_class() @endphp>
  @if(has_post_thumbnail())
    <figure class="feautured-image">
      @php the_post_thumbnail('thumb-square-big') @endphp
    </figure>
  @endif
  <div class="post-content">
    <header>
      <h2 class="entry-title">
        <a href="{{ get_permalink() }}">
          {!! get_the_title() !!}
        </a>
      </h2>
    </header>
    <div class="entry-summary">
      <p>{!! $resumo !!}</p>
      <span class="underline">Leia o perfil completo</span>
    </div>
  </div>
</article>
