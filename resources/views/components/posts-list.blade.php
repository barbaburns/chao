@if($posts)
  <section class="popular-posts">
    <h3>{{ $title }}</h3>
    <ul class="popular-posts-list">
      @foreach($posts as $post)
        <li>
          <a href="{{ $post['url'] }}">
            {{ $post['title'] }} <span class="underline">Leia mais »</span>
          </a>
        </li>
      @endforeach
    </ul>
  </section>
@endif
