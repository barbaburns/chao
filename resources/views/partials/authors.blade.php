<div class="dropdown">
  <button class="btn btn-primary dropdown-toggle" type="button" id="dropdown-authors" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    OUTROS AUTORES
  </button>
  <div class="dropdown-menu" aria-labelledby="dropdown-authors">
    @foreach ($get_all_authors as $author)
      <a class="dropdown-item" href="{{ $author['url'] }}">{{ $author['title'] }}</a>
    @endforeach
  </div>
</div>
