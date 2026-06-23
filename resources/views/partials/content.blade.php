<article @php post_class() @endphp>
  @if(has_post_thumbnail())
    <figure class="feautured-image">
      @php the_post_thumbnail('thumb-square-big') @endphp
    </figure>
  @endif
  <div class="post-content">
    <header>
      <time class="updated" datetime="{{ get_post_time('c', true) }}">{{ get_the_date('d F Y') }}</time>
      <h2 class="entry-title"><a href="{{ get_permalink() }}">{!! get_the_title() !!}</a></h2>
    </header>
    <div class="entry-summary">
      @php the_excerpt() @endphp

      <span class="underline">Leia o post completo</span>
    </div>
  </div>
</article>
