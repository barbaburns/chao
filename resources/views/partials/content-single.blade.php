<article @php post_class() @endphp>
  @include('partials.page-header', ['title' => App::getPageTitle(93)])

  <header class="post-header">
    <div class="post-categories">
      {!! the_category('|') !!}
    </div>
    <div class="post-tags">
      {!! the_tags('', '|') !!}
    </div>
    <h2 class="entry-title">{!! get_the_title() !!}</h2>
    @include('partials.entry-meta')
  </header>

  @if(has_post_thumbnail())
    <figure class="feautured-image">
      @php the_post_thumbnail('thumb-square') @endphp
    </figure>
  @endif

  <div class="entry-content">
    @php the_content() @endphp
    @include('partials.share-buttons')
  </div>

  @php comments_template('/partials/comments.blade.php') @endphp

  @include('components.books-list', [
    'books' => $get_related_books
  ])

  @include('components.posts-list', [
    'title' => 'Posts relacionados:',
    'posts' => $get_related_posts
  ])
</article>
