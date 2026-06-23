<div class="dropdown">
  <button class="btn dropdown-toggle" type="button" id="{{ $id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    {!! $slot !!}
    @include('icon::arrow-bottom')
  </button>
  <div class="dropdown-menu" aria-labelledby="{{ $id }}">
    @foreach ($items as $item)
      <a class="dropdown-item {{ isset($item['class']) ? $item['class'] : '' }}" href="{{ $item['url'] }}" target="_blank">{{ $item['title'] }}</a>
    @endforeach
  </div>
</div>
