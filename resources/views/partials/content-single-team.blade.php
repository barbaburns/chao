<div class="grid-header">
  @include('partials.page-header', ['title' => App::getPageTitle(26)])
  @include('partials.page-header-text', ['field' => 'header_publisher'])
</div>
@include('partials.team-list')
<article @php post_class('grid-content') @endphp>
  <h2 class="entry-title">{!! get_the_title() !!}</h2>
  @php the_content() @endphp
</article>
